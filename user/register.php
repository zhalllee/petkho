<?php
session_start();
include("../includes/config.php");

// values the user typed before (saved by store.php when there was an error)
$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);

// grey placeholder picture shown until the user chooses a photo
$placeholder = 'data:image/svg+xml;utf8,' . rawurlencode("<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='#e9ecef'/><circle cx='50' cy='38' r='18' fill='#adb5bd'/><path d='M14 100 Q14 64 50 64 Q86 64 86 100Z' fill='#adb5bd'/></svg>");

$rows = [
    ['fname' => 'First name', 'lname' => 'Last name'],
    ['email' => 'Email address', 'phone' => 'Phone number'],
    ['password' => 'Password', 'confirm' => 'Confirm password'],
    ['address' => 'Address'],
    ['town' => 'Town / City', 'zipcode' => 'Zip code'],
];

include("../includes/header.php");
?>
<div class="account-page pk-register">
  <h3 class="mb-4">Create your account</h3>
  <form action="store.php" method="POST" enctype="multipart/form-data">
    <div class="row g-4">

      <!-- LEFT: account details -->
      <div class="col-lg-8">
        <?php foreach ($rows as $row): ?>
          <div class="row gx-3 mb-3">
            <?php foreach ($row as $name => $label):
              $type = in_array($name, ['password', 'confirm']) ? 'password' : ($name === 'email' ? 'email' : 'text'); ?>
              <div class="<?php echo count($row) === 1 ? 'col-12' : 'col-md-6'; ?>">
                <label class="small mb-1" for="<?php echo $name; ?>"><?php echo $label; ?></label>
                <input class="form-control" id="<?php echo $name; ?>" type="<?php echo $type; ?>" name="<?php echo $name; ?>"
                       placeholder="Enter your <?php echo strtolower($label); ?>"
                       value="<?php echo $type === 'password' ? '' : htmlspecialchars($old[$name] ?? ''); ?>" required>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
        <div class="d-flex justify-content-between align-items-center">
          <span class="small">Already a member? <a href="<?php echo BASE; ?>/user/login.php">Login</a></span>
          <button type="submit" class="btn btn-primary px-4" name="submit">Create account</button>
        </div>
      </div>

      <div class="col-lg-4 pk-reg-photo text-center order-first order-lg-last">
        <img id="preview" class="img-account-profile rounded-circle mb-3" src="<?php echo $placeholder; ?>" alt="Profile photo preview">
        <div class="small text-muted mb-3">JPG or PNG, no larger than 2 MB (Optional).</div>
        <input type="file" class="form-control" id="photo" name="photo">
      </div>

    </div>
  </form>
</div>

<script>
document.getElementById('photo').addEventListener('change', function () {
  if (this.files[0]) {
    document.getElementById('preview').src = URL.createObjectURL(this.files[0]);
  }
});
</script>
</body>
</html>
