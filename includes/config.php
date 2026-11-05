<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
define('BASE', '/petkho');          
$conn = mysqli_connect('localhost', 'root', '', 'petkho', 3306);

// Keep the session in sync with the database on every page.
// If an admin changes someone's role or sets the account to inactive, it takes effect right away.
if (!empty($_SESSION['user_id'])) {
    $sessionUser = mysqli_execute_query($conn, "SELECT email, role, status FROM users WHERE user_id = ?", [$_SESSION['user_id']])->fetch_assoc();
    if (!$sessionUser || $sessionUser['status'] !== 'active') {
        unset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['role'], $_SESSION['cart'], $_SESSION['checkout']);
        $_SESSION['message'] = 'Your account is no longer active. Please contact the store.';
    } else {
        $_SESSION['email'] = $sessionUser['email'];
        $_SESSION['role']  = $sessionUser['role'];
    }
}

