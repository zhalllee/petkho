<?php
// Admin only: list of staff and admin accounts. The role can only be changed here.
session_start();
include("../includes/config.php");
include("../includes/adminAuth.php");

$rows = mysqli_query($conn, "SELECT user_id, email, role, status, created_at FROM users WHERE role IN ('admin', 'staff') ORDER BY role, email");

$page = 'staff';
include("../includes/adminHeader.php");
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <h2 class="page-title mb-0">Staff Accounts</h2>
  <a class="btn btn-maroon" href="staff_form.php">+ Add Staff</a>
</div>

<div class="panel table-responsive">
  <table class="table table-striped align-middle mb-0">
    <thead><tr><th>ID</th><th>Email</th><th>Role</th><th>Account</th><th>Created</th><th></th></tr></thead>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo $r['user_id']; ?></td>
        <td><?php echo htmlspecialchars($r['email']); ?><?php echo $r['user_id'] == $_SESSION['user_id'] ? ' <span class="text-muted small">(you)</span>' : ''; ?></td>
        <td><span class="badge text-bg-<?php echo $r['role'] === 'admin' ? 'danger' : 'primary'; ?>"><?php echo ucfirst($r['role']); ?></span></td>
        <td><span class="badge text-bg-<?php echo $r['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo $r['status']; ?></span></td>
        <td><?php echo date('M j, Y', strtotime($r['created_at'])); ?></td>
        <td><a class="btn btn-sm btn-outline-secondary" href="staff_form.php?id=<?php echo $r['user_id']; ?>">Edit</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<p class="text-muted small mt-2">Staff can update transactions and inventory. Admins can do everything. To remove someone's access, set the account to Inactive.</p>
<?php include("../includes/adminFooter.php"); ?>
