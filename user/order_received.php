<?php
// Called by the "Order Received" button in orders.php. Moves the customer's own Shipped order to Delivered.
session_start();
include("../includes/config.php");
if (empty($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please log in first';
    header("Location: login.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mysqli_execute_query($conn, "UPDATE orderinfo o JOIN customer c USING (customer_id)
        SET o.status = 'Delivered' WHERE o.orderinfo_id = ? AND c.user_id = ? AND o.status = 'Shipped'",
        [(int)($_POST['id'] ?? 0), $_SESSION['user_id']]);
    $_SESSION['success'] = 'Thanks! You can now review the products in this order.';
}
header("Location: orders.php?tab=review");
exit();
