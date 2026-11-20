<?php
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();
$conversationId = filter_input(INPUT_GET, 'conversation_id', FILTER_VALIDATE_INT);
$productId = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT)
    : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if ($user['role'] !== 'buyer') {
        http_response_code(403);
        exit('Buyer accounts can start a conversation from a product listing.');
    }
    if (!$productId || $productId < 1) {
        http_response_code(400);
        exit('A valid product is required to start a conversation.');
    }
    $q = $pdo->prepare("SELECT id,farmer_id FROM products WHERE id=? AND status='active' AND quantity>0");
    $q->execute([$productId]);
    $product = $q->fetch();
    if (!$product || (int)$product['farmer_id'] === (int)$user['id']) {
        http_response_code(404);
        exit('This listing is unavailable for a new conversation.');
    }
    $q = $pdo->prepare('INSERT INTO conversations(product_id,buyer_id,farmer_id) VALUES(?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)');
    $q->execute([$productId, $user['id'], $product['farmer_id']]);
    $conversationId = (int)$pdo->lastInsertId();
    header('Location: /messages.php?conversation_id=' . $conversationId);
    exit;
}

if (!$conversationId || $conversationId < 1) {
    http_response_code(400);
    exit('A valid conversation is required.');
}

$q = $pdo->prepare('SELECT c.id,c.buyer_id,c.farmer_id,p.name product_name,p.id product_id,b.full_name buyer_name,f.full_name farmer_name
    FROM conversations c
    JOIN products p ON p.id=c.product_id
    JOIN users b ON b.id=c.buyer_id
    JOIN users f ON f.id=c.farmer_id
    WHERE c.id=?');
$q->execute([$conversationId]);
$conversationRow = $q->fetch();
if (!$conversationRow || ((int)$conversationRow['buyer_id'] !== (int)$user['id'] && (int)$conversationRow['farmer_id'] !== (int)$user['id'])) {
    http_response_code(403);
    exit('You do not have access to this conversation.');
}

$otherName = (int)$conversationRow['buyer_id'] === (int)$user['id'] ? $conversationRow['farmer_name'] : $conversationRow['buyer_name'];
$q = $pdo->prepare('SELECT m.id,m.sender_id,m.body,m.created_at,u.full_name sender_name
    FROM messages m JOIN users u ON u.id=m.sender_id
    WHERE m.conversation_id=? ORDER BY m.id ASC');
$q->execute([$conversationId]);
$messages = $q->fetchAll();
$lastMessageId = $messages ? (int)$messages[count($messages) - 1]['id'] : 0;
$pageTitle = 'Message ' . $otherName;
require __DIR__ . '/includes/header.php';
?>
<section class="page-intro">
  <span class="eyebrow" style="color:var(--green)">Product conversation</span>
  <h1 style="font-size:clamp(2rem,4vw,3.4rem)"><?= e($otherName) ?></h1>
  <p>About <?= e($conversationRow['product_name']) ?> · Private conversation between this buyer and farmer.</p>
  <p><a href="<?= $user['role'] === 'farmer' ? '/farmer/dashboard.php' : '/market.php' ?>">← Back to <?= $user['role'] === 'farmer' ? 'your farm' : 'the market' ?></a></p>
</section>
<section class="panel conversation-panel">
  <div class="conversation-messages" id="conversation-messages" data-conversation-id="<?= (int)$conversationRow['id'] ?>" data-user-id="<?= (int)$user['id'] ?>" data-last-id="<?= $lastMessageId ?>" data-endpoint="/messages-api.php" aria-live="polite" aria-label="Conversation messages">
    <?php if ($messages): foreach ($messages as $message): ?>
      <article class="conversation-message <?= (int)$message['sender_id'] === (int)$user['id'] ? 'conversation-message-own' : '' ?>" data-message-id="<?= (int)$message['id'] ?>">
        <div class="conversation-message-meta"><strong><?= (int)$message['sender_id'] === (int)$user['id'] ? 'You' : e($message['sender_name']) ?></strong><time datetime="<?= e(date(DATE_ATOM, strtotime($message['created_at']))) ?>"><?= e(date('M j, Y · g:i a', strtotime($message['created_at']))) ?></time></div>
        <p><?= e($message['body']) ?></p>
      </article>
    <?php endforeach; else: ?>
      <div class="empty-state" data-empty-message>No messages yet. Send a friendly note to start the conversation.</div>
    <?php endif; ?>
  </div>
  <p class="conversation-status helper" id="conversation-status" role="status" aria-live="polite">Messages update automatically every few seconds.</p>
  <form class="conversation-form" id="conversation-form" method="post" action="/messages-api.php">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="conversation_id" value="<?= (int)$conversationRow['id'] ?>">
    <div class="field"><label for="body">Message to <?= e($otherName) ?></label><textarea id="body" name="body" maxlength="2000" required></textarea><div class="helper">Keep messages respectful and related to this product. Up to 2,000 characters.</div></div>
    <button class="button" type="submit">Send message</button>
  </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
