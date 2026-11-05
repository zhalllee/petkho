<?php $cartCount = array_sum($_SESSION['cart'] ?? []);
$unread = 0;
if (!empty($_SESSION['user_id']) && isset($conn)) {
    $unread = (int)mysqli_execute_query($conn, "SELECT COUNT(*) AS n FROM inbox i JOIN customer c USING (customer_id) WHERE c.user_id = ? AND i.is_read = 0", [$_SESSION['user_id']])->fetch_assoc()['n'];
}
$here = basename($_SERVER['SCRIPT_NAME']);
$logoUrl = BASE . '/includes/style/' . (is_file(__DIR__ . '/style/logo.png') ? 'logo.png' : 'logo.svg'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Petkho</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link href="<?php echo BASE; ?>/includes/style/style.css" rel="stylesheet" type="text/css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">

<div class="pk-topbar">
  <div class="container d-flex justify-content-between align-items-center gap-3">
    <span>
      <?php if (!empty($_SESSION['user_id'])): ?>
        Welcome back, <strong><?php echo htmlspecialchars($_SESSION['email']); ?></strong>
      <?php else: ?>
        Welcome to Petkho Pet Supplies!
      <?php endif; ?>
    </span>
    <span class="d-none d-md-inline">Flat shipping fee: &#8369;50.00</span>
    <span class="d-none d-lg-inline">
      <i class="bi bi-telephone-fill"></i>(+63) 917 123 4567
      <span class="mx-2">|</span>
      <i class="bi bi-envelope-fill"></i>hello@petkho.com
    </span>
  </div>
</div>

<nav class="navbar navbar-expand-lg pk-nav">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo BASE; ?>/index.php">
      <img src="<?php echo $logoUrl; ?>" alt="">
      <span>Petkho</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav mx-auto align-items-lg-center">
        <li class="nav-item"><a class="nav-link <?php echo $here === 'index.php' ? 'active' : ''; ?>" href="<?php echo BASE; ?>/index.php">Shop</a></li>
        <?php if (empty($_SESSION['user_id'])): ?>
          <li class="nav-item"><a class="nav-link <?php echo $here === 'login.php' ? 'active' : ''; ?>" href="<?php echo BASE; ?>/user/login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link <?php echo $here === 'register.php' ? 'active' : ''; ?>" href="<?php echo BASE; ?>/user/register.php">Register</a></li>
        <?php else: ?>
          <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE; ?>/admin/index.php">Admin Panel</a></li>
          <?php elseif (($_SESSION['role'] ?? '') === 'staff'): ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE; ?>/admin/transactions.php">Staff Panel</a></li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link <?php echo $here === 'orders.php' ? 'active' : ''; ?>" href="<?php echo BASE; ?>/user/orders.php">My Orders</a></li>
          <li class="nav-item"><a class="nav-link <?php echo $here === 'inbox.php' ? 'active' : ''; ?>" href="<?php echo BASE; ?>/user/inbox.php">Inbox<?php if ($unread): ?> <span class="badge rounded-pill text-bg-danger"><?php echo $unread; ?></span><?php endif; ?></a></li>
          <li class="nav-item"><a class="nav-link <?php echo $here === 'profile.php' ? 'active' : ''; ?>" href="<?php echo BASE; ?>/user/profile.php">Profile</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE; ?>/user/logout.php" onclick="return confirm('Are you sure you want to log out?');">Logout</a></li>
        <?php endif; ?>
      </ul>
      <a class="btn btn-primary pk-cart-btn" href="<?php echo BASE; ?>/cart.php"><i class="bi bi-cart3 me-1"></i>Cart (<?php echo $cartCount; ?>)</a>
    </div>
  </div>
</nav>
<div class="container my-4 flex-grow-1">
<?php include(__DIR__ . "/alert.php"); ?>
