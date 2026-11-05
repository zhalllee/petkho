<?php
session_start();
include("../includes/config.php");
include("../includes/adminAuth.php");

$id = (int)($_GET['id'] ?? 0);
$c = mysqli_execute_query($conn, "SELECT c.*, u.email, u.status, u.role FROM customer c JOIN users u ON u.user_id = c.user_id WHERE c.customer_id = ? AND u.role = 'customer'", [$id])->fetch_assoc();
if (!$c) {
    $_SESSION['message'] = 'Customer not found';
    header("Location: customers.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // an array sent by mistake becomes '' instead of crashing trim()
    $f = array_map(fn($v) => is_array($v) ? '' : trim($v), $_POST)
       + ['fname' => '', 'lname' => '', 'addressline' => '', 'town' => '', 'zipcode' => '', 'phone' => '', 'status' => ''];
    $status = $f['status'] === 'inactive' ? 'inactive' : 'active';
    if ($f['fname'] === '' || $f['lname'] === '') {
        $_SESSION['message'] = 'First name and last name are required';
        $c = array_merge($c, $f);
    } else {
        mysqli_begin_transaction($conn);
        try {
            mysqli_execute_query($conn, "UPDATE customer SET fname = ?, lname = ?, addressline = ?, town = ?, zipcode = ?, phone = ? WHERE customer_id = ?",
                [$f['fname'], $f['lname'], $f['addressline'], $f['town'], $f['zipcode'], $f['phone'], $id]);
            mysqli_execute_query($conn, "UPDATE users SET status = ? WHERE user_id = ?", [$status, $c['user_id']]);
            mysqli_commit($conn);
            $_SESSION['success'] = 'Customer updated';
            header("Location: customers.php");
            exit();
        } catch (Throwable $ex) {
            mysqli_rollback($conn);
            $_SESSION['message'] = 'Could not save the changes, please try again';
        }
    }
}

$page = 'customers';
include("../includes/adminHeader.php");
?>
<h2 class="page-title">Edit Customer</h2>
<div class="panel" style="max-width:640px">
  <form method="POST">
    <div class="mb-3"><label class="form-label">Email (login)</label><input class="form-control" value="<?php echo htmlspecialchars($c['email']); ?>" disabled></div>
    <div class="row">
      <div class="col-md-6 mb-3"><label class="form-label">First name</label><input class="form-control" name="fname" value="<?php echo htmlspecialchars($c['fname']); ?>" required></div>
      <div class="col-md-6 mb-3"><label class="form-label">Last name</label><input class="form-control" name="lname" value="<?php echo htmlspecialchars($c['lname']); ?>" required></div>
    </div>
    <div class="mb-3"><label class="form-label">Address</label><input class="form-control" name="addressline" value="<?php echo htmlspecialchars($c['addressline']); ?>"></div>
    <div class="row">
      <div class="col-md-6 mb-3"><label class="form-label">Town / City</label><input class="form-control" name="town" value="<?php echo htmlspecialchars($c['town']); ?>"></div>
      <div class="col-md-6 mb-3"><label class="form-label">Zip code</label><input class="form-control" name="zipcode" value="<?php echo htmlspecialchars($c['zipcode']); ?>"></div>
    </div>
    <div class="mb-3"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?php echo htmlspecialchars($c['phone']); ?>"></div>
    <div class="mb-3">
      <label class="form-label">Account status</label>
      <select class="form-select" name="status">
        <option value="active" <?php echo $c['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
        <option value="inactive" <?php echo $c['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
      </select>
    </div>
    <button class="btn btn-maroon">Save changes</button>
    <a class="btn btn-outline-secondary" href="customers.php">Cancel</a>
  </form>
</div>
<?php include("../includes/adminFooter.php"); ?>
