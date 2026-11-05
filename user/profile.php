<?php
session_start();
include("../includes/config.php");
if (empty($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please log in first';
    header("Location: login.php");
    exit();
}
$fields = ['fname' => 'First name', 'lname' => 'Last name', 'addressline' => 'Address', 'town' => 'Town / City', 'zipcode' => 'Zip code', 'phone' => 'Phone'];

// the photo this user has now (the path saved in users.photo, or null)
$oldPhoto = mysqli_execute_query($conn, "SELECT photo FROM users WHERE user_id = ?", [$_SESSION['user_id']])->fetch_assoc()['photo'] ?? null;

if (isset($_POST['submit'])) {
    $v = array_map(fn($k) => trim($_POST[$k] ?? ''), array_keys($fields));

    // ---------- photo (optional) ----------
    $newPhoto = null;
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
            header("Location: profile.php");
            exit();
        }

        $folder = __DIR__ . '/../images/users/';
        if (!is_dir($folder)) {
            mkdir($folder, 0775, true);
        }
        $ext = $info['mime'] === 'image/png' ? 'png' : 'jpg';
        $newPhoto = 'images/users/' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../' . $newPhoto)) {
            $_SESSION['message'] = 'Could not save the photo. Check that the images/users folder is writable.';
            header("Location: profile.php");
            exit();
        }
    }

    // ---------- save (details and photo together) ----------
    mysqli_begin_transaction($conn);
    try {
        mysqli_execute_query($conn, "UPDATE customer SET fname=?, lname=?, addressline=?, town=?, zipcode=?, phone=? WHERE user_id=?",
            [...$v, $_SESSION['user_id']]);
        if ($newPhoto !== null) {
            mysqli_execute_query($conn, "UPDATE users SET photo = ? WHERE user_id = ?", [$newPhoto, $_SESSION['user_id']]);
        }
        mysqli_commit($conn);
    } catch (Throwable $ex) {
        mysqli_rollback($conn);
        if ($newPhoto !== null) {
            @unlink(__DIR__ . '/../' . $newPhoto);     // the new file is not needed if saving failed
        }
        $_SESSION['message'] = 'Could not save your profile, please try again';
        header("Location: profile.php");
        exit();
    }

    // the old photo file is not used anymore
    if ($newPhoto !== null && $oldPhoto && is_file(__DIR__ . '/../' . $oldPhoto)) {
        unlink(__DIR__ . '/../' . $oldPhoto);
    }
    $_SESSION['success'] = 'Profile updated';
    header("Location: profile.php");
    exit();
}

// grey placeholder picture shown when the user has no photo yet
$placeholder = 'data:image/svg+xml;utf8,' . rawurlencode("<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='#e9ecef'/><circle cx='50' cy='38' r='18' fill='#adb5bd'/><path d='M14 100 Q14 64 50 64 Q86 64 86 100Z' fill='#adb5bd'/></svg>");
$photoSrc = $oldPhoto ? BASE . '/' . htmlspecialchars($oldPhoto) : $placeholder;

$c = mysqli_execute_query($conn, "SELECT * FROM customer WHERE user_id = ?", [$_SESSION['user_id']])->fetch_assoc();
include("../includes/header.php");

$rows = [
    ['fname' => 'First name', 'lname' => 'Last name'],
    ['email' => 'Email address', 'phone' => 'Phone number'],
    ['addressline' => 'Address'],
    ['town' => 'Town / City', 'zipcode' => 'Zip code'],
];
?>

<div class="account-page pk-register">
  <h3 class="mb-4">My Profile</h3>

  <form action="" method="POST" enctype="multipart/form-data">
    <div class="row g-4">

      <!-- LEFT: account details -->
      <div class="col-lg-8">
        <?php foreach ($rows as $row): ?>
          <div class="row gx-3 mb-3">
            <?php foreach ($row as $name => $label): ?>
              <div class="<?php echo count($row) === 1 ? 'col-12' : 'col-md-6'; ?>">
                <label class="small mb-1" for="<?php echo $name; ?>"><?php echo $label; ?></label>
                <?php if ($name === 'email'): ?>
                  <!-- the email is the login, so it is shown but cannot be edited here (no name = not sent) -->
                  <input class="form-control" id="email" type="text" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" disabled>
                <?php else: ?>
                  <input class="form-control" id="<?php echo $name; ?>" type="text" name="<?php echo $name; ?>"
                         placeholder="Enter your <?php echo strtolower($label); ?>"
                         value="<?php echo htmlspecialchars($c[$name] ?? ''); ?>" required>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
        <button class="btn btn-primary px-4" type="submit" name="submit">Save changes</button>
      </div>

      <!-- RIGHT: profile picture -->
      <div class="col-lg-4 pk-reg-photo text-center order-first order-lg-last">
        <img id="preview" class="img-account-profile rounded-circle mb-3" src="<?php echo $photoSrc; ?>" alt="Profile photo">
        <div class="small text-muted mb-3">JPG or PNG, no larger than 2 MB.</div>
        <input type="file" class="form-control" id="photo" name="photo">
      </div>

    </div>
  </form>
</div>

<script>
// show the chosen picture in the circle before saving (only a preview, nothing is uploaded yet)
document.getElementById('photo').addEventListener('change', function () {
  if (this.files[0]) {
    document.getElementById('preview').src = URL.createObjectURL(this.files[0]);
  }
});
</script>
