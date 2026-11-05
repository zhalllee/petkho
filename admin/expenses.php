<?php
session_start();
include("../includes/config.php");
include("../includes/adminAuth.php");

if (isset($_POST['delete_id'])) {
    mysqli_execute_query($conn, "DELETE FROM expense WHERE expense_id = ?", [(int)$_POST['delete_id']]);
    $_SESSION['success'] = 'Expense deleted';
    header("Location: expenses.php?q=" . urlencode($_GET['q'] ?? ''));
    exit();
}

$q = trim($_GET['q'] ?? '');
$like = "%$q%";
$rows = mysqli_execute_query($conn, "SELECT e.*, s.name AS supplier FROM expense e LEFT JOIN supplier s ON s.supplier_id = e.supplier_id
    WHERE e.description LIKE ? OR s.name LIKE ? OR e.expense_date LIKE ?
    ORDER BY e.expense_date DESC, e.expense_id DESC", [$like, $like, $like])->fetch_all(MYSQLI_ASSOC);
$total = array_sum(array_column($rows, 'amount'));

$page = 'expenses';
include("../includes/adminHeader.php");
?>
<h2 class="page-title">Expenses</h2>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
<form method="GET" class="d-flex gap-2 flex-grow-1" style="max-width:480px">
  <input type="search" class="form-control" name="q" placeholder="Search description, supplier, date (2026-07)..." value="<?php echo htmlspecialchars($q); ?>">
  <button class="btn btn-maroon">Search</button>
  <?php if ($q !== ''): ?><a class="btn btn-outline-secondary" href="expenses.php">Clear</a><?php endif; ?>
</form>
  <a class="btn btn-maroon" href="expense_form.php">+ Add Expense</a>
</div>

<div class="panel table-responsive">
  <table class="table table-striped align-middle mb-0">
    <thead><tr><th>ID</th><th>Date</th><th>Description</th><th>Supplier</th><th class="text-end">Amount</th><th></th></tr></thead>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo $r['expense_id']; ?></td>
        <td><?php echo date('M j, Y', strtotime($r['expense_date'])); ?></td>
        <td><?php echo htmlspecialchars($r['description']); ?></td>
        <td><?php echo htmlspecialchars($r['supplier'] ?? '-'); ?></td>
        <td class="text-end">&#8369;<?php echo number_format($r['amount'], 2); ?></td>
        <td class="text-nowrap">
          <a class="btn btn-sm btn-outline-secondary" href="expense_form.php?id=<?php echo $r['expense_id']; ?>">Edit</a>
          <form method="POST" class="d-inline" onsubmit="return confirm('Delete this expense?');">
            <input type="hidden" name="delete_id" value="<?php echo $r['expense_id']; ?>">
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
      <tr><td colspan="6" class="text-center text-muted py-3">No expenses found.</td></tr>
    <?php else: ?>
      <tr class="table-light"><td colspan="4" class="text-end fw-bold">Total</td><td class="text-end fw-bold">&#8369;<?php echo number_format($total, 2); ?></td><td></td></tr>
    <?php endif; ?>
  </table>
</div>
<?php include("../includes/adminFooter.php"); ?>
