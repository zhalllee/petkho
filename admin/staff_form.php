<?php
// Admin only: add a staff/admin account, or edit its role, status and password.
session_start();
include("../includes/config.php");
include("../includes/adminAuth.php");

$id = (int)($_GET['id'] ?? 0);
$u = ['email' => '', 'role' => 'staff', 'status' => 'active'];
if ($id) {
    $u = mysqli_execute_query($conn, "SELECT user_id, email, role, status FROM users WHERE user_id = ? AND role IN ('admin', 'staff')", [$id])->fetch_assoc();
    if (!$u) {
        $_SESSION['message'] = 'Staff account not found';
        header("Location: staff.php");
        exit();
    }
}
$isSelf = $id && $id == $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $role     = in_array($_POST['role'] ?? '', ['staff', 'admin'], true) ? $_POST['role'] : 'staff';
    $status   = ($_POST['status'] ?? '') === 'inactive' ? 'inactive' : 'active';
    if ($isSelf) {                       // you cannot lock yourself out
        $role = 'admin';
        $status = 'active';
    }
    $u = ['email' => $email, 'role' => $role, 'status' => $status] + $u;

    if (!$id && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = 'Email invalid format';
    } elseif (($id === 0 || $password !== '') && strlen($password) < 6) {
        $_SESSION['message'] = 'Password should be at least 6 characters';
    } else {
        try {
            if ($id) {
                if ($password !== '') {
                    mysqli_execute_query($conn, "UPDATE users SET role = ?, status = ?, password = ? WHERE user_id = ?",
                        [$role, $status, password_hash($password, PASSWORD_BCRYPT), $id]);
                } else {
                    mysqli_execute_query($conn, "UPDATE users SET role = ?, status = ? WHERE user_id = ?", [$role, $status, $id]);
                }
                $_SESSION['success'] = 'Account updated';
            } else {
                mysqli_execute_query($conn, "INSERT INTO users (email, password, role, status) VALUES (?, ?, ?, ?)",
                    [$email, password_hash($password, PASSWORD_BCRYPT), $role, $status]);
                $_SESSION['success'] = 'Staff account added';
            }
            header("Location: staff.php");
            exit();
        } catch (mysqli_sql_exception $e) {
            $_SESSION['message'] = $e->getCode() == 1062 ? 'That email is already registered' : 'Could not save, try again';
        }
    }
}

$page = 'staff';
include("../includes/adminHeader.php");
?>
<h2 class="page-title"><?php echo $id ? 'Edit Staff Account' : 'Add Staff'; ?></h2>
<div class="panel" style="max-width:560px">
  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Email (login)</label>
      <input class="form-control" name="email" type="email" value="<?php echo htmlspecialchars($u['email']); ?>" <?php echo $id ? 'disabled' : 'required'; ?>>
    </div>
    <div class="mb-3">
      <label class="form-label"><?php echo $id ? 'New password (leave empty to keep the current one)' : 'Password'; ?></label>
      <input class="form-control" type="password" name="password" autocomplete="new-password" <?php echo $id ? '' : 'required'; ?>>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Role</label>
        <select class="form-select" name="role" <?php echo $isSelf ? 'disabled' : ''; ?>>
          <?php foreach (['staff', 'admin'] as $rl): ?>
            <option value="<?php echo $rl; ?>" <?php echo $u['role'] === $rl ? 'selected' : ''; ?>><?php echo ucfirst($rl); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Account status</label>
        <select class="form-select" name="status" <?php echo $isSelf ? 'disabled' : ''; ?>>
          <option value="active" <?php echo $u['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
          <option value="inactive" <?php echo $u['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
        </select>
      </div>
    </div>
    <?php if ($isSelf): ?><p class="text-muted small">This is your own account, so its role and status are locked.</p><?php endif; ?>
    <button class="btn btn-maroon"><?php echo $id ? 'Save changes' : 'Add staff'; ?></button>
    <a class="btn btn-outline-secondary" href="staff.php">Cancel</a>
  </form>
</div>
<?php include("../includes/adminFooter.php"); ?>
