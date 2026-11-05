<?php
session_start();
include("../includes/config.php");
include("../includes/adminAuth.php");

// ---------- delete ----------
if (isset($_POST['delete_id'])) {
    $c = mysqli_execute_query($conn, "SELECT user_id, (SELECT COUNT(*) FROM orderinfo o WHERE o.customer_id = customer.customer_id) AS orders
        FROM customer WHERE customer_id = ?", [(int)$_POST['delete_id']])->fetch_assoc();
    if (!$c) {
        $_SESSION['message'] = 'Customer not found';
    } elseif ($c['user_id'] == $_SESSION['user_id']) {
        $_SESSION['message'] = 'You cannot delete your own account';
    } elseif ($c['orders'] > 0) {
        $_SESSION['message'] = 'This customer already has orders, so they cannot be deleted. Set the account to Inactive instead (Edit).';
    } else {
        mysqli_begin_transaction($conn);
        mysqli_execute_query($conn, "DELETE FROM customer WHERE customer_id = ?", [(int)$_POST['delete_id']]);
        mysqli_execute_query($conn, "DELETE FROM users WHERE user_id = ?", [$c['user_id']]);
        mysqli_commit($conn);
        $_SESSION['success'] = 'Customer deleted';
    }
    header("Location: customers.php?q=" . urlencode($_GET['q'] ?? ''));
    exit();
}

// ---------- search ----------
$q = trim($_GET['q'] ?? '');
$like = "%$q%";
$rows = mysqli_execute_query($conn, "SELECT c.customer_id, c.fname, c.lname, c.addressline, c.town, c.zipcode, c.phone, u.email, u.status, u.role,
        (SELECT COUNT(*) FROM orderinfo o WHERE o.customer_id = c.customer_id) AS orders
    FROM customer c JOIN users u ON u.user_id = c.user_id
    WHERE u.role = 'customer'
      AND (CONCAT(c.fname, ' ', c.lname) LIKE ? OR u.email LIKE ? OR c.phone LIKE ? OR c.town LIKE ? OR c.addressline LIKE ?)
    ORDER BY c.customer_id DESC", [$like, $like, $like, $like, $like]);

$page = 'customers';
include("../includes/adminHeader.php");
?>
<h2 class="page-title">Customers</h2>

<form method="GET" class="d-flex gap-2 mb-3" style="max-width:480px">
  <input type="search" class="form-control" name="q" placeholder="Search name, email, phone, address..." value="<?php echo htmlspecialchars($q); ?>">
  <button class="btn btn-maroon">Search</button>
  <?php if ($q !== ''): ?><a class="btn btn-outline-secondary" href="customers.php">Clear</a><?php endif; ?>
</form>

<div class="panel table-responsive">
  <table class="table table-striped align-middle mb-0">
    <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Address</th><th>Phone</th><th class="text-center">Orders</th><th>Account</th><th></th></tr></thead>
    <?php $n = 0; foreach ($rows as $r): $n++; ?>
      <tr>
        <td><?php echo $r['customer_id']; ?></td>
        <td><?php echo htmlspecialchars($r['fname'] . ' ' . $r['lname']); ?></td>
        <td><?php echo htmlspecialchars($r['email']); ?></td>
        <td><?php echo htmlspecialchars($r['addressline'] . ', ' . $r['town'] . ' ' . $r['zipcode']); ?></td>
        <td><?php echo htmlspecialchars($r['phone']); ?></td>
        <td class="text-center"><?php echo $r['orders']; ?></td>
        <td><span class="badge text-bg-<?php echo $r['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo $r['status']; ?></span></td>
        <td class="text-nowrap">
          <a class="btn btn-sm btn-outline-secondary" href="customer_edit.php?id=<?php echo $r['customer_id']; ?>">Edit</a>
          <form method="POST" class="d-inline" onsubmit="return confirm('Delete this customer?');">
            <input type="hidden" name="delete_id" value="<?php echo $r['customer_id']; ?>">
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$n): ?><tr><td colspan="8" class="text-center text-muted py-3">No customers found.</td></tr><?php endif; ?>
  </table>
</div>
<?php include("../includes/adminFooter.php"); ?>
