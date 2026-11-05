<?php
session_start();
include("../includes/config.php");
if (empty($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please log in first';
    header("Location: login.php");
    exit();
}
$c = mysqli_execute_query($conn, "SELECT customer_id FROM customer WHERE user_id = ?", [$_SESSION['user_id']])->fetch_assoc();
$cid = $c['customer_id'] ?? 0;

// open one email (only if it belongs to this customer)
$view = null;
if (isset($_GET['id'])) {
    $view = mysqli_execute_query($conn, "SELECT * FROM inbox WHERE inbox_id = ? AND customer_id = ?", [(int)$_GET['id'], $cid])->fetch_assoc();
    if ($view) {
        mysqli_execute_query($conn, "UPDATE inbox SET is_read = 1 WHERE inbox_id = ?", [$view['inbox_id']]);
    }
}
$msgs = mysqli_execute_query($conn, "SELECT inbox_id, subject, is_read, created_at FROM inbox WHERE customer_id = ? ORDER BY inbox_id DESC", [$cid]);

include("../includes/header.php");
?>
<h3>Inbox</h3>
<div class="row g-3">
  <div class="col-md-4">
    <div class="list-group">
      <?php $n = 0; foreach ($msgs as $m): $n++; ?>
        <a class="list-group-item list-group-item-action <?php echo ($view && $view['inbox_id'] == $m['inbox_id']) ? 'active' : ''; ?>"
           href="<?php echo BASE; ?>/user/inbox.php?id=<?php echo $m['inbox_id']; ?>">
          <div class="<?php echo $m['is_read'] ? '' : 'fw-bold'; ?>"><?php echo htmlspecialchars($m['subject']); ?></div>
          <small><?php echo date('M j, Y g:i A', strtotime($m['created_at'])); ?></small>
        </a>
      <?php endforeach; ?>
      <?php if (!$n): ?><div class="list-group-item text-muted">No messages yet.</div><?php endif; ?>
    </div>
  </div>
  <div class="col-md-8">
    <?php if ($view): ?>
      <div class="card"><div class="card-body">
        <h5><?php echo htmlspecialchars($view['subject']); ?></h5>
        <hr>
        <?php echo $view['body']; ?>
      </div></div>
    <?php else: ?>
      <p class="text-muted">Select a message to read it.</p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
