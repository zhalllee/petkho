<?php
session_start();
include("../includes/config.php");
include("../includes/adminAuth.php");

if (isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $it = mysqli_execute_query($conn, "SELECT img_path, (SELECT COUNT(*) FROM orderline ol WHERE ol.item_id = item.item_id) AS sold FROM item WHERE item_id = ?", [$id])->fetch_assoc();
    if (!$it) {
        $_SESSION['message'] = 'Product not found';
    } elseif ($it['sold'] > 0) {
        $_SESSION['message'] = 'This product is already in customer orders, so it cannot be deleted. Edit it and set the status to Retired instead.';
    } else {
        $gal = mysqli_execute_query($conn, "SELECT photo_path FROM item_photo WHERE item_id = ?", [$id])->fetch_all(MYSQLI_ASSOC);
        mysqli_begin_transaction($conn);
        mysqli_execute_query($conn, "DELETE FROM stock WHERE item_id = ?", [$id]);
        mysqli_execute_query($conn, "DELETE FROM item WHERE item_id = ?", [$id]);   
        mysqli_commit($conn);
        foreach ($gal as $g) {
            if (is_file(__DIR__ . "/../images/" . $g['photo_path'])) unlink(__DIR__ . "/../images/" . $g['photo_path']);
        }
        if ($it['img_path'] && is_file(__DIR__ . "/../images/" . basename($it['img_path']))) {
            unlink(__DIR__ . "/../images/" . basename($it['img_path']));
        }
        $_SESSION['success'] = 'Product deleted';
    }
    header("Location: products.php?q=" . urlencode($_GET['q'] ?? ''));
    exit();
}

$q = trim($_GET['q'] ?? '');
$like = "%$q%";
$rows = mysqli_execute_query($conn, "SELECT i.*, c.name AS category, sp.name AS supplier, COALESCE(s.quantity, 0) AS qty
    FROM item i
    JOIN category c ON c.category_id = i.category_id
    LEFT JOIN supplier sp ON sp.supplier_id = i.supplier_id
    LEFT JOIN stock s ON s.item_id = i.item_id
    WHERE i.name LIKE ? OR i.brand LIKE ? OR c.name LIKE ? OR sp.name LIKE ? OR i.description LIKE ?
    ORDER BY i.item_id DESC", [$like, $like, $like, $like, $like]);

$page = 'products';
include("../includes/adminHeader.php");
?>
<h2 class="page-title">Products</h2>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
<form method="GET" class="d-flex gap-2 flex-grow-1" style="max-width:480px">
  <input type="search" class="form-control" name="q" placeholder="Search name, brand, category, supplier..." value="<?php echo htmlspecialchars($q); ?>">
  <button class="btn btn-maroon">Search</button>
  <?php if ($q !== ''): ?><a class="btn btn-outline-secondary" href="products.php">Clear</a><?php endif; ?>
</form>
  <a class="btn btn-maroon" href="product_form.php">+ Add Product</a>
</div>

<div class="panel table-responsive">
  <table class="table table-striped align-middle mb-0">
    <thead><tr><th>Image</th><th>ID</th><th>Product</th><th>Brand</th><th>Category</th><th>Supplier</th><th class="text-end">Price</th><th class="text-center">Stock</th><th>Status</th><th></th></tr></thead>
    <?php $n = 0; foreach ($rows as $r): $n++; ?>
      <tr>
        <td>
          <?php if ($r['img_path']): ?>
            <img class="thumb" src="<?php echo BASE; ?>/images/<?php echo htmlspecialchars($r['img_path']); ?>" alt="">
          <?php else: ?>
            <div class="thumb bg-light text-muted small d-flex align-items-center justify-content-center">none</div>
          <?php endif; ?>
        </td>
        <td><?php echo $r['item_id']; ?></td>
        <td><?php echo htmlspecialchars($r['name']); ?></td>
        <td><?php echo htmlspecialchars($r['brand'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($r['category']); ?></td>
        <td><?php echo htmlspecialchars($r['supplier'] ?? ''); ?></td>
        <td class="text-end">&#8369;<?php echo number_format($r['sell_price'], 2); ?></td>
        <td class="text-center"><?php echo $r['qty']; ?></td>
        <td><span class="badge text-bg-<?php echo $r['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo $r['status']; ?></span></td>
        <td class="text-nowrap">
          <a class="btn btn-sm btn-outline-secondary" href="product_form.php?id=<?php echo $r['item_id']; ?>">Edit</a>
          <form method="POST" class="d-inline" onsubmit="return confirm('Delete this product?');">
            <input type="hidden" name="delete_id" value="<?php echo $r['item_id']; ?>">
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$n): ?><tr><td colspan="10" class="text-center text-muted py-3">No products found.</td></tr><?php endif; ?>
  </table>
</div>
<?php include("../includes/adminFooter.php"); ?>
