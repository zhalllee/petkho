<?php
session_start();
include("../includes/config.php");
include("../includes/adminAuth.php");

if (isset($_POST['delete_id'])) {
    $rid = (int)$_POST['delete_id'];

    $photos = mysqli_execute_query($conn, "SELECT photo_path FROM review_photo WHERE review_id = ?", [$rid])->fetch_all(MYSQLI_ASSOC);

    mysqli_execute_query($conn, "DELETE FROM review WHERE review_id = ?", [$rid]);

    foreach ($photos as $p) {
        $f = __DIR__ . '/../images/reviews/' . basename($p['photo_path']);
        if (is_file($f)) {
            unlink($f);
        }
    }

    $_SESSION['success'] = 'Review deleted';
    header("Location: reviews.php?q=" . urlencode($_GET['q'] ?? ''));
    exit();
}

$q = trim($_GET['q'] ?? '');
$like = "%$q%";
$rows = mysqli_execute_query($conn, "SELECT r.*, i.name AS product, CONCAT(c.fname, ' ', c.lname) AS customer
    FROM review r JOIN item i ON i.item_id = r.item_id LEFT JOIN customer c ON c.customer_id = r.customer_id
    WHERE i.name LIKE ? OR CONCAT(c.fname, ' ', c.lname) LIKE ? OR r.comment LIKE ?
    ORDER BY r.review_id DESC", [$like, $like, $like])->fetch_all(MYSQLI_ASSOC);

// Pull in every photo for each review so admins can see all of them, not just the first.
foreach ($rows as &$row) {
    $row['photos'] = mysqli_execute_query($conn, "SELECT photo_path FROM review_photo WHERE review_id = ? ORDER BY review_photo_id",
        [$row['review_id']])->fetch_all(MYSQLI_ASSOC);
}
unset($row);

$page = 'reviews';
include("../includes/adminHeader.php");
?>
<h2 class="page-title">Product Reviews</h2>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
<form method="GET" class="d-flex gap-2 flex-grow-1" style="max-width:480px">
  <input type="search" class="form-control" name="q" placeholder="Search product, customer, comment..." value="<?php echo htmlspecialchars($q); ?>">
  <button class="btn btn-maroon">Search</button>
  <?php if ($q !== ''): ?><a class="btn btn-outline-secondary" href="reviews.php">Clear</a><?php endif; ?>
</form>
</div>

<div class="panel table-responsive">
  <table class="table table-striped align-middle mb-0">
    <thead><tr><th>ID</th><th>Product</th><th>Customer</th><th>Rating</th><th>Comment</th><th>Photos</th><th>Date</th><th></th></tr></thead>
    <?php $n = 0; foreach ($rows as $r): $n++; ?>
      <tr>
        <td><?php echo $r['review_id']; ?></td>
        <td><?php echo htmlspecialchars($r['product']); ?></td>
        <td><?php echo htmlspecialchars($r['customer'] ?? 'Anonymous'); ?></td>
        <td class="text-warning text-nowrap"><?php echo str_repeat('&#9733;', $r['rating']) . str_repeat('&#9734;', 5 - $r['rating']); ?></td>
        <td><?php echo htmlspecialchars($r['comment'] ?? ''); ?></td>
        <td>
          <?php if ($r['photos']): ?>
            <div class="d-flex flex-wrap gap-1">
              <?php foreach ($r['photos'] as $p): ?>
                <a href="<?php echo BASE; ?>/images/reviews/<?php echo htmlspecialchars($p['photo_path']); ?>" target="_blank">
                  <img src="<?php echo BASE; ?>/images/reviews/<?php echo htmlspecialchars($p['photo_path']); ?>" class="thumb" alt="">
                </a>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <span class="text-muted small">&mdash;</span>
          <?php endif; ?>
        </td>
        <td class="text-nowrap"><?php echo date('M j, Y', strtotime($r['review_date'])); ?></td>
        <td class="text-nowrap">
          <form method="POST" class="d-inline" onsubmit="return confirm('Delete this review? This also deletes its photos.');">
            <input type="hidden" name="delete_id" value="<?php echo $r['review_id']; ?>">
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$n): ?><tr><td colspan="8" class="text-center text-muted py-3">No reviews found.</td></tr><?php endif; ?>
  </table>
</div>
<?php include("../includes/adminFooter.php"); ?>