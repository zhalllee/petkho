<?php
$page = $page ?? '';
$logoUrl = BASE . '/includes/style/' . (is_file(__DIR__ . '/style/logo.png') ? 'logo.png' : 'logo.svg');
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
// admin sees everything; staff only sees the pages they are allowed to use
$menu = [
    'dashboard'    => ['Dashboard',       'index.php'],
    'customers'    => ['Customers',       'customers.php'],
    'transactions' => ['Transactions',    'transactions.php'],
    'inventory'    => ['Inventory',       'inventory.php'],
    'suppliers'    => ['Suppliers',       'suppliers.php'],
    'expenses'     => ['Expenses',        'expenses.php'],
    'products'     => ['Products',        'products.php'],
    'reviews'      => ['Product Reviews', 'reviews.php'],
    'staff'        => ['Staff Accounts',  'staff.php'],
];
if (!$isAdmin) {
    $menu = array_intersect_key($menu, array_flip(['transactions', 'inventory']));
}
$panelName = $isAdmin ? 'Petkho Admin' : 'Petkho Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $panelName; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link href="<?php echo BASE; ?>/includes/style/style.css" rel="stylesheet" type="text/css">
  <link href="<?php echo BASE; ?>/includes/style/admin.css" rel="stylesheet" type="text/css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="d-flex flex-column flex-md-row min-vh-100">

  <nav class="admin-side">
    <div class="brand"><img src="<?php echo $logoUrl; ?>" alt=""><?php echo $panelName; ?></div>
    <div class="who"><?php echo htmlspecialchars($_SESSION['email']); ?></div>
    <?php foreach ($menu as $key => $m): ?>
      <a class="<?php echo $page === $key ? 'active' : ''; ?>" href="<?php echo BASE; ?>/admin/<?php echo $m[1]; ?>"><?php echo $m[0]; ?></a>
    <?php endforeach; ?>
    <hr class="mx-3 border-light opacity-50">
    <a href="<?php echo BASE; ?>/index.php">View Shop</a>
    <a href="<?php echo BASE; ?>/user/logout.php" onclick="return confirm('Are you sure you want to log out?');">Logout</a>
  </nav>

  <main class="flex-grow-1 p-4" style="min-width:0">
<?php include(__DIR__ . "/alert.php"); ?>
