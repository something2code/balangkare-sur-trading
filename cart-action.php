<?php
require __DIR__ . '/includes/bootstrap.php';
require_login('buyer');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /cart.php');
    exit;
}
verify_csrf();

$_SESSION['cart'] = is_array($_SESSION['cart'] ?? null) ? $_SESSION['cart'] : [];
$action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
$id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
$message = 'Your cart was updated.';
$kind = 'success';

if (!$id || $id < 1) {
    $message = 'That product could not be found.';
    $kind = 'error';
} elseif ($action === 'remove') {
    unset($_SESSION['cart'][$id]);
} elseif (in_array($action, ['add', 'update'], true)) {
    $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($quantity === false || $quantity <= 0 || $quantity > 99999999) {
        $message = 'Enter a valid quantity above zero.';
        $kind = 'error';
    } else {
        $q = $pdo->prepare("SELECT quantity,unit,farmer_id FROM products WHERE id=? AND status='active'");
        $q->execute([$id]);
        $product = $q->fetch();
        $requested = $action === 'add' ? (float)($_SESSION['cart'][$id] ?? 0) + $quantity : $quantity;
        if ($product && (int)$product['farmer_id'] === (int)current_user()['id']) {
            $message = 'You cannot add your own listing to the cart.';
            $kind = 'error';
        } elseif (!$product || (float)$product['quantity'] <= 0) {
            $message = 'This product is no longer available. Remove it from your cart.';
            $kind = 'error';
        } elseif ($requested > (float)$product['quantity']) {
            $available = rtrim(rtrim(number_format((float)$product['quantity'], 2, '.', ''), '0'), '.');
            $message = 'Only ' . $available . ' ' . $product['unit'] . ' is currently available. Adjust the cart quantity and try again.';
            $kind = 'error';
        } elseif ($action === 'add' && !isset($_SESSION['cart'][$id]) && count($_SESSION['cart']) >= 50) {
            $message = 'Your cart can hold up to 50 different products.';
            $kind = 'error';
        } else {
            $_SESSION['cart'][$id] = round($requested, 2);
            if ($action === 'add') $message = 'Product added to your cart.';
            else $message = 'Cart quantity updated.';
        }
    }
} else {
    $message = 'Choose a valid cart action.';
    $kind = 'error';
}

flash($message, $kind);
header('Location: /cart.php');
exit;
