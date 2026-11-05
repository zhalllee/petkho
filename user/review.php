<?php
session_start();
include("../includes/config.php");
include("../includes/badwords.php");
if (empty($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please log in first';
    header("Location: login.php");
    exit();
}

define('REVIEW_MAX_PHOTOS', 5);
define('REVIEW_MAX_PHOTO_SIZE', 2 * 1024 * 1024); // 2MB, same limit as product photos

$orderId = (int)($_GET['order'] ?? $_POST['order'] ?? 0);
$itemId  = (int)($_GET['item'] ?? $_POST['item'] ?? 0);

$photoDir = __DIR__ . '/../images/reviews';
if (!is_dir($photoDir)) {
    mkdir($photoDir, 0777, true);
}

$line = mysqli_execute_query($conn, "SELECT i.name, i.img_path, c.customer_id
    FROM orderinfo o JOIN customer c USING (customer_id) JOIN orderline ol USING (orderinfo_id) JOIN item i USING (item_id)
    WHERE o.orderinfo_id = ? AND ol.item_id = ? AND c.user_id = ? AND o.status = 'Delivered'",
    [$orderId, $itemId, $_SESSION['user_id']])->fetch_assoc();
if (!$line) {
    $_SESSION['message'] = 'You can only review products from your delivered orders';
    header("Location: " . BASE . "/product.php?id=" . $itemId);
    exit();
}

$old = mysqli_execute_query($conn, "SELECT review_id, rating, comment FROM review WHERE item_id = ? AND customer_id = ? LIMIT 1",
    [$itemId, $line['customer_id']])->fetch_assoc();

$oldPhotos = [];
if ($old) {
    $oldPhotos = mysqli_execute_query($conn, "SELECT review_photo_id, photo_path FROM review_photo WHERE review_id = ? ORDER BY review_photo_id",
        [$old['review_id']])->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating  = (int)($_POST['rating'] ?? 0);
    $comment = mask_bad_words(trim($_POST['comment'] ?? ''));  
    $err = '';

    if ($rating < 1 || $rating > 5) {
        $err = 'Please choose a rating from 1 to 5';
    }

    $removeIds = array_map('intval', $_POST['remove_photo'] ?? []);
    $keptCount = 0;
    foreach ($oldPhotos as $p) {
        if (!in_array($p['review_photo_id'], $removeIds, true)) $keptCount++;
    }

    $newFiles = [];
    if (!empty($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
        foreach ($_FILES['photos']['name'] as $i => $name) {
            if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                $newFiles[] = $i;
            }
        }
    }

    if (!$err && ($keptCount + count($newFiles)) < 1) {
        $err = 'Please add at least one photo';
    }

    if (!$err && ($keptCount + count($newFiles)) > REVIEW_MAX_PHOTOS) {
        $err = 'You can only have up to ' . REVIEW_MAX_PHOTOS . ' photos per review';
    }

    $toSave = [];
    if (!$err) {
        foreach ($newFiles as $i) {
            if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) {
                $err = 'One of the photos failed to upload (maybe it is too big)';
                break;
            }
            $tmp = $_FILES['photos']['tmp_name'][$i];
            $ext = strtolower(pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION));
            if ($_FILES['photos']['size'][$i] > REVIEW_MAX_PHOTO_SIZE) {
                $err = 'Each photo must be 2MB or smaller';
                break;
            }
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) || @getimagesize($tmp) === false) {
                $err = 'Photos must be real image files (jpg, png, gif or webp)';
                break;
            }
            $toSave[] = ['tmp' => $tmp, 'ext' => $ext];
        }
    }

    $saved = false;
    if ($err) {
        $_SESSION['message'] = $err;
    } else {
        $movedFiles = [];      // new photo files, deleted again if saving fails
        $oldFiles   = [];      // removed photos, their files are deleted only after the commit
        mysqli_begin_transaction($conn);
        try {
            if ($old) {
                mysqli_execute_query($conn, "UPDATE review SET rating = ?, comment = ?, review_date = NOW() WHERE review_id = ?",
                    [$rating, $comment, $old['review_id']]);
                $reviewId = $old['review_id'];
                $okMessage = 'Your review was updated';
            } else {
                mysqli_execute_query($conn, "INSERT INTO review (item_id, customer_id, rating, comment, review_date) VALUES (?, ?, ?, ?, NOW())",
                    [$itemId, $line['customer_id'], $rating, $comment]);
                $reviewId = mysqli_insert_id($conn);
                $okMessage = 'Thank you for your review!';
            }

            foreach ($oldPhotos as $p) {
                if (in_array($p['review_photo_id'], $removeIds, true)) {
                    mysqli_execute_query($conn, "DELETE FROM review_photo WHERE review_photo_id = ?", [$p['review_photo_id']]);
                    $oldFiles[] = $photoDir . '/' . basename($p['photo_path']);
                }
            }

            foreach ($toSave as $file) {
                $fname = bin2hex(random_bytes(8)) . '.' . $file['ext'];
                if (!move_uploaded_file($file['tmp'], $photoDir . '/' . $fname)) {
                    throw new Exception('Could not save a photo');
                }
                $movedFiles[] = $photoDir . '/' . $fname;
                mysqli_execute_query($conn, "INSERT INTO review_photo (review_id, photo_path) VALUES (?, ?)", [$reviewId, $fname]);
            }
            mysqli_commit($conn);
            $saved = true;
        } catch (Throwable $ex) {
            mysqli_rollback($conn);
            foreach ($movedFiles as $f) {
                @unlink($f);
            }
            $_SESSION['message'] = 'Could not save your review, please try again';
        }

        if ($saved) {
            foreach ($oldFiles as $f) {
                if (is_file($f)) unlink($f);
            }
            $_SESSION['success'] = $okMessage;
            header("Location: " . BASE . "/product.php?id=" . $itemId);
            exit();
        }
    }
    $old = ['rating' => $rating, 'comment' => $comment, 'review_id' => $old['review_id'] ?? null];
}

