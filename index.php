<?php
session_start();
include("./includes/config.php");
include("./includes/header.php");
$q = trim($_GET['q'] ?? ''); $cat = (int)($_GET['cat'] ?? 0);
$min = $_GET['min'] ?? ''; $max = $_GET['max'] ?? '';

$sql = "SELECT i.*, c.name AS category, s.quantity FROM item i
        JOIN category c USING (category_id) JOIN stock s USING (item_id)
        WHERE i.status = 'active'";
$p = [];
if ($q !== '')   { $sql .= " AND (i.name LIKE ? OR i.description LIKE ?)"; $p[] = "%$q%"; $p[] = "%$q%"; }
if ($cat)        { $sql .= " AND i.category_id = ?"; $p[] = $cat; }
if ($min !== '' && is_numeric($min) && $min >= 0) { $sql .= " AND i.sell_price >= ?"; $p[] = $min; }
if ($max !== '' && is_numeric($max) && $max >= 0) { $sql .= " AND i.sell_price <= ?"; $p[] = $max; }
$items = mysqli_execute_query($conn, $sql . " ORDER BY i.name", $p ?: null);

$cats = mysqli_query($conn, "SELECT c.*, (SELECT COUNT(*) FROM item i WHERE i.category_id = c.category_id AND i.status = 'active') AS n
                             FROM category c ORDER BY c.name")->fetch_all(MYSQLI_ASSOC);
$icons = ['Pet Food' => 'bi-basket2-fill', 'Treats' => 'bi-cookie', 'Toys' => 'bi-balloon-heart-fill', 'Grooming' => 'bi-scissors', 'Cages & Beds' => 'bi-house-heart-fill'];

function categoryPicture($name)
{
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');
    foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
        if (is_file(__DIR__ . "/images/categories/$slug.$ext")) {
            return BASE . "/images/categories/$slug.$ext";
        }
    }
    return null;  
}
$heroImg = is_file(__DIR__ . '/images/hero.png');   
?>

<section class="pk-hero mb-4">
  <div class="row align-items-center g-3">
    <div class="col-md-7">
      <div class="pk-eyebrow">Petkho Pet Supplies</div>
      <h1>Everything Your Pets Need, All in One Place!</h1>
      <p class="mb-4">Food, treats, toys, grooming essentials, cages and beds &ndash; delivered to your door.</p>
      <a href="#products" class="btn btn-primary btn-lg">Shop Now! </a>
    </div>
    <div class="col-md-5 text-center">
      <img class="pk-hero-art" src="<?php echo $heroImg ? BASE . '/images/hero.png' : $logoUrl; ?>" alt="">
    </div>
  </div>
</section>

