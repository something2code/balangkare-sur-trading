<?php
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();
header('Content-Type: application/json; charset=utf-8');

function message_api_error(string $message, int $status): void
{
    http_response_code($status);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

$conversationId = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? filter_var($_POST['conversation_id'] ?? null, FILTER_VALIDATE_INT)
    : filter_input(INPUT_GET, 'conversation_id', FILTER_VALIDATE_INT);
if (!$conversationId || $conversationId < 1) {
    message_api_error('A valid conversation is required.', 400);
}

try {
    $q = $pdo->prepare('SELECT id,buyer_id,farmer_id FROM conversations WHERE id=?');
    $q->execute([$conversationId]);
    $conversationRow = $q->fetch();
    if (!$conversationRow || ((int)$conversationRow['buyer_id'] !== (int)$user['id'] && (int)$conversationRow['farmer_id'] !== (int)$user['id'])) {
        message_api_error('You do not have access to this conversation.', 403);
    }
    $otherId = (int)$conversationRow['buyer_id'] === (int)$user['id'] ? (int)$conversationRow['farmer_id'] : (int)$conversationRow['buyer_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submittedToken = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : '';
        if (!hash_equals((string)($_SESSION['csrf'] ?? ''), $submittedToken)) {
            message_api_error('This form expired. Reload the conversation and try again.', 403);
        }
        $body = is_string($_POST['body'] ?? null) ? trim($_POST['body']) : '';
        if ($body === '' || mb_strlen($body) > 2000) {
            message_api_error('Write a message of 1 to 2,000 characters.', 422);
        }
        $q = $pdo->prepare('INSERT INTO messages(conversation_id,sender_id,recipient_id,body) VALUES(?,?,?,?)');
        $q->execute([$conversationId, $user['id'], $otherId, $body]);
        $messageId = (int)$pdo->lastInsertId();
        $q = $pdo->prepare('UPDATE conversations SET updated_at=CURRENT_TIMESTAMP WHERE id=?');
        $q->execute([$conversationId]);
        $q = $pdo->prepare('SELECT m.id,m.sender_id,m.body,m.created_at,u.full_name sender_name FROM messages m JOIN users u ON u.id=m.sender_id WHERE m.id=?');
        $q->execute([$messageId]);
        echo json_encode(['message' => $q->fetch()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        message_api_error('This request method is not supported.', 405);
    }
    $afterId = filter_input(INPUT_GET, 'after_id', FILTER_VALIDATE_INT);
    if ($afterId === false || $afterId === null || $afterId < 0) {
        message_api_error('A valid message cursor is required.', 400);
    }
    $q = $pdo->prepare('SELECT m.id,m.sender_id,m.body,m.created_at,u.full_name sender_name
        FROM messages m JOIN users u ON u.id=m.sender_id
        WHERE m.conversation_id=? AND m.id>? ORDER BY m.id ASC LIMIT 100');
    $q->execute([$conversationId, $afterId]);
    echo json_encode(['messages' => $q->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not load or send the message. Please try again.']);
}
