<?php
session_start();
include("./includes/config.php");
include("./includes/badwords.php");

$id = (int)($_GET['id'] ?? 0);
$item = mysqli_execute_query($conn, "SELECT i.*, c.name AS category, s.quantity
    FROM item i JOIN category c USING (category_id) JOIN stock s USING (item_id)
    WHERE i.item_id = ? AND i.status = 'active'", [$id])->fetch_assoc();

include("./includes/header.php");

if (!$item) {
    echo '<p class="text-danger">Product not found.</p><a class="btn btn-outline-secondary" href="' . BASE . '/index.php">Back to shop</a>';
    include("./includes/footer.php");
    exit();
}

$stats = mysqli_execute_query($conn, "SELECT COUNT(*) AS n, AVG(rating) AS avg_rating FROM review WHERE item_id = ?", [$id])->fetch_assoc();
$avg = (int)round($stats['avg_rating'] ?? 0);

$reviews = mysqli_execute_query($conn, "SELECT r.*, CONCAT(c.fname, ' ', c.lname) AS customer
    FROM review r LEFT JOIN customer c ON c.customer_id = r.customer_id
    WHERE r.item_id = ? ORDER BY r.review_date DESC", [$id])->fetch_all(MYSQLI_ASSOC);

foreach ($reviews as &$rv) {
    $rv['photos'] = mysqli_execute_query($conn, "SELECT photo_path FROM review_photo WHERE review_id = ? ORDER BY review_photo_id",
        [$rv['review_id']])->fetch_all(MYSQLI_ASSOC);
}
unset($rv);

$gallery = mysqli_execute_query($conn, "SELECT photo_path FROM item_photo WHERE item_id = ? ORDER BY item_photo_id", [$id])->fetch_all(MYSQLI_ASSOC);

$canReview = null;
if (!empty($_SESSION['user_id'])) {
    $canReview = mysqli_execute_query($conn, "SELECT o.orderinfo_id FROM orderinfo o JOIN customer c USING (customer_id) JOIN orderline ol USING (orderinfo_id)
        WHERE c.user_id = ? AND ol.item_id = ? AND o.status = 'Delivered' LIMIT 1", [$_SESSION['user_id'], $id])->fetch_assoc();
}
?>
<a href="<?php echo BASE; ?>/index.php" class="btn btn-sm btn-outline-secondary mb-3">&larr; Back to shop</a>

<div class="row g-4">
  <div class="col-md-5">
    <?php if ($item['img_path']): ?>
      <img id="pkMainPhoto" src="<?php echo BASE; ?>/images/<?php echo htmlspecialchars($item['img_path']); ?>" class="img-fluid rounded">
    <?php else: ?>
      <div class="bg-light text-center text-muted py-5 rounded">No image</div>
    <?php endif; ?>
    <?php if ($gallery): ?>
      <div class="d-flex flex-wrap gap-2 mt-2">
        <?php
        // main image counts as the first gallery photo so thumbnails can "next" through all of them
        $allPhotos = [];
        if ($item['img_path']) $allPhotos[] = $item['img_path'];
        foreach ($gallery as $g) $allPhotos[] = $g['photo_path'];
        ?>
        <?php foreach ($allPhotos as $idx => $photo): ?>
          <img src="<?php echo BASE; ?>/images/<?php echo htmlspecialchars($photo); ?>"
               class="pk-gallery-thumb<?php echo $idx === 0 ? ' active' : ''; ?>"
               style="width:80px;height:80px;object-fit:cover;border-radius:.25rem;cursor:pointer"
               alt="product photo"
               data-full="<?php echo BASE; ?>/images/<?php echo htmlspecialchars($photo); ?>">
        <?php endforeach; ?>
      </div>
      <script>
        (function () {
          var mainPhoto = document.getElementById('pkMainPhoto');
          var thumbs = document.querySelectorAll('.pk-gallery-thumb');
          thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function () {
              if (!mainPhoto) return;
              mainPhoto.src = thumb.getAttribute('data-full');
              thumbs.forEach(function (t) { t.classList.remove('active'); });
              thumb.classList.add('active');
            });
          });
        })();
      </script>
    <?php endif; ?>
  </div>
  <div class="col-md-7">
    <span class="badge text-bg-secondary"><?php echo htmlspecialchars($item['category']); ?></span>
    <h2 class="mt-2"><?php echo htmlspecialchars($item['name']); ?></h2>
    <?php if (!empty($item['brand'])): ?><p class="text-muted mb-1">Brand: <?php echo htmlspecialchars($item['brand']); ?></p><?php endif; ?>
    <p class="text-warning fs-5">
      <?php echo str_repeat('&#9733;', $avg) . str_repeat('&#9734;', 5 - $avg); ?>
      <span class="text-muted small">(<?php echo $stats['n']; ?> review<?php echo $stats['n'] == 1 ? '' : 's'; ?>)</span>
    </p>
    <p class="fs-4 fw-bold">&#8369;<?php echo number_format($item['sell_price'], 2); ?></p>
    <p><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
    <p class="text-muted">Stock: <?php echo (int)$item['quantity']; ?></p>

    <?php if ($item['quantity'] > 0): ?>
      <form method="POST" action="<?php echo BASE; ?>/cart_update.php" class="d-flex gap-2">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
        <input type="number" name="qty" value="1" min="1" max="<?php echo $item['quantity']; ?>" class="form-control" style="width:90px">
        <button class="btn btn-success">Add to Cart</button>
      </form>
    <?php else: ?>
      <span class="text-danger">Out of stock</span>
    <?php endif; ?>
  </div>
</div>

<hr class="my-4">

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Customer Reviews</h4>
  <?php if ($canReview): ?>
    <a class="btn btn-sm btn-primary" href="<?php echo BASE; ?>/user/review.php?order=<?php echo $canReview['orderinfo_id']; ?>&item=<?php echo $id; ?>">Write a review</a>
  <?php elseif (empty($_SESSION['user_id'])): ?>
    <span class="text-muted small">Log in and buy this item to write a review.</span>
  <?php endif; ?>
</div>

<?php if (!$reviews): ?>
  <p class="text-muted">No reviews yet. Be the first to review this product!</p>
<?php endif; ?>

<?php foreach ($reviews as $rv): ?>
  <div class="border-bottom pb-3 mb-3">
    <div class="d-flex justify-content-between">
      <strong><?php echo htmlspecialchars($rv['customer'] ?? 'Anonymous'); ?></strong>
      <span class="text-muted small"><?php echo date('M j, Y', strtotime($rv['review_date'])); ?></span>
    </div>
    <div class="text-warning"><?php echo str_repeat('&#9733;', $rv['rating']) . str_repeat('&#9734;', 5 - $rv['rating']); ?></div>
    <?php if (trim($rv['comment'] ?? '') !== ''): ?>
      <p class="mb-2"><?php echo nl2br(htmlspecialchars(mask_bad_words($rv['comment']))); ?></p>
    <?php endif; ?>
    <?php if ($rv['photos']): ?>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($rv['photos'] as $p): ?>
          <a href="<?php echo BASE; ?>/images/reviews/<?php echo htmlspecialchars($p['photo_path']); ?>" target="_blank">
            <img src="<?php echo BASE; ?>/images/reviews/<?php echo htmlspecialchars($p['photo_path']); ?>" style="width:90px;height:90px;object-fit:cover;border-radius:.25rem" alt="review photo">
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<?php include("./includes/footer.php"); ?>