include("../includes/header.php");
?>
<div class="col-md-6 mx-auto">
  <h3><?php echo !empty($old['review_id']) ? 'Edit your review' : 'Write a review'; ?></h3>
  <p class="text-muted"><?php echo htmlspecialchars($line['name']); ?> &middot; Order #<?php echo str_pad($orderId, 6, '0', STR_PAD_LEFT); ?></p>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="order" value="<?php echo $orderId; ?>">
    <input type="hidden" name="item" value="<?php echo $itemId; ?>">
    <div class="mb-3">
      <label class="form-label">Rating</label>
      <select class="form-select" name="rating" required>
        <option value="">- choose -</option>
        <?php for ($s = 5; $s >= 1; $s--): ?>
          <option value="<?php echo $s; ?>" <?php echo (int)($old['rating'] ?? 0) === $s ? 'selected' : ''; ?>>
            <?php echo str_repeat('&#9733;', $s) . str_repeat('&#9734;', 5 - $s); ?> (<?php echo $s; ?>)
          </option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Comment (optional)</label>
      <textarea class="form-control" name="comment" rows="4" maxlength="1000"><?php echo htmlspecialchars($old['comment'] ?? ''); ?></textarea>
    </div>

    <?php if ($oldPhotos): ?>
    <div class="mb-3">
      <label class="form-label">Your current photos</label>
      <div class="d-flex flex-wrap gap-3">
        <?php foreach ($oldPhotos as $p): ?>
          <div class="text-center">
            <img src="<?php echo BASE; ?>/images/reviews/<?php echo htmlspecialchars($p['photo_path']); ?>" style="width:90px;height:90px;object-fit:cover;border-radius:.25rem" alt="">
            <div class="form-check small mt-1">
              <input class="form-check-input" type="checkbox" name="remove_photo[]" value="<?php echo $p['review_photo_id']; ?>" id="rp<?php echo $p['review_photo_id']; ?>">
              <label class="form-check-label" for="rp<?php echo $p['review_photo_id']; ?>">Remove</label>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="mb-3">
      <label class="form-label">Add photos (required)</label>
      <input type="file" id="pkPhotoInput" class="form-control" accept="image/*" multiple <?php echo $oldPhotos ? '' : 'required'; ?>>
      <div id="pkPhotoCount" class="form-text mb-0">No photos selected yet.</div>
      <div class="form-text">Up to <?php echo REVIEW_MAX_PHOTOS; ?> photos total, jpg/png/gif/webp, 2MB each.</div>
      <div id="pkPhotoPreview" class="d-flex flex-wrap gap-3 mt-2"></div>
      <div id="pkPhotoInputs"></div>
    </div>

    <button class="btn btn-primary">Submit review</button>
    <a class="btn btn-outline-secondary" href="<?php echo BASE; ?>/product.php?id=<?php echo $itemId; ?>">Cancel</a>
  </form>
