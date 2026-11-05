<?php
session_start();
include("./includes/config.php");

$act = $_POST['action'] ?? '';

if ($act === 'add') {
    $id = (int)$_POST['item_id'];
    $qty = max(1, (int)$_POST['qty']);

    $item = mysqli_execute_query(
        $conn,
        "SELECT i.name, s.quantity FROM item i JOIN stock s USING (item_id) WHERE i.item_id = ? AND i.status = 'active'",
        [$id]
    )->fetch_assoc();

    if (!$item) {
        $_SESSION['message'] = 'Item not found';
    } elseif (($_SESSION['cart'][$id] ?? 0) + $qty > $item['quantity']) {
        $_SESSION['message'] = 'Not enough stock for ' . $item['name'];
    } else {
        $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $qty;
        $_SESSION['success'] = 'Added to cart';
    }
    header("Location: index.php");
    exit();
}

if ($act === 'update') {
    foreach ($_POST['qty'] ?? [] as $id => $q) {
        $id = (int)$id;
        $q  = (int)$q;
        if (!isset($_SESSION['cart'][$id])) {
            continue;                                   // only items that are already in the cart
        }
        $row = mysqli_execute_query($conn, "SELECT s.quantity FROM stock s JOIN item i USING (item_id)
                                            WHERE s.item_id = ? AND i.status = 'active'", [$id])->fetch_assoc();
        $max = (int)($row['quantity'] ?? 0);
        if ($q < 1) {
            unset($_SESSION['cart'][$id]);
        } elseif ($max < 1) {
            unset($_SESSION['cart'][$id]);
            $_SESSION['message'] = 'An item in your cart is no longer available and was removed';
        } elseif ($q > $max) {
            $_SESSION['cart'][$id] = $max;
            $_SESSION['message'] = 'Quantity was lowered to the available stock';
        } else {
            $_SESSION['cart'][$id] = $q;
        }
    }

    $sel = [];
    foreach ($_POST['sel'] ?? [] as $id) {
        if (isset($_SESSION['cart'][(int)$id])) {
            $sel[] = (int)$id;
        }
    }

    if (!$sel) {
        $_SESSION['message'] = 'Please select at least one item first';
        header("Location: cart.php");
        exit();
    }

    if (($_POST['do'] ?? '') === 'remove') {
        foreach ($sel as $id) {
            unset($_SESSION['cart'][$id]);
        }
        $_SESSION['success'] = 'Selected items removed';
        header("Location: cart.php");
        exit();
    }

    if (($_POST['do'] ?? '') === 'checkout') {
        $_SESSION['checkout'] = $sel;
        header("Location: payment.php");
        exit();
    }
}

header("Location: cart.php");
exit();