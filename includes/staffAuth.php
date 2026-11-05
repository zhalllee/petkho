<?php
// For pages that BOTH staff and admin may use (transactions, inventory).
// Pages that only the admin may use include adminAuth.php instead.
date_default_timezone_set('Asia/Manila');
if (empty($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please log in to access that page';
    header("Location: " . BASE . "/user/login.php");
    exit();
}
if (!in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    $_SESSION['message'] = 'Access denied: staff and admin accounts only';
    header("Location: " . BASE . "/index.php");
    exit();
}
