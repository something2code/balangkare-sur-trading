<?php
require __DIR__ . '/includes/bootstrap.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$q = $pdo->prepare("SELECT p.*,u.full_name farmer_name,u.phone farmer_phone FROM products p JOIN users u ON u.id=p.farmer_id WHERE p.id=? AND p.status='active'");
$q->execute([$id ?: 0]);
$product = $q->fetch();
if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="section"><div class="empty-state">This listing is no longer available. <a href="/market.php">Return to the market</a>.</div></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
$pageTitle = $product['name'];
require __DIR__ . '/includes/header.php';
?>
<section class="section"><a class="helper" href="/market.php">← Back to the market</a><div class="two-col" style="margin-top:18px;align-items:start">
  <?php $p = $product; $artStyle = 'height:360px;border-radius:20px;font-size:100px'; require __DIR__ . '/includes/product-art.php'; ?>
  <article class="panel"><span class="tag"><?= e($product['category']) ?></span><h1 style="font-size:2.6rem;margin-top:12px"><?= e($product['name']) ?></h1>
    <div class="price" style="font-size:1.7rem"><?= money($product['price']) ?> <small class="product-meta">per <?= e($product['unit']) ?></small></div>
    <p class="muted">Available: <?= e(rtrim(rtrim((string)$product['quantity'], '0'), '.')) ?> <?= e($product['unit']) ?><br>Grown/listed by <strong><?= e($product['farmer_name']) ?></strong><br><?= e($product['location']) ?></p>
    <p><?= nl2br(e($product['description'] ?: 'The farmer has not added a description yet.')) ?></p>
    <?php if (current_user() && (int)current_user()['id'] === (int)$product['farmer_id']): ?>
      <a class="button button-quiet" href="/farmer/dashboard.php">Manage your listings</a>
    <?php elseif ((current_user()['role'] ?? '') === 'buyer' && (float)$product['quantity'] > 0): ?>
      <form method="post" action="/create-order.php">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
        <div class="field"><label for="quantity">How much? (<?= e($product['unit']) ?>)</label><input id="quantity" name="quantity" type="number" min="0.01" max="<?= e($product['quantity']) ?>" step="0.01" value="1" required></div>
        <div class="field"><label for="fulfillment">Pickup or delivery?</label><select id="fulfillment" name="fulfillment"><option value="pickup">Arrange pickup</option><option value="delivery">Ask about delivery</option></select></div>
        <div class="field"><label for="payment_method">Payment preference</label><select id="payment_method" name="payment_method"><option value="to_agree">Agree with farmer</option><option value="cash">Cash</option><option value="bank_transfer">Bank transfer</option><option value="ewallet">Digital wallet</option></select></div>
        <div class="field"><label for="buyer_note">Note to farmer (optional)</label><textarea id="buyer_note" name="buyer_note" maxlength="500" placeholder="When would you like to collect it?"></textarea></div>
        <div class="product-purchase-actions"><button class="button button-quiet" type="submit" name="action" value="add" formaction="/cart-action.php" formnovalidate>Add to cart</button><button class="button" type="submit">Send order request</button></div>
        <p class="helper">Adding to cart does not reserve stock. A direct order request reserves stock when submitted. Neither action takes payment.</p>
      </form>
    <?php elseif (!current_user()): ?>
      <a class="button" href="/login.php">Log in to order</a>
    <?php elseif ((current_user()['role'] ?? '') !== 'buyer'): ?>
      <p class="disclaimer">Buyer accounts can add products to a cart or send order requests.</p>
    <?php else: ?>
      <p class="disclaimer">This listing is sold out.</p>
    <?php endif; ?>
  </article>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
