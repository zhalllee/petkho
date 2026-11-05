<?php
session_start();
include("../includes/config.php");
if (empty($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please log in first';
    header("Location: login.php");
    exit();
}
include("../includes/header.php");
$id = (int)($_GET['id'] ?? 0);

// only the owner can see the receipt
$o = mysqli_execute_query($conn, "SELECT o.*, t.total, c.fname, c.lname, c.addressline, c.town, c.zipcode, c.phone
    FROM orderinfo o JOIN ordertotals t USING (orderinfo_id) JOIN customer c USING (customer_id)
    WHERE o.orderinfo_id = ? AND c.user_id = ?", [$id, $_SESSION['user_id']])->fetch_assoc();
if (!$o) {
    echo "<p>Receipt not found.</p>";
    include("../includes/footer.php");
    exit;
}
$lines = mysqli_execute_query($conn, "SELECT i.name, ol.quantity, ol.price FROM orderline ol JOIN item i USING (item_id) WHERE ol.orderinfo_id = ?", [$id]);
?>
<style>
  @media print {
    nav, footer, .no-print, .alert, .bg-dark { display: none !important; }
    .receipt { border: 0 !important; }
  }
</style>

<div class="receipt card mx-auto" style="max-width:640px">
  <div class="card-body p-4">
    <div class="text-center mb-3">
      <h3 class="fw-bold mb-0">Petkho</h3>
      <div class="text-muted small">Official Receipt</div>
    </div>

    <div class="d-flex justify-content-between small mb-3">
      <div>
        <div><strong>Receipt #:</strong> <?php echo str_pad($o['orderinfo_id'], 6, '0', STR_PAD_LEFT); ?></div>
        <div><strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($o['date_placed'])); ?></div>
        <div><strong>Status:</strong> <?php echo htmlspecialchars($o['status']); ?></div>
      </div>
      <div class="text-end">
        <div><strong>Customer</strong></div>
        <div><?php echo htmlspecialchars($o['fname'] . ' ' . $o['lname']); ?></div>
        <div><?php echo htmlspecialchars($o['addressline']); ?></div>
        <div><?php echo htmlspecialchars($o['town'] . ' ' . $o['zipcode']); ?></div>
        <div><?php echo htmlspecialchars($o['phone']); ?></div>
      </div>
    </div>

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
      <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end">&#8369;<?php echo number_format($o['total'] - $o['shipping'], 2); ?></td></tr>
      <tr><td colspan="3" class="text-end">Shipping</td><td class="text-end">&#8369;<?php echo number_format($o['shipping'], 2); ?></td></tr>
      <tr><td colspan="3" class="text-end fw-bold">Total</td><td class="text-end fw-bold">&#8369;<?php echo number_format($o['total'], 2); ?></td></tr>
    </table>

    <p class="text-center text-muted small mb-0">Thank you for shopping at Petkho!</p>
  </div>
</div>

<div class="text-center mt-3 no-print">
  <button class="btn btn-outline-secondary" onclick="window.print()">Print receipt</button>
  <a class="btn btn-primary" href="<?php echo BASE; ?>/index.php">Continue shopping</a>
  <a class="btn btn-link" href="<?php echo BASE; ?>/user/orders.php">My orders</a>
</div>
<?php include("../includes/footer.php"); ?>