</div>
<script>
(function () {
  // Lets the customer pick photos, preview them, and remove any of them
  // (one at a time) before the review is actually submitted.
  var picker   = document.getElementById('pkPhotoInput');
  var preview  = document.getElementById('pkPhotoPreview');
  var hiddenBox = document.getElementById('pkPhotoInputs');
  var countLabel = document.getElementById('pkPhotoCount');
  var hasOldPhotos = <?php echo $oldPhotos ? 'true' : 'false'; ?>;
  var selected = []; // holds the actual File objects still queued for upload

  function render() {
    preview.innerHTML = '';
    hiddenBox.innerHTML = '';

    // Custom label instead of relying on the native "No file chosen" text,
    // which resets every time we clear the input after a selection.
    if (selected.length > 0) {
      countLabel.textContent = selected.length + ' photo' + (selected.length > 1 ? 's' : '') + ' selected.';
      countLabel.classList.remove('text-danger');
    } else {
      countLabel.textContent = hasOldPhotos ? 'No new photos added.' : 'No photos selected yet.';
    }

    // Only force the "required" validation when there are truly no photos at all
    // (none queued and none already saved) — otherwise the browser blocks
    // submission even though photos are already attached via the hidden inputs.
    picker.required = selected.length === 0 && !hasOldPhotos;

    selected.forEach(function (file, index) {
      var url = URL.createObjectURL(file);

      var wrap = document.createElement('div');
      wrap.className = 'text-center';
      wrap.innerHTML =
        '<div style="position:relative;display:inline-block">' +
          '<img src="' + url + '" style="width:90px;height:90px;object-fit:cover;border-radius:.25rem" alt="">' +
          '<button type="button" class="btn btn-sm btn-danger" data-index="' + index + '" ' +
            'style="position:absolute;top:-8px;right:-8px;border-radius:50%;width:24px;height:24px;padding:0;line-height:1" ' +
            'title="Remove this photo">&times;</button>' +
        '</div>';
      preview.appendChild(wrap);

      // each still-queued file needs its own input so the form still submits them under photos[]
      var dt = new DataTransfer();
      dt.items.add(file);
      var hidden = document.createElement('input');
      hidden.type = 'file';
      hidden.name = 'photos[]';
      hidden.hidden = true;
      hidden.files = dt.files;
      hiddenBox.appendChild(hidden);
    });

    preview.querySelectorAll('button[data-index]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        selected.splice(parseInt(btn.getAttribute('data-index'), 10), 1);
        render();
      });
    });
  }

  picker.addEventListener('change', function () {
    Array.prototype.forEach.call(picker.files, function (file) {
      selected.push(file);
    });
    picker.value = ''; // allow re-picking the same file name later
    render();
  });
})();
</script>
<?php include("../includes/footer.php"); ?>