<?php
session_start();
include("../includes/config.php");
include("../includes/staffAuth.php");
include("../includes/mailer.php");
include("../includes/order_email.php");
include("../includes/receipt_pdf.php");

// Allowed status changes. Orders only move forward, and only a Processing order can be cancelled.
$nextStatus = [
    'Processing' => ['Shipped', 'Canceled'],
    'Shipped'    => ['Delivered'],
    'Delivered'  => [],
    'Canceled'   => [],
];
$label = ['Processing' => 'To Ship', 'Shipped' => 'To Receive', 'Delivered' => 'Delivered', 'Canceled' => 'Cancelled'];
$color = ['Processing' => 'warning', 'Shipped' => 'info', 'Delivered' => 'success', 'Canceled' => 'secondary'];
$id = (int)($_GET['id'] ?? 0);

$o = mysqli_execute_query($conn, "SELECT o.*, t.total, c.fname, c.lname, c.addressline, c.town, c.zipcode, c.phone, u.email
    FROM orderinfo o JOIN ordertotals t ON t.orderinfo_id = o.orderinfo_id
    JOIN customer c ON c.customer_id = o.customer_id JOIN users u ON u.user_id = c.user_id
    WHERE o.orderinfo_id = ?", [$id])->fetch_assoc();
if (!$o) {
    $_SESSION['message'] = 'Transaction not found';
    header("Location: transactions.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $o['status'];
    $new = $_POST['status'] ?? '';
    if (!in_array($new, $nextStatus[$old], true)) {
        $_SESSION['message'] = 'That status change is not allowed';
        header("Location: transaction_view.php?id=$id");
        exit();
    }

    // build the email first, so the inbox copy can be saved together with the status change
    $o['status'] = $new;
    $emailLines = mysqli_execute_query($conn, "SELECT item_name AS name, quantity, price FROM order_details WHERE orderinfo_id = ?", [$id])->fetch_all(MYSQLI_ASSOC);
    $body    = buildOrderEmail($o, $emailLines, $label);
    $subject = 'Petkho order #' . str_pad($id, 6, '0', STR_PAD_LEFT) . ' - ' . $label[$new];

    // status + stock + inbox message: all succeed or none of them
    mysqli_begin_transaction($conn);
    try {
        // "AND status = old" makes sure two people cannot change the same order at the same time
        mysqli_execute_query($conn, "UPDATE orderinfo SET status = ? WHERE orderinfo_id = ? AND status = ?", [$new, $id, $old]);
        if (mysqli_affected_rows($conn) !== 1) {
            throw new Exception('This order was just changed by someone else. Please check it again.');
        }
        if ($new === 'Canceled') {
            // cancelled -> the items go back to stock
            mysqli_execute_query($conn, "UPDATE stock s JOIN orderline ol ON ol.item_id = s.item_id
                SET s.quantity = s.quantity + ol.quantity WHERE ol.orderinfo_id = ?", [$id]);
        }
        mysqli_execute_query($conn, "INSERT INTO inbox (customer_id, orderinfo_id, subject, body) VALUES (?, ?, ?, ?)",
            [$o['customer_id'], $id, $subject, $body]);
        mysqli_commit($conn);
    } catch (Throwable $ex) {
        mysqli_rollback($conn);
        $_SESSION['message'] = $ex->getMessage();
        header("Location: transaction_view.php?id=$id");
        exit();
    }

    // the email goes out after the commit (an email cannot be rolled back)
    $pdfData = buildReceiptPdf($o, $emailLines, $label);
    $attachments = [['content' => $pdfData, 'name' => 'receipt-' . str_pad($id, 6, '0', STR_PAD_LEFT) . '.pdf']];
    $sent = sendMail($o['email'], $o['fname'] . ' ' . $o['lname'], $subject, $body, $attachments);
    if ($sent === true) {
        $_SESSION['success'] = 'Order status updated!';
    } else {
        $_SESSION['message'] = 'Order status updated, but the email could not be sent: ' . $sent;
    }
    header("Location: transaction_view.php?id=$id");
    exit();
}

$lines = mysqli_execute_query($conn, "SELECT item_name AS name, quantity, price FROM order_details WHERE orderinfo_id = ?", [$id]);

$page = 'transactions';
include("../includes/adminHeader.php");
?>
<h2 class="page-title">Order #<?php echo str_pad($o['orderinfo_id'], 6, '0', STR_PAD_LEFT); ?></h2>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="panel">
      <table class="table table-sm align-middle">
        <thead><tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr></thead>
        <?php foreach ($lines as $l): ?>
          <tr>
            <td><?php echo htmlspecialchars($l['name']); ?></td>
            <td class="text-end"><?php echo $l['quantity']; ?></td>
            <td class="text-end">&#8369;<?php echo number_format($l['price'], 2); ?></td>
            <td class="text-end">&#8369;<?php echo number_format($l['price'] * $l['quantity'], 2); ?></td>
          </tr>
        <?php endforeach; ?>
        <tr><td colspan="3" class="text-end">Shipping</td><td class="text-end">&#8369;<?php echo number_format($o['shipping'], 2); ?></td></tr>
        <tr><td colspan="3" class="text-end fw-bold">Total</td><td class="text-end fw-bold">&#8369;<?php echo number_format($o['total'], 2); ?></td></tr>
      </table>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="panel mb-3">
      <h5 class="section-title">Customer</h5>
      <div><?php echo htmlspecialchars($o['fname'] . ' ' . $o['lname']); ?></div>
      <div class="text-muted small"><?php echo htmlspecialchars($o['email']); ?></div>
      <div><?php echo htmlspecialchars($o['addressline'] . ', ' . $o['town'] . ' ' . $o['zipcode']); ?></div>
      <div><?php echo htmlspecialchars($o['phone']); ?></div>
      <div class="mt-2 small text-muted">Placed <?php echo date('F j, Y g:i A', strtotime($o['date_placed'])); ?> &middot; Cash on Delivery</div>
    </div>

    <div class="panel">
      <h5 class="section-title">Status</h5>
      <p class="mb-2">Current status: <span class="badge text-bg-<?php echo $color[$o['status']]; ?>"><?php echo $label[$o['status']]; ?></span></p>
      <?php if (!$nextStatus[$o['status']]): ?>
        <p class="mb-0 text-muted"><?php echo $o['status'] === 'Canceled' ? 'Order cancelled!' : 'This order is delivered. It cannot be changed anymore.'; ?></p>
      <?php else: ?>
        <form method="POST" class="d-flex gap-2">
          <select class="form-select" name="status">
            <?php foreach ($nextStatus[$o['status']] as $s): ?>
              <option value="<?php echo $s; ?>"><?php echo $label[$s]; ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-maroon" onclick="return confirm('Update the order status?');">Update</button>
        </form>
        <p class="text-muted small mt-2 mb-0">Orders only move forward. Choosing Cancelled returns the items to stock.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
<a class="d-inline-block mt-3" href="transactions.php">&larr; Back to transactions</a>
<?php include("../includes/adminFooter.php"); ?>