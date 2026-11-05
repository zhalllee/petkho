<?php
session_start();
include("../includes/config.php");
include("../includes/adminAuth.php");
include("../includes/badwords.php");

$id = (int)($_GET['id'] ?? 0);

// Admins may only edit/delete existing customer reviews, not create new ones.
if (!$id) {
    $_SESSION['message'] = 'Admins cannot add reviews. Reviews can only be submitted by customers.';
    header("Location: reviews.php");
    exit();
}

$r = ['item_id' => '', 'customer_id' => '', 'rating' => 5, 'comment' => ''];
if ($id) {
    $r = mysqli_execute_query($conn, "SELECT * FROM review WHERE review_id = ?", [$id])->fetch_assoc();
    if (!$r) {
        $_SESSION['message'] = 'Review not found';
        header("Location: reviews.php");
        exit();
    }
}
$items = mysqli_query($conn, "SELECT item_id, name FROM item ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$custs = mysqli_query($conn, "SELECT customer_id, CONCAT(fname, ' ', lname) AS name FROM customer ORDER BY fname")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = array_map('trim', $_POST);
    $r = array_merge($r, $f);
    $rating = (int)$f['rating'];
    if (!in_array((int)$f['item_id'], array_map('intval', array_column($items, 'item_id')), true)) {
        $_SESSION['message'] = 'Please choose a product';
    } elseif ($rating < 1 || $rating > 5) {
        $_SESSION['message'] = 'Rating must be 1 to 5';
    } else {
        $cust = $f['customer_id'] === '' ? null : (int)$f['customer_id'];
        $vals = [(int)$f['item_id'], $cust, $rating, mask_bad_words($f['comment'])];
        mysqli_execute_query($conn, "UPDATE review SET item_id = ?, customer_id = ?, rating = ?, comment = ? WHERE review_id = ?", [...$vals, $id]);
        $_SESSION['success'] = 'Review updated';
        header("Location: reviews.php");
        exit();
    }
}

$page = 'reviews';
include("../includes/adminHeader.php");
?>
<h2 class="page-title">Edit Review</h2>
<div class="panel" style="max-width:640px">
  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Product</label>
      <select class="form-select" name="item_id" required>
        <option value="">- choose -</option>
        <?php foreach ($items as $i): ?>
          <option value="<?php echo $i['item_id']; ?>" <?php echo (string)$r['item_id'] === (string)$i['item_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($i['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="row">
      <div class="col-md-8 mb-3">
        <label class="form-label">Customer</label>
        <select class="form-select" name="customer_id">
          <option value="">Anonymous</option>
          <?php foreach ($custs as $c): ?>
            <option value="<?php echo $c['customer_id']; ?>" <?php echo (string)$r['customer_id'] === (string)$c['customer_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Rating</label>
        <select class="form-select" name="rating">
          <?php for ($s = 5; $s >= 1; $s--): ?>
            <option value="<?php echo $s; ?>" <?php echo (int)$r['rating'] === $s ? 'selected' : ''; ?>><?php echo $s; ?> star<?php echo $s > 1 ? 's' : ''; ?></option>
          <?php endfor; ?>
        </select>
      </div>
    </div>
    <div class="mb-3"><label class="form-label">Comment</label><textarea class="form-control" name="comment" rows="3"><?php echo htmlspecialchars($r['comment'] ?? ''); ?></textarea></div>
    <button class="btn btn-maroon">Save changes</button>
    <a class="btn btn-outline-secondary" href="reviews.php">Cancel</a>
  </form>
</div>
<?php include("../includes/adminFooter.php"); ?>
