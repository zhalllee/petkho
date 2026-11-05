<?php
session_start();
include("../includes/config.php");
include("../includes/staffAuth.php");

$statuses = ['Processing', 'Shipped', 'Delivered', 'Canceled'];
$q = trim($_GET['q'] ?? '');
$st = $_GET['status'] ?? '';
$like = "%" . ltrim($q, '#') . "%";    

$sql = "SELECT o.orderinfo_id, o.date_placed, o.status, t.total, c.fname, c.lname, u.email,
            (SELECT SUM(quantity) FROM orderline ol WHERE ol.orderinfo_id = o.orderinfo_id) AS items
        FROM orderinfo o
        JOIN ordertotals t ON t.orderinfo_id = o.orderinfo_id
        JOIN customer c ON c.customer_id = o.customer_id
        JOIN users u ON u.user_id = c.user_id
        WHERE (LPAD(o.orderinfo_id, 6, '0') LIKE ? OR CONCAT(c.fname, ' ', c.lname) LIKE ? OR u.email LIKE ?";
$params = [$like, $like, $like];
if (str_contains($q, '-')) {         
    $sql .= " OR DATE(o.date_placed) LIKE ?";
    $params[] = $like;
}
$sql .= ")";
if (in_array($st, $statuses, true)) {
    $sql .= " AND o.status = ?";
    $params[] = $st;
}
$sql .= " ORDER BY o.orderinfo_id DESC";
$rows = mysqli_execute_query($conn, $sql, $params);

$label = ['Processing' => 'To Ship', 'Shipped' => 'To Receive', 'Delivered' => 'Delivered', 'Canceled' => 'Cancelled'];
$color = ['Processing' => 'warning', 'Shipped' => 'info', 'Delivered' => 'success', 'Canceled' => 'secondary'];

$page = 'transactions';
include("../includes/adminHeader.php");
?>
<h2 class="page-title">Transactions</h2>

<form method="GET" class="d-flex gap-2 mb-3 flex-wrap" style="max-width:640px">
  <input type="search" class="form-control" style="flex:1 1 220px" name="q" placeholder="Search order #, customer, email, date (2026-07-14)..." value="<?php echo htmlspecialchars($q); ?>">
  <select class="form-select" style="width:auto" name="status">
    <option value="">All statuses</option>
    <?php foreach ($statuses as $s): ?>
      <option value="<?php echo $s; ?>" <?php echo $st === $s ? 'selected' : ''; ?>><?php echo $label[$s]; ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-maroon">Search</button>
  <?php if ($q !== '' || $st !== ''): ?><a class="btn btn-outline-secondary" href="transactions.php">Clear</a><?php endif; ?>
</form>

<div class="panel table-responsive">
  <table class="table table-striped align-middle mb-0">
    <thead><tr><th>Order #</th><th>Date</th><th>Customer</th><th class="text-center">Items</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
    <?php $n = 0; foreach ($rows as $r): $n++; ?>
      <tr>
        <td>#<?php echo str_pad($r['orderinfo_id'], 6, '0', STR_PAD_LEFT); ?></td>
        <td><?php echo date('M j, Y g:i A', strtotime($r['date_placed'])); ?></td>
        <td><?php echo htmlspecialchars($r['fname'] . ' ' . $r['lname']); ?><div class="small text-muted"><?php echo htmlspecialchars($r['email']); ?></div></td>
        <td class="text-center"><?php echo $r['items']; ?></td>
        <td class="text-end">&#8369;<?php echo number_format($r['total'], 2); ?></td>
        <td><span class="badge text-bg-<?php echo $color[$r['status']]; ?>"><?php echo $label[$r['status']]; ?></span></td>
        <td><a class="btn btn-sm btn-outline-secondary" href="transaction_view.php?id=<?php echo $r['orderinfo_id']; ?>">View</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$n): ?><tr><td colspan="7" class="text-center text-muted py-3">No transactions found.</td></tr><?php endif; ?>
  </table>
</div>
<?php include("../includes/adminFooter.php"); ?>
