<?php
session_start();
include("../includes/config.php");
include("../includes/adminAuth.php");

// no id = add, with id = update
$id = (int)($_GET['id'] ?? 0);
$errors = [];   // field => message
$it = ['name' => '', 'brand' => '', 'category_id' => '', 'supplier_id' => '', 'description' => '', 'sell_price' => '', 'qty' => 0, 'status' => 'active', 'img_path' => null];
if ($id) {
    $it = mysqli_execute_query($conn, "SELECT i.*, COALESCE(s.quantity, 0) AS qty FROM item i LEFT JOIN stock s ON s.item_id = i.item_id WHERE i.item_id = ?", [$id])->fetch_assoc();
    if (!$it) {
        $_SESSION['message'] = 'Product not found';
        header("Location: products.php");
        exit();
    }
}
$cats = mysqli_query($conn, "SELECT category_id, name FROM category ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$sups = mysqli_query($conn, "SELECT supplier_id, name FROM supplier ORDER BY name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = array_map(fn($v) => is_array($v) ? '' : trim($v), $_POST) + ['brand' => '', 'supplier_id' => '', 'description' => '', 'category_id' => '', 'sell_price' => '', 'qty' => '', 'status' => '', 'name' => ''];
    $old = $it['img_path'];
    $it = array_merge($it, $f);
    $it['img_path'] = $old;
    $err = '';

    if ($f['name'] === '') {
        $errors['name'] = 'Product name is required';
    } elseif (strlen($f['name']) > 100) {
        $errors['name'] = 'Product name is too long (100 characters max)';
    }
    if ($f['sell_price'] === '' || !is_numeric($f['sell_price']) || $f['sell_price'] < 0) {
        $errors['sell_price'] = 'Please enter a valid price (0 or more)';
    }
    if (!ctype_digit((string)$f['qty'])) {
        $errors['qty'] = 'Stock must be a whole number (0 or more)';
    }
    if (!in_array($f['status'], ['active', 'retired'], true)) {
        $errors['status'] = 'Invalid status';
    }
    if (!in_array((int)$f['category_id'], array_map('intval', array_column($cats, 'category_id')), true)) {
        $errors['category_id'] = 'Please choose a category';
    }
    if ($f['supplier_id'] !== '' && !in_array((int)$f['supplier_id'], array_map('intval', array_column($sups, 'supplier_id')), true)) {
        $errors['supplier_id'] = 'Invalid supplier';
    }
    if ($errors) {
        $err = 'Please fix the errors below';
    }

    $newImg = null;
    if (!$err && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $err = 'Image upload failed (the file may be too big, the limit is 2MB)';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $err = 'Image is too big, 2MB only';
        } elseif (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) || @getimagesize($file['tmp_name']) === false) {
            $err = 'Please upload a real image (jpg, png, gif or webp)';
        } else {
            $newImg = bin2hex(random_bytes(8)) . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../images/' . $newImg)) {
                $err = 'Could not save the image. Check that the images folder exists and is writable.';
                $newImg = null;
            }
        }
    }

    $gallery = [];
    $removeIds = array_map('intval', $_POST['remove_photo'] ?? []);
    if (!$err && !empty($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
        $keep = 0;
        if ($id) {
            foreach (mysqli_execute_query($conn, "SELECT item_photo_id FROM item_photo WHERE item_id = ?", [$id]) as $gp) {
                if (!in_array((int)$gp['item_photo_id'], $removeIds, true)) $keep++;
            }
        }
        foreach ($_FILES['photos']['name'] as $k => $nm) {
            if ($_FILES['photos']['error'][$k] === UPLOAD_ERR_NO_FILE) continue;
            $gext = strtolower(pathinfo($nm, PATHINFO_EXTENSION));
            if ($_FILES['photos']['error'][$k] !== UPLOAD_ERR_OK) { $errors['photos'] = 'A photo failed to upload (maybe too big)'; break; }
            if ($_FILES['photos']['size'][$k] > 2 * 1024 * 1024) { $errors['photos'] = 'Each photo must be 2MB or smaller'; break; }
            if (!in_array($gext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) || @getimagesize($_FILES['photos']['tmp_name'][$k]) === false) {
                $errors['photos'] = 'Photos must be real images (jpg, png, gif or webp)'; break;
            }
            $gallery[] = ['tmp' => $_FILES['photos']['tmp_name'][$k], 'ext' => $gext];
        }
        if (!isset($errors['photos']) && $keep + count($gallery) > 8) {
            $errors['photos'] = 'A product can have up to 8 extra photos';
        }
        if ($errors) {
            $err = 'Please fix the errors below';
            if ($newImg) { @unlink(__DIR__ . '/../images/' . $newImg); $newImg = null; }
        }
    }

    if ($err) {
        $_SESSION['message'] = $err;
    } else {
        $img = $newImg ?? $old;
        $sup = $f['supplier_id'] === '' ? null : (int)$f['supplier_id'];
        $brand = $f['brand'] === '' ? null : $f['brand'];
        mysqli_begin_transaction($conn);
        if ($id) {
            mysqli_execute_query($conn, "UPDATE item SET category_id = ?, name = ?, brand = ?, description = ?, sell_price = ?, img_path = ?, status = ?, supplier_id = ? WHERE item_id = ?",
                [(int)$f['category_id'], $f['name'], $brand, $f['description'], $f['sell_price'], $img, $f['status'], $sup, $id]);
            mysqli_execute_query($conn, "INSERT INTO stock (item_id, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)", [$id, (int)$f['qty']]);
            $_SESSION['success'] = 'Product updated';
        } else {
            mysqli_execute_query($conn, "INSERT INTO item (category_id, name, brand, description, sell_price, img_path, status, supplier_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [(int)$f['category_id'], $f['name'], $brand, $f['description'], $f['sell_price'], $img, $f['status'], $sup]);
            $itemId = mysqli_insert_id($conn);     // read it BEFORE the next insert
            mysqli_execute_query($conn, "INSERT INTO stock (item_id, quantity) VALUES (?, ?)", [$itemId, (int)$f['qty']]);
            $_SESSION['success'] = 'Product added';
        }
        if ($id) {
            $itemId = $id;
        }

        // gallery: remove the ticked photos (files are deleted after the commit)
        $toDelete = [];
        if ($id && $removeIds) {
            foreach (mysqli_execute_query($conn, "SELECT item_photo_id, photo_path FROM item_photo WHERE item_id = ?", [$id]) as $gp) {
                if (in_array((int)$gp['item_photo_id'], $removeIds, true)) {
                    mysqli_execute_query($conn, "DELETE FROM item_photo WHERE item_photo_id = ?", [$gp['item_photo_id']]);
                    $toDelete[] = $gp['photo_path'];
                }
            }
        }
        // gallery: add the new ones
        $saved = [];
        foreach ($gallery as $g) {
            $gname = 'items/' . bin2hex(random_bytes(8)) . '.' . $g['ext'];
            if (move_uploaded_file($g['tmp'], __DIR__ . '/../images/' . $gname)) {
                mysqli_execute_query($conn, "INSERT INTO item_photo (item_id, photo_path) VALUES (?, ?)", [$itemId, $gname]);
                $saved[] = $gname;
            }
        }
        mysqli_commit($conn);
        foreach ($toDelete as $gp) {
            if (is_file(__DIR__ . '/../images/' . $gp)) unlink(__DIR__ . '/../images/' . $gp);
        }

        // the old picture is no longer used
        if ($newImg && $old && is_file(__DIR__ . '/../images/' . basename($old))) {
            unlink(__DIR__ . '/../images/' . basename($old));
        }
        header("Location: products.php");
        exit();
    }
}

$page = 'products';
function ferr($errors, $k) { return isset($errors[$k]) ? '<div class="invalid-feedback">' . htmlspecialchars($errors[$k]) . '</div>' : ''; }
function fcls($errors, $k) { return isset($errors[$k]) ? ' is-invalid' : ''; }
include("../includes/adminHeader.php");
?>
<h2 class="page-title"><?php echo $id ? 'Edit Product' : 'Add Product'; ?></h2>
<div class="panel" style="max-width:720px">
  <form method="POST" enctype="multipart/form-data" novalidate>
    <div class="row">
      <div class="col-md-8 mb-3"><label class="form-label">Product name</label><input class="form-control<?php echo fcls($errors, 'name'); ?>" name="name" value="<?php echo htmlspecialchars($it['name']); ?>"><?php echo ferr($errors, 'name'); ?></div>
      <div class="col-md-4 mb-3"><label class="form-label">Brand</label><input class="form-control" name="brand" value="<?php echo htmlspecialchars($it['brand'] ?? ''); ?>"></div>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Category</label>
        <select class="form-select<?php echo fcls($errors, 'category_id'); ?>" name="category_id">
          <option value="">- choose -</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?php echo $c['category_id']; ?>" <?php echo (string)$it['category_id'] === (string)$c['category_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
        <?php echo ferr($errors, 'category_id'); ?>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Supplier</label>
        <select class="form-select" name="supplier_id">
          <option value="">- none -</option>
          <?php foreach ($sups as $s): ?>
            <option value="<?php echo $s['supplier_id']; ?>" <?php echo (string)$it['supplier_id'] === (string)$s['supplier_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($it['description'] ?? ''); ?></textarea></div>
    <div class="row">
      <div class="col-md-4 mb-3"><label class="form-label">Price (&#8369;)</label><input type="text" class="form-control<?php echo fcls($errors, 'sell_price'); ?>" name="sell_price" value="<?php echo htmlspecialchars($it['sell_price']); ?>"><?php echo ferr($errors, 'sell_price'); ?></div>
      <div class="col-md-4 mb-3"><label class="form-label">Stock</label><input type="text" class="form-control<?php echo fcls($errors, 'qty'); ?>" name="qty" value="<?php echo htmlspecialchars($it['qty']); ?>"><?php echo ferr($errors, 'qty'); ?></div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
          <option value="active" <?php echo $it['status'] === 'active' ? 'selected' : ''; ?>>Active (shown in shop)</option>
          <option value="retired" <?php echo $it['status'] === 'retired' ? 'selected' : ''; ?>>Retired (hidden)</option>
        </select>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Product image</label>
      <?php if ($it['img_path']): ?>
        <div class="mb-2"><img src="<?php echo BASE; ?>/images/<?php echo htmlspecialchars($it['img_path']); ?>" style="height:110px;border-radius:.25rem" alt=""> <span class="text-muted small ms-2">current image</span></div>
      <?php endif; ?>
      <input type="file" class="form-control" name="image" accept="image/*">
      <div class="form-text">jpg, png, gif or webp, up to 2MB. <?php echo $id ? 'Leave empty to keep the current image.' : ''; ?></div>
    </div>
    <div class="mb-3">
      <label class="form-label">More photos (gallery)</label>
      <?php if ($id): $gal = mysqli_execute_query($conn, "SELECT item_photo_id, photo_path FROM item_photo WHERE item_id = ? ORDER BY item_photo_id", [$id])->fetch_all(MYSQLI_ASSOC); ?>
        <?php if ($gal): ?>
          <div class="d-flex flex-wrap gap-3 mb-2">
            <?php foreach ($gal as $g): ?>
              <div class="text-center">
                <img src="<?php echo BASE; ?>/images/<?php echo htmlspecialchars($g['photo_path']); ?>" style="width:90px;height:90px;object-fit:cover;border-radius:.25rem" alt="">
                <div class="form-check small mt-1">
                  <input class="form-check-input" type="checkbox" name="remove_photo[]" value="<?php echo $g['item_photo_id']; ?>" id="gp<?php echo $g['item_photo_id']; ?>">
                  <label class="form-check-label" for="gp<?php echo $g['item_photo_id']; ?>">Remove</label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
      <input type="file" class="form-control<?php echo fcls($errors, 'photos'); ?>" name="photos[]" accept="image/*" multiple>
      <?php echo ferr($errors, 'photos'); ?>
      <div class="form-text">You can pick many files at once. Up to 8 extra photos, 2MB each.</div>
    </div>
    <button class="btn btn-maroon"><?php echo $id ? 'Save changes' : 'Add product'; ?></button>
    <a class="btn btn-outline-secondary" href="products.php">Cancel</a>
  </form>
</div>
<?php include("../includes/adminFooter.php"); ?>
