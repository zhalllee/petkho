<?php
session_start();
include("../includes/config.php");
include("../includes/adminAuth.php");

$id = (int)($_GET['id'] ?? 0);
$s = ['name' => '', 'contact_person' => '', 'phone' => '', 'email' => '', 'address' => ''];
if ($id) {
    $s = mysqli_execute_query($conn, "SELECT * FROM supplier WHERE supplier_id = ?", [$id])->fetch_assoc();
    if (!$s) {
        $_SESSION['message'] = 'Supplier not found';
        header("Location: suppliers.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = array_map('trim', $_POST);
    $s = array_merge($s, $f);
    if ($f['name'] === '') {
        $_SESSION['message'] = 'Supplier name is required';
    } else {
        $vals = [$f['name'], $f['contact_person'], $f['phone'], $f['email'], $f['address']];
        if ($id) {
            mysqli_execute_query($conn, "UPDATE supplier SET name = ?, contact_person = ?, phone = ?, email = ?, address = ? WHERE supplier_id = ?", [...$vals, $id]);
            $_SESSION['success'] = 'Supplier updated';
        } else {
            mysqli_execute_query($conn, "INSERT INTO supplier (name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)", $vals);
            $_SESSION['success'] = 'Supplier added';
        }
        header("Location: suppliers.php");
        exit();
    }
}

$page = 'suppliers';
include("../includes/adminHeader.php");
?>
<h2 class="page-title"><?php echo $id ? 'Edit Supplier' : 'Add Supplier'; ?></h2>
<div class="panel" style="max-width:640px">
  <form method="POST">
    <div class="mb-3"><label class="form-label">Supplier name</label><input class="form-control" name="name" value="<?php echo htmlspecialchars($s['name']); ?>" required></div>
    <div class="mb-3"><label class="form-label">Contact person</label><input class="form-control" name="contact_person" value="<?php echo htmlspecialchars($s['contact_person'] ?? ''); ?>"></div>
    <div class="row">
      <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?php echo htmlspecialchars($s['phone'] ?? ''); ?>"></div>
      <div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($s['email'] ?? ''); ?>"></div>
    </div>
    <div class="mb-3"><label class="form-label">Address</label><input class="form-control" name="address" value="<?php echo htmlspecialchars($s['address'] ?? ''); ?>"></div>
    <button class="btn btn-maroon"><?php echo $id ? 'Save changes' : 'Add supplier'; ?></button>
    <a class="btn btn-outline-secondary" href="suppliers.php">Cancel</a>
  </form>
</div>
<?php include("../includes/adminFooter.php"); ?>
