<?php
session_start();
include("../includes/config.php");
if (empty($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please log in first';
    header("Location: login.php");
    exit();
}

$tabs = [
    'all'       => ['All',        null],
    'ship'      => ['To Ship',    'Processing'],
    'receive'   => ['To Receive', 'Shipped'],
    'review'    => ['To Review',  'Delivered'],
    'cancelled' => ['Cancelled',  'Canceled'],
];
$tab = $_GET['tab'] ?? 'all';
if (!isset($tabs[$tab])) {
    $tab = 'all';
}

$count = [];
$rows = mysqli_execute_query($conn, "SELECT o.status, COUNT(*) AS n FROM orderinfo o JOIN customer c USING (customer_id)
    WHERE c.user_id = ? GROUP BY o.status", [$_SESSION['user_id']]);
foreach ($rows as $r) {
    $count[$r['status']] = $r['n'];
}

$sql = "SELECT o.orderinfo_id, o.date_placed, o.status, t.total
        FROM orderinfo o JOIN ordertotals t USING (orderinfo_id) JOIN customer c USING (customer_id)
        WHERE c.user_id = ?";
$params = [$_SESSION['user_id']];
if ($tabs[$tab][1] !== null) {
    $sql .= " AND o.status = ?";
    $params[] = $tabs[$tab][1];
}
$sql .= " ORDER BY o.orderinfo_id DESC";
$orders = mysqli_execute_query($conn, $sql, $params);

$label = ['Processing' => 'To Ship', 'Shipped' => 'To Receive', 'Delivered' => 'To Review', 'Canceled' => 'Cancelled'];
$color = ['Processing' => 'warning', 'Shipped' => 'info', 'Delivered' => 'success', 'Canceled' => 'secondary'];

include("../includes/header.php");
?>
<h3>My Orders</h3>

<ul class="nav nav-tabs mb-3">
  <?php foreach ($tabs as $key => $t):
    $n = $t[1] === null ? array_sum($count) : ($count[$t[1]] ?? 0); ?>
    <li class="nav-item">
      <a class="nav-link <?php echo $tab === $key ? 'active' : ''; ?>" href="<?php echo BASE; ?>/user/orders.php?tab=<?php echo $key; ?>">
        <?php echo $t[0]; ?>
        <?php if ($n > 0): ?><span class="badge rounded-pill text-bg-secondary"><?php echo $n; ?></span><?php endif; ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<?php $shown = 0; foreach ($orders as $o): $shown++;
  $lines = mysqli_execute_query($conn, "SELECT i.item_id, i.name, ol.quantity, ol.price,
                                        (SELECT r.rating FROM review r JOIN customer c ON c.customer_id = r.customer_id
                                         WHERE r.item_id = i.item_id AND c.user_id = ? LIMIT 1) AS my_rating
                                        FROM orderline ol JOIN item i USING (item_id)
                                         WHERE ol.orderinfo_id = ?", [$_SESSION['user_id'], $o['orderinfo_id']])->fetch_all(MYSQLI_ASSOC); ?>
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>
        <strong>Order #<?php echo str_pad($o['orderinfo_id'], 6, '0', STR_PAD_LEFT); ?></strong>
        <span class="text-muted small ms-2"><?php echo date('M j, Y g:i A', strtotime($o['date_placed'])); ?></span>
      </span>
      <span class="badge text-bg-<?php echo $color[$o['status']]; ?>"><?php echo $label[$o['status']]; ?></span>
    </div>
    <div class="card-body">
      <?php foreach ($lines as $l): ?>
        <div class="d-flex justify-content-between small">
          <span><?php echo htmlspecialchars($l['name']); ?> <span class="text-muted">x<?php echo $l['quantity']; ?></span></span>
          <span>&#8369;<?php echo number_format($l['price'] * $l['quantity'], 2); ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <span>Total: <strong>&#8369;<?php echo number_format($o['total'], 2); ?></strong></span>
      <span>
        <?php if ($o['status'] === 'Delivered'): foreach ($lines as $l): ?>
          <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE; ?>/user/review.php?order=<?php echo $o['orderinfo_id']; ?>&item=<?php echo $l['item_id']; ?>">
            <?php echo $l['my_rating'] ? 'Edit review' : 'Write a review'; ?><?php echo count($lines) > 1 ? ': ' . htmlspecialchars($l['name']) : ''; ?>
          </a>
        <?php endforeach; endif; ?>        
        <a class="btn btn-sm btn-outline-secondary" href="<?php echo BASE; ?>/user/receipt.php?id=<?php echo $o['orderinfo_id']; ?>">Receipt</a>
        <?php if ($o['status'] === 'Shipped'): ?>
          <form method="POST" action="<?php echo BASE; ?>/user/order_received.php" class="d-inline">
            <input type="hidden" name="id" value="<?php echo $o['orderinfo_id']; ?>">
            <button class="btn btn-sm btn-primary" onclick="return confirm('Confirm that you received this order?');">Order Received</button>
          </form>
        <?php endif; ?>
      </span>
    </div>
  </div>
<?php endforeach; ?>

<?php if (!$shown): ?>
  <p class="text-muted">No orders here yet.</p>
<?php endif; ?>
<?php include("../includes/footer.php"); ?>