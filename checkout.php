<?php
require __DIR__ . '/includes/bootstrap.php';
$buyer = require_login('buyer');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /cart.php');
    exit;
}
verify_csrf();

$cart = is_array($_SESSION['cart'] ?? null) ? $_SESSION['cart'] : [];
$items = [];
foreach ($cart as $rawId => $rawQuantity) {
    $id = filter_var($rawId, FILTER_VALIDATE_INT);
    $quantity = filter_var($rawQuantity, FILTER_VALIDATE_FLOAT);
    if ($id && $id > 0 && $quantity !== false && $quantity > 0) $items[$id] = (float)$quantity;
}
$fulfillment = is_string($_POST['fulfillment'] ?? null) ? $_POST['fulfillment'] : '';
$payment = is_string($_POST['payment_method'] ?? null) ? $_POST['payment_method'] : '';
$note = is_string($_POST['buyer_note'] ?? null) ? trim($_POST['buyer_note']) : '';
if (!$items || count($items) > 50 || !in_array($fulfillment, ['pickup', 'delivery'], true) || !in_array($payment, ['cash', 'bank_transfer', 'ewallet', 'to_agree'], true) || mb_strlen($note) > 500) {
    flash('Please review your cart and order details, then try again.', 'error');
    header('Location: /cart.php');
    exit;
}

$ids = array_keys($items);
sort($ids, SORT_NUMERIC);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
try {
    $pdo->beginTransaction();
    $q = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders) ORDER BY id FOR UPDATE");
    $q->execute($ids);
    $products = [];
    foreach ($q->fetchAll() as $product) $products[(int)$product['id']] = $product;
    if (count($products) !== count($items)) throw new RuntimeException('One or more cart listings are no longer available. Review your cart.');

    foreach ($items as $id => $quantity) {
        $product = $products[$id];
        if ($product['status'] !== 'active' || $quantity > (float)$product['quantity']) {
            throw new RuntimeException('Stock changed for ' . $product['name'] . '. Review the cart quantities and try again.');
        }
        if ((int)$product['farmer_id'] === (int)$buyer['id']) {
            throw new RuntimeException('You cannot order your own listing. Remove it from the cart.');
        }
    }

    $insert = $pdo->prepare('INSERT INTO orders(buyer_id,farmer_id,product_id,quantity,unit_price,total,fulfillment,payment_method,buyer_note) VALUES(?,?,?,?,?,?,?,?,?)');
    $update = $pdo->prepare("UPDATE products SET quantity=?,status=? WHERE id=?");
    foreach ($items as $id => $quantity) {
        $product = $products[$id];
        $total = round($quantity * (float)$product['price'], 2);
        $insert->execute([$buyer['id'], $product['farmer_id'], $id, $quantity, $product['price'], $total, $fulfillment, $payment, $note !== '' ? $note : null]);
        $remaining = round((float)$product['quantity'] - $quantity, 2);
        $update->execute([$remaining, $remaining <= 0 ? 'sold_out' : 'active', $id]);
    }
    $pdo->commit();
    unset($_SESSION['cart']);
    flash('Order requests sent to ' . count($items) . ' farmer listing(s). The farmers will contact you to confirm details.');
    header('Location: /orders.php');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash($e instanceof RuntimeException && !($e instanceof PDOException) ? $e->getMessage() : 'Could not send the order requests. Please try again.', 'error');
    header('Location: /cart.php');
    exit;
}
