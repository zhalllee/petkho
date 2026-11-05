<?php
session_start();
include("./includes/config.php");
include("./includes/header.php");
$cart = $_SESSION['cart'] ?? [];
$total = 0;
?>
<h3>Your Cart</h3>
<?php if (!$cart): ?>
  <p>Your cart is empty. <a href="<?php echo BASE; ?>/index.php">Go shopping</a></p>
<?php else: ?>
<form method="POST" action="<?php echo BASE; ?>/cart_update.php">
  <input type="hidden" name="action" value="update">
  <table class="table align-middle">
    <thead>
      <tr>
        <th style="width:40px"><input type="checkbox" class="form-check-input" id="selAll" title="Select all"></th>
        <th>Item</th>
        <th>Price</th>
        <th style="width:100px">Qty</th>
        <th>Subtotal</th>
      </tr>
    </thead>
    <?php foreach ($cart as $id => $qty):
      $it = mysqli_execute_query($conn, "SELECT i.name, i.sell_price, s.quantity FROM item i JOIN stock s USING (item_id)
                                         WHERE i.item_id = ? AND i.status = 'active'", [$id])->fetch_assoc();
      if (!$it) { unset($_SESSION['cart'][$id]); continue; }
      $sub = $it['sell_price'] * $qty;
      $total += $sub; ?>
      <tr>
        <td><input type="checkbox" class="form-check-input rowchk" name="sel[]" value="<?php echo $id; ?>"></td>
        <td><?php echo htmlspecialchars($it['name']); ?></td>
        <td>&#8369;<?php echo number_format($it['sell_price'], 2); ?></td>
        <td><input type="number" class="form-control form-control-sm" name="qty[<?php echo $id; ?>]" value="<?php echo $qty; ?>" min="1" max="<?php echo $it['quantity']; ?>"></td>
        <td>&#8369;<?php echo number_format($sub, 2); ?></td>
      </tr>
    <?php endforeach; ?>
    <tr>
      <td colspan="4" class="text-end fw-bold">Cart total</td>
      <td class="fw-bold">&#8369;<?php echo number_format($total, 2); ?></td>
    </tr>
  </table>

  <button class="btn btn-outline-danger" name="do" value="remove" onclick="return confirm('Remove the selected items from your cart?');">Remove</button>
  <button class="btn btn-primary" name="do" value="checkout">Checkout</button>
</form>

<script>
  // select all / unselect all
  document.getElementById('selAll').addEventListener('change', function () {
    document.querySelectorAll('.rowchk').forEach(function (c) { c.checked = document.getElementById('selAll').checked; });
  });
</script>
<?php endif; ?>
