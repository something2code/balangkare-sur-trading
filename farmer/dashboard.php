<?php
require __DIR__ . '/../includes/bootstrap.php';
$farmer = require_login('farmer');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
    if ($action === 'product_status') {
        $status = $_POST['status'] ?? '';
        if ($id && in_array($status, ['active', 'paused'], true)) {
            $q = $pdo->prepare('UPDATE products SET status=? WHERE id=? AND farmer_id=? AND quantity>0');
            $q->execute([$status, $id, $farmer['id']]);
            flash('Listing updated.');
        }
    } elseif ($action === 'product_stock') {
        $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($id && $quantity !== false && $quantity >= 0 && $quantity <= 99999999) {
            $q = $pdo->prepare("UPDATE products SET quantity=?,status=CASE WHEN ?<=0 THEN 'sold_out' WHEN status='sold_out' THEN 'active' ELSE status END WHERE id=? AND farmer_id=?");
            $q->execute([$quantity, $quantity, $id, $farmer['id']]);
            flash('Available quantity updated.');
        } else {
            flash('Enter a valid stock amount.', 'error');
        }
    }
    header('Location: /farmer/dashboard.php');
    exit;
}

$q = $pdo->prepare('SELECT COUNT(*) total, COALESCE(SUM(status="new"),0) new_orders FROM orders WHERE farmer_id=?');
$q->execute([$farmer['id']]);
$stats = $q->fetch();
$q = $pdo->prepare('SELECT * FROM products WHERE farmer_id=? ORDER BY created_at DESC');
$q->execute([$farmer['id']]);
$products = $q->fetchAll();
$q = $pdo->prepare("SELECT o.*,p.name product_name,u.full_name buyer_name,u.phone buyer_phone FROM orders o JOIN products p ON p.id=o.product_id JOIN users u ON u.id=o.buyer_id WHERE o.farmer_id=? ORDER BY o.created_at DESC LIMIT 30");
$q->execute([$farmer['id']]);
$orders = $q->fetchAll();
$pageTitle = 'Farmer dashboard';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
  <span class="eyebrow" style="color:var(--green)">Your farm workspace</span>
  <h1 style="font-size:clamp(2rem,4vw,3.4rem)">Hello, <?= e(explode(' ', $farmer['full_name'])[0]) ?>.</h1>
  <p>Add your available harvest and keep buyer requests organized in one place.</p>
  <a class="button button-small" href="/farmer/messages.php">Open buyer messages</a>
</section>
<div class="section" style="padding-top:0">
  <div class="stats-grid"><div class="stat-card"><div class="stat-value"><?= count($products) ?></div><div class="stat-label">Your listings</div></div><div class="stat-card"><div class="stat-value"><?= (int)$stats['new_orders'] ?></div><div class="stat-label">New order requests</div></div><div class="stat-card"><div class="stat-value"><?= (int)$stats['total'] ?></div><div class="stat-label">Total orders</div></div></div>

  <section class="panel">
    <div class="section-heading"><div><h2 style="font-size:1.6rem">Product management</h2><p>Keep stock and availability up to date.</p></div><a class="button" href="/farmer/product-new.php">＋ Add product</a></div>
    <?php if ($products): ?>
      <div class="table-wrap"><table class="data-table"><thead><tr><th>Product</th><th>Category</th><th>Price / unit</th><th>Available</th><th>Status</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($products as $p): ?>
          <tr><td><strong><?= e($p['name']) ?></strong></td><td><?= e($p['category']) ?></td><td><?= money($p['price']) ?> / <?= e($p['unit']) ?></td><td><?= e(rtrim(rtrim((string)$p['quantity'], '0'), '.')) ?> <?= e($p['unit']) ?></td><td><span class="status"><?= e(str_replace('_', ' ', $p['status'])) ?></span></td><td><?php if ($p['status'] !== 'sold_out'): ?><form class="inline-form" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="product_status"><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"><select aria-label="Listing status" name="status"><option value="active" <?= $p['status'] === 'active' ? 'selected' : '' ?>>Active</option><option value="paused" <?= $p['status'] === 'paused' ? 'selected' : '' ?>>Paused</option></select><button class="button button-small">Save</button></form><?php else: ?>—<?php endif; ?></td></tr>
        <?php endforeach; ?>
      </tbody></table></div>
    <?php else: ?>
      <div class="empty-state">Your first listing helps buyers discover your harvest. <a href="/farmer/product-new.php">Add a product</a>.</div>
    <?php endif; ?>
  </section>

  <section class="panel">
    <div class="section-heading"><div><h2 style="font-size:1.6rem">Incoming orders</h2><p>Contact the buyer to confirm payment and fulfillment details.</p></div></div>
    <?php if ($orders): ?>
      <div class="table-wrap"><table class="data-table"><thead><tr><th>Date</th><th>Product</th><th>Buyer</th><th>Quantity</th><th>Total</th><th>Request</th><th>Status / action</th></tr></thead><tbody>
        <?php foreach ($orders as $o): ?>
          <tr><td><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td><td><?= e($o['product_name']) ?></td><td><?= e($o['buyer_name']) ?><br><small><?= e($o['buyer_phone']) ?></small></td><td><?= e(rtrim(rtrim((string)$o['quantity'], '0'), '.')) ?></td><td><?= money($o['total']) ?></td><td><?= e(ucfirst($o['fulfillment'])) ?><br><?= e(str_replace('_', ' ', $o['payment_method'])) ?><?php if ($o['buyer_note']): ?><br><small><?= e($o['buyer_note']) ?></small><?php endif; ?></td><td><span class="status status-<?= e($o['status']) ?>"><?= e(str_replace('_', ' ', $o['status'])) ?></span><?php if (in_array($o['status'], ['new', 'accepted', 'ready'], true)): ?><form class="inline-form" method="post" action="/farmer/order-action.php" style="margin-top:8px"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>"><select name="status" aria-label="Change order status"><?php foreach (($o['status'] === 'new' ? ['accepted' => 'Accept', 'rejected' => 'Reject'] : ($o['status'] === 'accepted' ? ['ready' => 'Mark ready'] : ['completed' => 'Complete'])) as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?></select><button class="button button-small">Update</button></form><?php endif; ?></td></tr>
        <?php endforeach; ?>
      </tbody></table></div>
    <?php else: ?>
      <div class="empty-state">No requests yet. Active product listings will appear in the market.</div>
    <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
