</div>

<footer class="pk-footer mt-auto">
  <div class="container">

    <div class="pk-footer-info py-4 mt-4">
      <div class="row g-4">
        <div class="col-12 col-sm-6 col-lg-3 d-flex gap-3 align-items-center">
          <span class="pk-ico"><i class="bi bi-chat-dots"></i></span>
          <div><h6>Have Questions?</h6><p>We are happy to help your furry friend.</p></div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3 d-flex gap-3 align-items-center">
          <span class="pk-ico"><i class="bi bi-geo-alt"></i></span>
          <div><h6>Locate Us</h6><p>123 Pet Street, Taguig City, Metro Manila</p></div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3 d-flex gap-3 align-items-center">
          <span class="pk-ico"><i class="bi bi-telephone"></i></span>
          <div><h6>Call Us Today</h6><p>(+63) 917 123 4567</p></div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3 d-flex gap-3 align-items-center">
          <span class="pk-ico"><i class="bi bi-envelope"></i></span>
          <div><h6>Get In Our Inbox</h6><p>hello@petkho.com</p></div>
        </div>
      </div>
    </div>

    <div class="row g-4 py-4">

      <div class="col-12 col-md-4">
        <img class="pk-footer-logo mb-2" src="<?php echo $logoUrl; ?>" alt="Petkho">
        <h5 class="mb-2">Petkho</h5>
        <p class="small mb-0">
          Your one-stop pet shop for food, treats, toys, grooming
          essentials, cages and beds. Everything your furry friend needs, delivered to your door.
        </p>
      </div>

      <div class="col-6 col-md-2 offset-md-1">
        <h6>Shop</h6>
        <ul class="list-unstyled small mb-0">
          <li><a href="<?php echo BASE; ?>/index.php">All Products</a></li>
          <li><a href="<?php echo BASE; ?>/cart.php">My Cart</a></li>
          <?php if (empty($_SESSION['user_id'])): ?>
            <li><a href="<?php echo BASE; ?>/user/login.php">Login</a></li>
            <li><a href="<?php echo BASE; ?>/user/register.php">Register</a></li>
          <?php else: ?>
            <li><a href="<?php echo BASE; ?>/user/orders.php">My Orders</a></li>
            <li><a href="<?php echo BASE; ?>/user/profile.php">My Profile</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="col-6 col-md-2">
        <h6>Store Hours</h6>
        <ul class="list-unstyled small mb-0">
          <li>9:00 AM &ndash; 6:00 PM</li>
          <li>Monday &ndash; Saturday</li>
        </ul>
      </div>

      <div class="col-12 col-md-3">
        <h6>Contact Us</h6>
        <ul class="list-unstyled small mb-0">
          <li><i class="bi bi-telephone-fill me-2"></i>(+63) 917 123 4567</li>
          <li><i class="bi bi-envelope-fill me-2"></i>hello@petkho.com</li>
          <li><i class="bi bi-geo-alt-fill me-2"></i>123 Pet Street, Taguig City, Metro Manila</li>
        </ul>
      </div>

    </div>

    <div class="pk-copy d-flex flex-column flex-md-row justify-content-between py-3">
      <span>&copy; <?php echo date('Y'); ?> Petkho. All rights reserved.</span>
      <span>JOSE, ZAMORA | BSIT -2A</span>
    </div>
  </div>
</footer>
</body>
</html>
