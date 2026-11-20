<?php
require __DIR__ . '/includes/bootstrap.php';
$buyer = require_login('buyer');
$cart = [];
foreach ((is_array($_SESSION['cart'] ?? null) ? $_SESSION['cart'] : []) as $rawId => $rawQuantity) {
    $id = filter_var($rawId, FILTER_VALIDATE_INT);
    $quantity = filter_var($rawQuantity, FILTER_VALIDATE_FLOAT);
    if ($id && $id > 0 && $quantity !== false && $quantity > 0) $cart[$id] = (float)$quantity;
}
$_SESSION['cart'] = $cart;

$products = [];
if ($cart) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $q = $pdo->prepare("SELECT id,name,category,image_path,price,unit,quantity,location,status FROM products WHERE id IN ($placeholders)");
    $q->execute($ids);
    foreach ($q->fetchAll() as $product) $products[(int)$product['id']] = $product;
}

$validForCheckout = !empty($cart);
$estimatedTotal = 0.0;
$pageTitle = 'Shopping cart';
require __DIR__ . '/includes/header.php';
?>
<section class="page-intro"><span class="eyebrow" style="color:var(--green)">Buyer workspace</span><h1 style="font-size:clamp(2rem,4vw,3.4rem)">Your cart</h1><p>Review quantities and confirm the request details for the farmers.</p><a class="helper" href="/market.php">← Continue browsing</a></section>
<section class="section cart-page">
  <?php if (!$cart): ?>
    <div class="empty-state"><h3>Your cart is empty</h3><p>Add products from the marketplace to collect them here.</p><a class="button" href="/market.php">Browse the market</a></div>
  <?php else: ?>
    <div class="cart-list">
      <?php foreach ($cart as $id => $quantity): $product = $products[$id] ?? null; $available = $product && $product['status'] === 'active' && (float)$product['quantity'] > 0; $withinStock = $available && $quantity <= (float)$product['quantity']; if (!$withinStock) $validForCheckout = false; if ($available) $estimatedTotal += $quantity * (float)$product['price']; ?>
        <article class="cart-item">
          <div class="cart-item-info">
            <?php if ($product): ?><span class="tag"><?= e($product['category']) ?></span><h2><?= e($product['name']) ?></h2><p><?= money($product['price']) ?> / <?= e($product['unit']) ?> · Available: <?= e(rtrim(rtrim(number_format((float)$product['quantity'], 2, '.', ''), '0'), '.')) ?> <?= e($product['unit']) ?></p>
            <?php else: ?><h2>Listing unavailable</h2><p>This product was removed from the marketplace.</p><?php endif; ?>
            <?php if (!$available): ?><p class="cart-warning">This listing is paused, sold out, or unavailable. Remove it to continue.</p><?php elseif (!$withinStock): ?><p class="cart-warning">Your requested amount is above the current stock. Reduce it or remove this product.</p><?php endif; ?>
          </div>
          <?php if ($product && $available): ?>
            <form class="cart-update-form" method="post" action="/cart-action.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="product_id" value="<?= (int)$id ?>"><label for="cart-quantity-<?= (int)$id ?>">Quantity (<?= e($product['unit']) ?>)</label><div><input id="cart-quantity-<?= (int)$id ?>" name="quantity" type="number" min="0.01" max="<?= e(max((float)$product['quantity'], 0.01)) ?>" step="0.01" value="<?= e(number_format($quantity, 2, '.', '')) ?>" required><button class="button button-small button-quiet">Update</button></div></form>
            <strong class="cart-line-total">Estimated <?= money($quantity * (float)$product['price']) ?></strong>
          <?php endif; ?>
          <form method="post" action="/cart-action.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?= (int)$id ?>"><button class="button button-small button-quiet" type="submit">Remove</button></form>
        </article>
      <?php endforeach; ?>
    </div>
    <section class="panel cart-checkout">
      <h2>Order request details</h2>
      <p class="helper">Adding products to your cart does not reserve stock. Stock is checked and reserved when you submit the request. This is not a payment.</p>
      <div class="cart-total"><span>Estimated total</span><strong><?= money($estimatedTotal) ?></strong></div>
      <?php if ($validForCheckout): ?>
        <form method="post" action="/checkout.php">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <div class="form-grid"><div class="field"><label for="fulfillment">Pickup or delivery?</label><select id="fulfillment" name="fulfillment"><option value="pickup">Arrange pickup</option><option value="delivery">Ask about delivery</option></select></div><div class="field"><label for="payment_method">Payment preference</label><select id="payment_method" name="payment_method"><option value="to_agree">Agree with farmer</option><option value="cash">Cash</option><option value="bank_transfer">Bank transfer</option><option value="ewallet">Digital wallet</option></select></div></div>
          <div class="field"><label for="buyer_note">Note to farmers (optional)</label><textarea id="buyer_note" name="buyer_note" maxlength="500" placeholder="Include pickup timing or other details"></textarea></div>
          <button class="button" type="submit">Send order requests</button>
        </form>
      <?php else: ?><button class="button" type="button" disabled>Fix unavailable quantities to continue</button><?php endif; ?>
    </section>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
