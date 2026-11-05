<?php
session_start();
include("./includes/config.php");

if (empty($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please log in first';
    header("Location: user/login.php");
    exit();
}

$c = mysqli_execute_query($conn, "SELECT * FROM customer WHERE user_id = ?", [$_SESSION['user_id']])->fetch_assoc();
if (!$c) {
    $_SESSION['message'] = 'Complete your profile first';
    header("Location: user/profile.php");
    exit();
}

// the items ticked in the cart
$cart = $_SESSION['cart'] ?? [];
$items = [];
foreach ($_SESSION['checkout'] ?? [] as $id) {
    if (isset($cart[$id])) {
        $items[$id] = $cart[$id];
    }
}
if (!$items) {
    $_SESSION['message'] = 'Please select the items you want to checkout';
    header("Location: cart.php");
    exit();
}

$shipping = 50;

// ---------- place order pressed ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mysqli_begin_transaction($conn);
    try {
        mysqli_execute_query($conn, "INSERT INTO orderinfo (customer_id, date_placed, shipping, status) VALUES (?, NOW(), ?, 'Processing')", [$c['customer_id'], $shipping]);
        $oid = mysqli_insert_id($conn);
        foreach ($items as $id => $qty) {
            $it = mysqli_execute_query($conn, "SELECT i.name, i.sell_price, s.quantity FROM item i JOIN stock s USING (item_id)
                                               WHERE i.item_id = ? AND i.status = 'active' FOR UPDATE", [$id])->fetch_assoc();
            if (!$it || $it['quantity'] < $qty) {
                throw new Exception('Not enough stock for ' . ($it['name'] ?? 'an item'));
            }
            mysqli_execute_query($conn, "INSERT INTO orderline (orderinfo_id, item_id, quantity, price) VALUES (?, ?, ?, ?)", [$oid, $id, $qty, $it['sell_price']]);
            mysqli_execute_query($conn, "UPDATE stock SET quantity = quantity - ? WHERE item_id = ?", [$qty, $id]);
        }
        mysqli_commit($conn);

        // only the ordered items leave the cart
        foreach ($items as $id => $qty) {
            unset($_SESSION['cart'][$id]);
        }
        unset($_SESSION['checkout']);
        $_SESSION['success'] = 'Order placed! Thank you for your purchase.';
        header("Location: user/receipt.php?id=$oid");
        exit();
    } catch (Throwable $ex) {
        mysqli_rollback($conn);
        $_SESSION['message'] = $ex->getMessage();
        header("Location: cart.php");
        exit();
    }
}

// ---------- show the summary ----------
$lines = [];
$subtotal = 0;
foreach ($items as $id => $qty) {
    $it = mysqli_execute_query($conn, "SELECT name, sell_price FROM item WHERE item_id = ? AND status = 'active'", [$id])->fetch_assoc();
    if (!$it) {
        unset($_SESSION['cart'][$id]);
        continue;
    }
    $lines[] = ['name' => $it['name'], 'qty' => $qty, 'price' => $it['sell_price']];
    $subtotal += $it['sell_price'] * $qty;
}
if (!$lines) {
    header("Location: cart.php");
    exit();
}
$grand = $subtotal + $shipping;

include("./includes/header.php");
?>
<h3>Payment</h3>
<div class="row g-4">

  <div class="col-12 col-lg-7">
    <h5>Order summary</h5>
    <table class="table align-middle">
      <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
      <?php foreach ($lines as $l): ?>
        <tr>
          <td><?php echo htmlspecialchars($l['name']); ?></td>
          <td><?php echo $l['qty']; ?></td>
          <td>&#8369;<?php echo number_format($l['price'], 2); ?></td>
          <td>&#8369;<?php echo number_format($l['price'] * $l['qty'], 2); ?></td>
        </tr>
      <?php endforeach; ?>
      <tr><td colspan="3" class="text-end">Subtotal</td><td>&#8369;<?php echo number_format($subtotal, 2); ?></td></tr>
      <tr><td colspan="3" class="text-end">Shipping</td><td>&#8369;<?php echo number_format($shipping, 2); ?></td></tr>
      <tr><td colspan="3" class="text-end fw-bold">Total</td><td class="fw-bold">&#8369;<?php echo number_format($grand, 2); ?></td></tr>
    </table>
    <a href="<?php echo BASE; ?>/cart.php">&larr; Back to cart</a>
  </div>

  <div class="col-12 col-lg-5">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Ship to</h5>
        <p>
          <?php echo htmlspecialchars($c['fname'] . ' ' . $c['lname']); ?><br>
          <?php echo htmlspecialchars($c['addressline'] . ', ' . $c['town'] . ' ' . $c['zipcode']); ?><br>
          <?php echo htmlspecialchars($c['phone']); ?>
        </p>

        <hr>

        <h5 class="card-title">Payment</h5>
        <p class="mb-1 fw-semibold">Cash on Delivery</p>
        <p class="text-muted small">Pay in cash when your order arrives.</p>

        <form method="POST" action="<?php echo BASE; ?>/payment.php">
          <button class="btn btn-primary w-100">Place order &middot; &#8369;<?php echo number_format($grand, 2); ?></button>
        </form>
      </div>
    </div>
  </div>

</div>
<?php include("./includes/footer.php"); ?>