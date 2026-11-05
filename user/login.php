<?php
session_start();
include("../includes/config.php");

$errors = [];
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($email === '') {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email format is invalid';
    }
    if ($pass === '') {
        $errors['password'] = 'Password is required';
    }

    if (!$errors) {
        $r = mysqli_execute_query($conn, "SELECT user_id, email, password, role FROM users WHERE email = ? AND status = 'active' LIMIT 1",
            [$email])->fetch_assoc();
        if ($r && password_verify($pass, $r['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $r['user_id']; $_SESSION['email'] = $r['email']; $_SESSION['role'] = $r['role'];
            if ($r['role'] === 'admin') {
                header("Location: ../admin/index.php");
            } elseif ($r['role'] === 'staff') {
                header("Location: ../admin/transactions.php");
            } else {
                header("Location: ../index.php");
            }
            exit();
        }
        $_SESSION['message'] = 'Wrong email or password';
    }
}
include("../includes/header.php");
?>
<div class="col-md-5 mx-auto">
  <h3>Login</h3>
  <form method="POST" novalidate>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="text" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" name="email" value="<?php echo htmlspecialchars($email); ?>">
      <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?php echo $errors['email']; ?></div><?php endif; ?>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <div class="input-group has-validation">
        <input type="password" id="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" name="password">
        <button type="button" class="btn btn-outline-secondary" id="togglePassword" aria-label="Show password"><i class="bi bi-eye"></i></button>
        <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?php echo $errors['password']; ?></div><?php endif; ?>
      </div>
    </div>
    <button class="btn btn-primary">Sign in</button>
    <p class="mt-3">Not a member? <a href="<?php echo BASE; ?>/user/register.php">Register</a></p>
  </form>
</div>
<script>
// show / hide the password
document.getElementById('togglePassword').addEventListener('click', function () {
  var box  = document.getElementById('password');
  var show = box.type === 'password';
  box.type = show ? 'text' : 'password';
  this.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
  this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
});
</script>
</body>
</html>
