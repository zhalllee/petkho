<?php

date_default_timezone_set('Asia/Manila');
if (empty($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please log in to access that page';
    header("Location: " . BASE . "/user/login.php");
    exit();
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['message'] = 'Access denied: admin accounts only';
    header("Location: " . BASE . "/index.php");
    exit();
}
