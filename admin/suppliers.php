<?php
session_start();
include("../includes/config.php");
include("../includes/adminAuth.php");

if (isset($_POST['delete_id'])) {
    mysqli_execute_query($conn, "DELETE FROM supplier WHERE supplier_id = ?", [(int)$_POST['delete_id']]);
    $_SESSION['success'] = 'Supplier deleted';
    header("Location: suppliers.php?q=" . urlencode($_GET['q'] ?? ''));
    exit();
}

$q = trim($_GET['q'] ?? '');
$like = "%$q%";
$rows = mysqli_execute_query($conn, "SELECT s.*, (SELECT COUNT(*) FROM item i WHERE i.supplier_id = s.supplier_id) AS products
    FROM supplier s
    WHERE s.name LIKE ? OR s.contact_person LIKE ? OR s.phone LIKE ? OR s.email LIKE ? OR s.address LIKE ?
    ORDER BY s.name", [$like, $like, $like, $like, $like]);

$page = 'suppliers';
include("../includes/adminHeader.php");
?>
<h2 class="page-title">Suppliers</h2>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
<form method="GET" class="d-flex gap-2 flex-grow-1" style="max-width:480px">
  <input type="search" class="form-control" name="q" placeholder="Search name, contact, phone, email..." value="<?php echo htmlspecialchars($q); ?>">
  <button class="btn btn-maroon">Search</button>
  <?php if ($q !== ''): ?><a class="btn btn-outline-secondary" href="suppliers.php">Clear</a><?php endif; ?>
</form>
  <a class="btn btn-maroon" href="supplier_form.php">+ Add Supplier</a>
</div>

<div class="panel table-responsive">
  <table class="table table-striped align-middle mb-0">
    <thead><tr><th>ID</th><th>Supplier</th><th>Contact Person</th><th>Phone</th><th>Email</th><th>Address</th><th class="text-center">Products</th><th></th></tr></thead>
    <?php $n = 0; foreach ($rows as $r): $n++; ?>
      <tr>
        <td><?php echo $r['supplier_id']; ?></td>
        <td><?php echo htmlspecialchars($r['name']); ?></td>
        <td><?php echo htmlspecialchars($r['contact_person'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($r['phone'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($r['email'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($r['address'] ?? ''); ?></td>
        <td class="text-center"><?php echo $r['products']; ?></td>
        <td class="text-nowrap">
          <a class="btn btn-sm btn-outline-secondary" href="supplier_form.php?id=<?php echo $r['supplier_id']; ?>">Edit</a>
          <form method="POST" class="d-inline" onsubmit="return confirm('Delete this supplier? Its products will just have no supplier.');">
            <input type="hidden" name="delete_id" value="<?php echo $r['supplier_id']; ?>">
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$n): ?><tr><td colspan="8" class="text-center text-muted py-3">No suppliers found.</td></tr><?php endif; ?>
  </table>
</div>
<?php include("../includes/adminFooter.php"); ?>
