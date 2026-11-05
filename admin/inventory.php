<?php
// Inventory page for staff AND admin: see stock levels and update the quantity of a product.
session_start();
include("../includes/config.php");
include("../includes/staffAuth.php");

$LOW = 10;   // same "low stock" limit as the admin dashboard

$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'low', 'out'], true)) {
    $filter = 'all';
}
$q = trim($_GET['q'] ?? '');

// ---------- update stock ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $qty    = $_POST['qty'] ?? '';
    $back   = "inventory.php?filter=" . urlencode($filter) . "&q=" . urlencode($q);

    $exists = mysqli_execute_query($conn, "SELECT name FROM item WHERE item_id = ?", [$itemId])->fetch_assoc();
    if (!$exists) {
        $_SESSION['message'] = 'Product not found';
    } elseif (!is_string($qty) || !ctype_digit($qty) || (int)$qty > 100000) {
        $_SESSION['message'] = 'Stock must be a whole number from 0 to 100000';
    } else {
        mysqli_execute_query($conn, "INSERT INTO stock (item_id, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)",
            [$itemId, (int)$qty]);
        $_SESSION['success'] = 'Stock of ' . $exists['name'] . ' is now ' . (int)$qty;
    }
    header("Location: $back");
    exit();
}

// ---------- list ----------
$like = "%$q%";
$all = mysqli_execute_query($conn, "SELECT i.item_id, i.name, i.brand, i.status, c.name AS category, COALESCE(s.quantity, 0) AS qty
    FROM item i
    JOIN category c ON c.category_id = i.category_id
    LEFT JOIN stock s ON s.item_id = i.item_id
    WHERE i.name LIKE ? OR i.brand LIKE ? OR c.name LIKE ?
    ORDER BY qty ASC, i.name ASC", [$like, $like, $like])->fetch_all(MYSQLI_ASSOC);

$cnt = ['all' => 0, 'low' => 0, 'out' => 0];
$rows = [];
foreach ($all as $r) {
    if ($r['qty'] <= 0) {
        $k = 'out'; $r['label'] = 'Out of Stock'; $r['cls'] = 'table-danger';
    } elseif ($r['qty'] <= $LOW) {
        $k = 'low'; $r['label'] = 'Low Stock'; $r['cls'] = 'table-warning';
    } else {
        $k = 'ok'; $r['label'] = 'Available'; $r['cls'] = 'table-success';
    }
    $cnt['all']++;
    if (isset($cnt[$k])) {
        $cnt[$k]++;
    }
    if ($filter === 'all' || $filter === $k) {
        $rows[] = $r;
    }
}

$page = 'inventory';
include("../includes/adminHeader.php");
$keep = "q=" . urlencode($q);
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h2 class="page-title mb-0">Inventory</h2>
  <div>
    <a class="btn <?php echo $filter === 'low' ? 'btn-maroon' : 'btn-outline-maroon'; ?>" href="?filter=low&<?php echo $keep; ?>">Low Stock (<?php echo $cnt['low']; ?>)</a>
    <a class="btn <?php echo $filter === 'out' ? 'btn-maroon' : 'btn-outline-maroon'; ?>" href="?filter=out&<?php echo $keep; ?>">Out of Stock (<?php echo $cnt['out']; ?>)</a>
    <a class="btn <?php echo $filter === 'all' ? 'btn-maroon' : 'btn-outline-maroon'; ?>" href="?filter=all&<?php echo $keep; ?>">All (<?php echo $cnt['all']; ?>)</a>
  </div>
</div>

<form method="GET" class="d-flex gap-2 mb-3" style="max-width:480px">
  <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
  <input type="search" class="form-control" name="q" placeholder="Search product, brand, category..." value="<?php echo htmlspecialchars($q); ?>">
  <button class="btn btn-maroon">Search</button>
  <?php if ($q !== ''): ?><a class="btn btn-outline-secondary" href="inventory.php?filter=<?php echo urlencode($filter); ?>">Clear</a><?php endif; ?>
</form>

<div class="panel table-responsive p-0">
  <table class="table table-bordered mb-0 align-middle">
    <thead><tr><th>ID</th><th>Product</th><th>Category</th><th>Product Status</th><th>Stock Status</th><th class="text-center">Stock</th><th style="width:220px">Update stock</th></tr></thead>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo $r['item_id']; ?></td>
        <td><?php echo htmlspecialchars($r['name']); ?><div class="small text-muted"><?php echo htmlspecialchars($r['brand'] ?? ''); ?></div></td>
        <td><?php echo htmlspecialchars($r['category']); ?></td>
        <td><span class="badge text-bg-<?php echo $r['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo $r['status']; ?></span></td>
        <td class="<?php echo $r['cls']; ?>"><?php echo $r['label']; ?></td>
        <td class="text-center fw-bold"><?php echo $r['qty']; ?></td>
        <td>
          <form method="POST" class="d-flex gap-2">
            <input type="hidden" name="item_id" value="<?php echo $r['item_id']; ?>">
            <input type="number" class="form-control form-control-sm" name="qty" min="0" max="100000" value="<?php echo $r['qty']; ?>" required>
            <button class="btn btn-sm btn-maroon">Save</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-3">Nothing to show.</td></tr><?php endif; ?>
  </table>
</div>
<p class="text-muted small mt-2">Type the new total quantity on the shelf, then press Save. Orders and cancellations also change the stock automatically.</p>
<?php include("../includes/adminFooter.php"); ?>
