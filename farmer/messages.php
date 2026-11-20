<?php
require __DIR__ . '/../includes/bootstrap.php';
$farmer = require_login('farmer');
$q = $pdo->prepare('SELECT c.id conversation_id,c.updated_at,p.name product_name,b.full_name buyer_name,
    (SELECT m.body FROM messages m WHERE m.conversation_id=c.id ORDER BY m.id DESC LIMIT 1) latest_message
    FROM conversations c
    JOIN products p ON p.id=c.product_id
    JOIN users b ON b.id=c.buyer_id
    WHERE c.farmer_id=? AND EXISTS (SELECT 1 FROM messages m WHERE m.conversation_id=c.id)
    ORDER BY c.updated_at DESC LIMIT 100');
$q->execute([$farmer['id']]);
$conversations = $q->fetchAll();
$pageTitle = 'Buyer messages';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro"><span class="eyebrow" style="color:var(--green)">Farmer workspace</span><h1 style="font-size:clamp(2rem,4vw,3.4rem)">Buyer messages</h1><p>Reply to buyers who have questions about your listings.</p><p><a href="/farmer/dashboard.php">← Back to your farm</a></p></section>
<section class="panel">
  <?php if ($conversations): ?>
    <div class="table-wrap"><table class="data-table"><thead><tr><th>Buyer</th><th>Product</th><th>Latest message</th><th>Updated</th><th></th></tr></thead><tbody>
      <?php foreach ($conversations as $conversation): ?>
        <tr><td><?= e($conversation['buyer_name']) ?></td><td><?= e($conversation['product_name']) ?></td><td><?= e($conversation['latest_message']) ?></td><td><?= e(date('M j, Y · g:i a', strtotime($conversation['updated_at']))) ?></td><td><a class="button button-small" href="/messages.php?conversation_id=<?= (int)$conversation['conversation_id'] ?>">Message</a></td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
  <?php else: ?>
    <div class="empty-state"><h3>No conversations yet</h3><p>When a buyer messages one of your listings, the conversation will appear here.</p></div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
