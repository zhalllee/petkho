<?php
session_start();
include("../includes/config.php");
include("../includes/adminAuth.php");

$LOW = 10;  

// ---------- inventory ----------
$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'low', 'out'], true)) {
    $filter = 'all';
}

$all = mysqli_query($conn, "SELECT i.item_id, i.name, i.brand, c.name AS category, i.sell_price, COALESCE(s.quantity, 0) AS qty, sp.name AS supplier
    FROM item i
    JOIN category c ON c.category_id = i.category_id
    LEFT JOIN stock s ON s.item_id = i.item_id
    LEFT JOIN supplier sp ON sp.supplier_id = i.supplier_id
    WHERE i.status = 'active'
    ORDER BY qty ASC, i.item_id ASC")->fetch_all(MYSQLI_ASSOC);

$cnt = ['all' => 0, 'ok' => 0, 'low' => 0, 'out' => 0];
$rows = [];
foreach ($all as $r) {
    if ($r['qty'] <= 0) {
        $k = 'out'; $r['status'] = 'Out of Stock'; $r['cls'] = 'table-danger';
    } elseif ($r['qty'] <= $LOW) {
        $k = 'low'; $r['status'] = 'Low Stock'; $r['cls'] = 'table-warning';
    } else {
        $k = 'ok'; $r['status'] = 'Available'; $r['cls'] = 'table-success';
    }
    $cnt[$k]++;
    $cnt['all']++;
    if ($filter === 'all' || $filter === $k) {
        $rows[] = $r;
    }
}

// ---------- date range ----------
function validDate($d)
{
    $x = DateTime::createFromFormat('Y-m-d', (string)$d);
    return $x && $x->format('Y-m-d') === $d;
}
$from = validDate($_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-01');
$to   = validDate($_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d');
if ($from > $to) {
    [$from, $to] = [$to, $from];
}

// ---------- income overview (cancelled orders are not counted) ----------
$s = mysqli_execute_query($conn, "SELECT COALESCE(SUM(t.total), 0) AS total, COALESCE(SUM(o.shipping), 0) AS shipping, COUNT(*) AS orders
    FROM orderinfo o JOIN ordertotals t USING (orderinfo_id)
    WHERE o.status <> 'Canceled' AND o.date_placed >= ? AND o.date_placed < DATE_ADD(?, INTERVAL 1 DAY)", [$from, $to])->fetch_assoc();
$sales = (float)$s['total'];

$expenses = (float)mysqli_execute_query($conn, "SELECT COALESCE(SUM(amount), 0) FROM expense WHERE expense_date BETWEEN ? AND ?", [$from, $to])->fetch_row()[0];
$net = $sales - $expenses;

// ---------- sales report: what sold in the range ----------
$report = mysqli_execute_query($conn, "SELECT i.name, SUM(ol.quantity) AS qty, SUM(ol.quantity * ol.price) AS amount
    FROM orderline ol JOIN orderinfo o ON o.orderinfo_id = ol.orderinfo_id JOIN item i ON i.item_id = ol.item_id
    WHERE o.status <> 'Canceled' AND o.date_placed >= ? AND o.date_placed < DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY i.item_id, i.name ORDER BY amount DESC", [$from, $to])->fetch_all(MYSQLI_ASSOC);

$page = 'dashboard';
include("../includes/adminHeader.php");
$keep = "from=" . urlencode($from) . "&to=" . urlencode($to);
?>

<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
  <h2 class="page-title mb-0">Inventory Monitoring</h2>
  <div>
    <a class="btn <?php echo $filter === 'low' ? 'btn-maroon' : 'btn-outline-maroon'; ?>" href="?filter=low&<?php echo $keep; ?>">Low Stock</a>
    <a class="btn <?php echo $filter === 'out' ? 'btn-maroon' : 'btn-outline-maroon'; ?>" href="?filter=out&<?php echo $keep; ?>">Out of Stock</a>
    <a class="btn <?php echo $filter === 'all' ? 'btn-maroon' : 'btn-outline-maroon'; ?>" href="?filter=all&<?php echo $keep; ?>">All</a>
  </div>
</div>

<div class="panel scroll-table p-0">
  <table class="table table-bordered mb-0 align-middle">
    <thead>
      <tr><th>Product ID</th><th>Product Name</th><th>Brand</th><th>Category</th><th class="text-end">Price (&#8369;)</th><th class="text-center">Stock</th><th>Supplier</th><th>Status</th></tr>
    </thead>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo $r['item_id']; ?></td>
        <td><?php echo htmlspecialchars($r['name']); ?></td>
        <td><?php echo htmlspecialchars($r['brand'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($r['category']); ?></td>
        <td class="text-end"><?php echo number_format($r['sell_price'], 2); ?></td>
        <td class="text-center"><?php echo $r['qty']; ?></td>
        <td><?php echo htmlspecialchars($r['supplier'] ?? ''); ?></td>
        <td class="<?php echo $r['cls']; ?>"><?php echo $r['status']; ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
      <tr><td colspan="8" class="text-center text-muted py-3">Nothing to show.</td></tr>
    <?php endif; ?>
  </table>
</div>
<p class="text-center mt-2 mb-4 fw-semibold">
  Total: <?php echo $cnt['all']; ?> &nbsp;&nbsp;
  Available: <?php echo $cnt['ok']; ?> &nbsp;&nbsp;
  Low Stock: <?php echo $cnt['low']; ?> &nbsp;&nbsp;
  Out of Stock: <?php echo $cnt['out']; ?>
</p>

<h4 class="section-title">Income Overview</h4>
<form method="GET" class="d-flex align-items-center gap-2 flex-wrap mb-3">
  <input type="hidden" name="filter" value="<?php echo $filter; ?>">
  <label class="text-muted fst-italic">Select date range:</label>
  <input type="date" class="form-control" style="width:auto" name="from" value="<?php echo $from; ?>">
  <span>-</span>
  <input type="date" class="form-control" style="width:auto" name="to" value="<?php echo $to; ?>">
  <button class="btn btn-maroon">Show</button>
</form>

<div class="row text-center fw-bold fs-5 mb-4 g-2">
  <div class="col-md-4"><div class="panel">Total Sales: &#8369;<?php echo number_format($sales, 2); ?></div></div>
  <div class="col-md-4"><div class="panel">Total Expenses: &#8369;<?php echo number_format($expenses, 2); ?></div></div>
  <div class="col-md-4"><div class="panel <?php echo $net < 0 ? 'text-danger' : ''; ?>">Net Income: &#8369;<?php echo number_format($net, 2); ?></div></div>
</div>

<h4 class="section-title">Sales Report</h4>
<p class="text-muted small">
  <?php echo date('M j, Y', strtotime($from)); ?> &ndash; <?php echo date('M j, Y', strtotime($to)); ?>
  &middot; <?php echo $s['orders']; ?> order(s) &middot; cancelled orders are not counted
</p>
<div class="panel">
  <table class="table table-striped mb-0 align-middle">
    <thead><tr><th>Product</th><th class="text-end">Units Sold</th><th class="text-end">Sales (&#8369;)</th></tr></thead>
    <?php $units = 0; $items = 0; foreach ($report as $r): $units += $r['qty']; $items += $r['amount']; ?>
      <tr>
        <td><?php echo htmlspecialchars($r['name']); ?></td>
        <td class="text-end"><?php echo $r['qty']; ?></td>
        <td class="text-end"><?php echo number_format($r['amount'], 2); ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$report): ?>
      <tr><td colspan="3" class="text-center text-muted py-3">No sales in this date range.</td></tr>
    <?php else: ?>
      <tr class="table-light sum-start"><td class="fw-semibold">Products total</td><td class="text-end fw-semibold"><?php echo $units; ?></td><td class="text-end fw-semibold"><?php echo number_format($items, 2); ?></td></tr>
      <tr class="table-light"><td colspan="2">Shipping collected</td><td class="text-end"><?php echo number_format($s['shipping'], 2); ?></td></tr>
      <tr class="table-light"><td colspan="2" class="fw-bold">Total Sales</td><td class="text-end fw-bold"><?php echo number_format($sales, 2); ?></td></tr>
    <?php endif; ?>
  </table>
</div>

<?php include("../includes/adminFooter.php"); ?>
