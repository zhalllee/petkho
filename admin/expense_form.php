<?php
session_start();
include("../includes/config.php");
include("../includes/adminAuth.php");

$id = (int)($_GET['id'] ?? 0);
$e = ['expense_date' => date('Y-m-d'), 'supplier_id' => '', 'description' => '', 'amount' => ''];
if ($id) {
    $e = mysqli_execute_query($conn, "SELECT * FROM expense WHERE expense_id = ?", [$id])->fetch_assoc();
    if (!$e) {
        $_SESSION['message'] = 'Expense not found';
        header("Location: expenses.php");
        exit();
    }
}
$suppliers = mysqli_query($conn, "SELECT supplier_id, name FROM supplier ORDER BY name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = array_map('trim', $_POST);
    $e = array_merge($e, $f);
    $d = DateTime::createFromFormat('Y-m-d', $f['expense_date']);
    if ($f['description'] === '') {
        $_SESSION['message'] = 'Description is required';
    } elseif (!is_numeric($f['amount']) || $f['amount'] <= 0) {
        $_SESSION['message'] = 'Amount must be more than 0';
    } elseif (!$d || $d->format('Y-m-d') !== $f['expense_date']) {
        $_SESSION['message'] = 'Please enter a valid date';
    } else {
        $sup = $f['supplier_id'] === '' ? null : (int)$f['supplier_id'];
        $vals = [$sup, $f['description'], $f['amount'], $f['expense_date']];
        if ($id) {
            mysqli_execute_query($conn, "UPDATE expense SET supplier_id = ?, description = ?, amount = ?, expense_date = ? WHERE expense_id = ?", [...$vals, $id]);
            $_SESSION['success'] = 'Expense updated';
        } else {
            mysqli_execute_query($conn, "INSERT INTO expense (supplier_id, description, amount, expense_date) VALUES (?, ?, ?, ?)", $vals);
            $_SESSION['success'] = 'Expense added';
        }
        header("Location: expenses.php");
        exit();
    }
}

$page = 'expenses';
include("../includes/adminHeader.php");
?>
<h2 class="page-title"><?php echo $id ? 'Edit Expense' : 'Add Expense'; ?></h2>
<div class="panel" style="max-width:640px">
  <form method="POST">
    <div class="row">
      <div class="col-md-6 mb-3"><label class="form-label">Date</label><input type="date" class="form-control" name="expense_date" value="<?php echo htmlspecialchars($e['expense_date']); ?>" required></div>
      <div class="col-md-6 mb-3"><label class="form-label">Amount (&#8369;)</label><input type="number" step="0.01" min="0.01" class="form-control" name="amount" value="<?php echo htmlspecialchars($e['amount']); ?>" required></div>
    </div>
    <div class="mb-3"><label class="form-label">Description</label><input class="form-control" name="description" placeholder="e.g. Restock - dog food" value="<?php echo htmlspecialchars($e['description']); ?>" required></div>
    <div class="mb-3">
      <label class="form-label">Supplier (optional)</label>
      <select class="form-select" name="supplier_id">
        <option value="">- none -</option>
        <?php foreach ($suppliers as $s): ?>
          <option value="<?php echo $s['supplier_id']; ?>" <?php echo (string)$e['supplier_id'] === (string)$s['supplier_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-maroon"><?php echo $id ? 'Save changes' : 'Add expense'; ?></button>
    <a class="btn btn-outline-secondary" href="expenses.php">Cancel</a>
  </form>
</div>
<?php include("../includes/adminFooter.php"); ?>