<h2 class="pk-section-title">Categories</h2>
<div class="pk-cats mb-4">
  <?php foreach ($cats as $c): ?>
    <a class="pk-cat <?php echo $cat == $c['category_id'] ? 'active' : ''; ?>" href="<?php echo BASE; ?>/index.php?cat=<?php echo $c['category_id']; ?>#products">
      <div class="pk-cat-circle">
        <?php $pic = categoryPicture($c['name']); ?>
        <?php if ($pic): ?><img src="<?php echo $pic; ?>" alt=""><?php else: ?><i class="bi <?php echo $icons[$c['name']] ?? 'bi-heart-fill'; ?>"></i><?php endif; ?>
      </div>
      <?php echo htmlspecialchars($c['name']); ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="row g-4" id="products">

  <aside class="col-lg-3 pk-side">
    <form method="GET" action="<?php echo BASE; ?>/index.php#products">
      <input type="hidden" name="cat" value="<?php echo $cat; ?>">

      <div class="pk-search mb-4">
        <input class="form-control" name="q" placeholder="Search products" value="<?php echo htmlspecialchars($q); ?>">
        <button type="submit" aria-label="Search"><i class="bi bi-search"></i></button>
      </div>

      <div class="card">
        <div class="card-header">Shop By Categories</div>
        <div class="card-body py-2">
          <ul class="pk-catlist">
            <li><a href="<?php echo BASE; ?>/index.php#products" class="<?php echo !$cat ? 'active' : ''; ?>"><span><i class="bi bi-circle-fill"></i>All products</span></a></li>
            <?php foreach ($cats as $c): ?>
              <li><a href="<?php echo BASE; ?>/index.php?cat=<?php echo $c['category_id']; ?>#products" class="<?php echo $cat == $c['category_id'] ? 'active' : ''; ?>">
                <span><i class="bi bi-circle-fill"></i><?php echo htmlspecialchars($c['name']); ?></span><span><?php echo $c['n']; ?></span>
              </a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <div class="card">
        <div class="card-header">Filter By Price</div>
        <div class="card-body">
          <div class="d-flex gap-2 mb-3">
            <input class="form-control" type="number" min="0" name="min" placeholder="Min &#8369;" value="<?php echo htmlspecialchars($min); ?>">
            <input class="form-control" type="number" min="0" name="max" placeholder="Max &#8369;" value="<?php echo htmlspecialchars($max); ?>">
          </div>
          <button class="btn btn-primary w-100">Filter</button>
          <?php if ($q !== '' || $cat || $min !== '' || $max !== ''): ?>
            <a class="btn btn-outline-secondary w-100 mt-2" href="<?php echo BASE; ?>/index.php#products">Clear filters</a>
          <?php endif; ?>
        </div>
      </div>
    </form>
  </aside>

  <section class="col-lg-9">
    <p class="mb-3 fs-5 fw-semibold">Showing <?php echo $items->num_rows; ?> result<?php echo $items->num_rows == 1 ? '' : 's'; ?></p>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3">
    <?php foreach ($items as $r): ?>
      <div class="col"><div class="card product-card h-100">
        <a class="pk-thumb" href="<?php echo BASE; ?>/product.php?id=<?php echo $r['item_id']; ?>">
          <?php if ($r['img_path']): ?>
            <img src="<?php echo BASE; ?>/images/<?php echo htmlspecialchars($r['img_path']); ?>" alt="<?php echo htmlspecialchars($r['name']); ?>">
          <?php else: ?>
            No image
          <?php endif; ?>
        </a>
        <div class="card-body">
          <span class="badge text-bg-secondary"><?php echo htmlspecialchars($r['category']); ?></span>
          <h5 class="card-title mt-2"><?php echo htmlspecialchars($r['name']); ?></h5>
          <p class="card-text small"><?php echo htmlspecialchars($r['description']); ?></p>
          <div class="d-flex justify-content-between align-items-baseline">
            <span class="pk-price">&#8369;<?php echo number_format($r['sell_price'], 2); ?></span>
            <?php if ($r['quantity'] > 0): ?>
              <span class="small text-success fw-semibold">In Stock: <?php echo (int)$r['quantity']; ?></span>
            <?php else: ?>
              <span class="small text-danger fw-semibold">Out of stock</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="card-footer">
          <div class="d-flex gap-2 align-items-center">
            <?php if ($r['quantity'] > 0): ?>
            <form method="POST" action="<?php echo BASE; ?>/cart_update.php" class="d-flex gap-2 flex-grow-1">
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="item_id" value="<?php echo $r['item_id']; ?>">
              <input type="number" name="qty" value="1" min="1" max="<?php echo $r['quantity']; ?>" class="form-control form-control-sm" style="width:64px">
              <button class="btn btn-sm btn-success">Add to Cart</button>
            </form>
            <?php endif; ?>
            <a href="<?php echo BASE; ?>/product.php?id=<?php echo $r['item_id']; ?>" class="btn btn-sm btn-outline-secondary ms-auto">Details</a>
          </div>
        </div>
      </div></div>
    <?php endforeach; ?>
    </div>

    <?php if (!$items->num_rows): ?>
      <p class="text-muted">No products match your search.</p>
    <?php endif; ?>
  </section>
</div>
<?php include("./includes/footer.php"); ?>