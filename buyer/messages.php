<?php
require __DIR__ . '/../includes/bootstrap.php';
$buyer = require_login('buyer');
$q = $pdo->prepare('SELECT c.id conversation_id,c.updated_at,p.name product_name,f.full_name farmer_name,
    (SELECT m.body FROM messages m WHERE m.conversation_id=c.id ORDER BY m.id DESC LIMIT 1) latest_message
    FROM conversations c
    JOIN products p ON p.id=c.product_id
    JOIN users f ON f.id=c.farmer_id
    WHERE c.buyer_id=? AND EXISTS (SELECT 1 FROM messages m WHERE m.conversation_id=c.id)
    ORDER BY c.updated_at DESC LIMIT 100');
$q->execute([$buyer['id']]);
$conversations = $q->fetchAll();
$pageTitle = 'My messages';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro"><span class="eyebrow" style="color:var(--green)">Buyer workspace</span><h1 style="font-size:clamp(2rem,4vw,3.4rem)">My messages</h1><p>Continue conversations with farmers about their products.</p><p><a href="/orders.php">← Back to my orders</a></p></section>
<section class="panel">
  <?php if ($conversations): ?>
    <div class="table-wrap"><table class="data-table"><thead><tr><th>Farmer</th><th>Product</th><th>Latest message</th><th>Updated</th><th></th></tr></thead><tbody>
      <?php foreach ($conversations as $conversation): ?>
        <tr><td><?= e($conversation['farmer_name']) ?></td><td><?= e($conversation['product_name']) ?></td><td><?= e($conversation['latest_message']) ?></td><td><?= e(date('M j, Y · g:i a', strtotime($conversation['updated_at']))) ?></td><td><a class="button button-small" href="/messages.php?conversation_id=<?= (int)$conversation['conversation_id'] ?>">Message</a></td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
  <?php else: ?>
    <div class="empty-state"><h3>No conversations yet</h3><p>Choose Message on a marketplace product card to contact its farmer.</p><a class="button" href="/market.php">Browse the market</a></div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
