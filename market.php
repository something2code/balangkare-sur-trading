<?php
require __DIR__ . '/includes/bootstrap.php';
$currentUser = current_user();
$search = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$max = trim($_GET['max_price'] ?? '');
$sql = "SELECT p.*,u.full_name farmer_name FROM products p JOIN users u ON u.id=p.farmer_id WHERE p.status='active' AND p.quantity>0";
$params = [];
if ($search !== '') {
    $sql .= ' AND (p.name LIKE ? OR p.description LIKE ? OR p.location LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}
if (in_array($category, ['Rice','Grains','Seeds','Tools','Fertilizer','Vegetables','Other'], true)) {
    $sql .= ' AND p.category=?';
    $params[] = $category;
}
if (is_numeric($max) && (float)$max > 0) {
    $sql .= ' AND p.price<=?';
    $params[] = (float)$max;
}
$sql .= ' ORDER BY p.created_at DESC';
$st = $pdo->prepare($sql);
$st->execute($params);
$products = $st->fetchAll();
$pageTitle = 'Browse local products';
require __DIR__ . '/includes/header.php';
?>
<section class="page-intro"><span class="eyebrow" style="color:var(--green)">The local harvest</span><h1 style="font-size:clamp(2.2rem,5vw,3.7rem)">Find something good.</h1><p>Browse what Balangkare farmers have available. Prices are shown per unit; the farmer and buyer arrange payment and pickup or delivery directly.</p></section>
<div class="market-layout">
  <form class="panel filters" method="get">
    <h3>Filter products</h3>
    <div class="field filter-search"><label for="q">Search</label><input type="search" id="q" name="q" placeholder="Rice, corn…" value="<?= e($search) ?>"></div>
    <div class="field"><label for="category">Category</label><select name="category" id="category"><option value="">All products</option><?php foreach(['Rice','Grains','Seeds','Tools','Fertilizer','Vegetables','Other'] as $c): ?><option <?= $category === $c ? 'selected' : '' ?> value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="max_price">Max price per unit (₱)</label><input id="max_price" name="max_price" type="number" min="1" step="0.01" placeholder="Any price" value="<?= e($max) ?>"></div>
    <button class="button">Apply filters</button><a class="button button-quiet" style="margin-top:8px" href="/market.php">Clear all</a>
  </form>
  <section>
    <div class="market-toolbar"><strong><?= count($products) ?> listing<?= count($products) === 1 ? '' : 's' ?> found</strong><?php if (($currentUser['role'] ?? '') === 'farmer'): ?><a class="button button-small" href="/farmer/product-new.php">＋ Add a product</a><?php endif; ?></div>
    <?php if ($products): ?>
      <div class="product-grid">
        <?php foreach ($products as $p): ?>
          <article class="product-card">
            <?php require __DIR__ . '/includes/product-art.php'; ?>
            <div class="product-content">
              <span class="tag"><?= e($p['category']) ?></span>
              <h3><?= e($p['name']) ?></h3>
              <div class="product-meta">By <?= e($p['farmer_name']) ?><br><?= e($p['location']) ?></div>
              <div class="product-meta">Available: <?= e(rtrim(rtrim((string)$p['quantity'], '0'), '.')) ?> <?= e($p['unit']) ?></div>
              <div class="product-bottom">
                <span class="price"><?= money($p['price']) ?><small class="product-meta"> / <?= e($p['unit']) ?></small></span>
                <div class="product-actions">
                  <?php if (($currentUser['role'] ?? '') === 'buyer' && (int)$currentUser['id'] !== (int)$p['farmer_id']): ?><form method="post" action="/messages.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"><button class="button button-small button-quiet" type="submit">Message</button></form><?php endif; ?>
                  <a class="button button-small" href="/product.php?id=<?= (int)$p['id'] ?>">Details</a>
                </div>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state"><h3>No products found yet</h3><p>Try another search, or check back when the next harvest is listed.</p><?php if (!$currentUser): ?><a class="button" href="/register.php?role=farmer">List a harvest</a><?php endif; ?></div>
    <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
