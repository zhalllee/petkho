<?php
// Handles the register form (register.php sends its data here).
session_start();
include("../includes/config.php");

if (!isset($_POST['submit'])) {        
    header("Location: register.php");
    exit();
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';   // not trimmed, so it matches what login.php checks
$confirm  = $_POST['confirm'] ?? '';
$fname    = trim($_POST['fname'] ?? '');
$lname    = trim($_POST['lname'] ?? '');
$address  = trim($_POST['address'] ?? '');
$town     = trim($_POST['town'] ?? '');
$zipcode  = trim($_POST['zipcode'] ?? '');
$phone    = trim($_POST['phone'] ?? '');

// remember what was typed so register.php can fill the form again if something is wrong
// (we never save the passwords)
$_SESSION['old'] = ['email' => $email, 'fname' => $fname, 'lname' => $lname, 'address' => $address,
                    'town' => $town, 'zipcode' => $zipcode, 'phone' => $phone];

// ---------- validation ----------
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['message'] = 'Email invalid format';
    header("Location: register.php");
    exit();
}
if (strlen($password) < 6) {
    $_SESSION['message'] = 'Password should be at least 6 characters';
    header("Location: register.php");
    exit();
}
if ($password !== $confirm) {
    $_SESSION['message'] = 'Passwords do not match';
    header("Location: register.php");
    exit();
}

// ---------- photo (optional) ----------
$photoPath = null;        
if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['photo'];
    $info = ($file['error'] === UPLOAD_ERR_OK) ? @getimagesize($file['tmp_name']) : false;   

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = 'Photo upload failed (the file may be too big)';
    } elseif ($file['size'] > 2 * 1024 * 1024) {
        $_SESSION['message'] = 'Photo is too big, 2MB only';
    } elseif ($info === false || !in_array($info['mime'], ['image/jpeg', 'image/png'], true)) {
        $_SESSION['message'] = 'Wrong file type, please upload a JPG or PNG';
    }
    if (isset($_SESSION['message'])) {
        header("Location: register.php");
        exit();
    }

    // save the file with a random name so two users can never overwrite each other's photo
    $folder = __DIR__ . '/../images/users/';
    if (!is_dir($folder)) {
        mkdir($folder, 0775, true);
    }
    $ext = $info['mime'] === 'image/png' ? 'png' : 'jpg';
    $photoPath = 'images/users/' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../' . $photoPath)) {
        $_SESSION['message'] = 'Could not save the photo. Check that the images/users folder is writable.';
        header("Location: register.php");
        exit();
    }
}

mysqli_begin_transaction($conn);
try {
    mysqli_execute_query($conn, "INSERT INTO users (email, password, photo) VALUES (?, ?, ?)",
        [$email, password_hash($password, PASSWORD_BCRYPT), $photoPath]);
    $uid = mysqli_insert_id($conn);
    mysqli_execute_query($conn, "INSERT INTO customer (user_id, fname, lname, addressline, town, zipcode, phone) VALUES (?,?,?,?,?,?,?)",
        [$uid, $fname, $lname, $address, $town, $zipcode, $phone]);
    mysqli_commit($conn);

    unset($_SESSION['old']);
    $_SESSION['success'] = 'Account created. Please log in.';
    header("Location: login.php");
    exit();
} catch (mysqli_sql_exception $e) {
    mysqli_rollback($conn);
    if ($photoPath !== null) {                     
        @unlink(__DIR__ . '/../' . $photoPath);
    }
    $_SESSION['message'] = $e->getCode() == 1062 ? 'That email is already registered' : 'Could not register, try again';
    header("Location: register.php");
    exit();
}