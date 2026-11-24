<?php
$appName = $config['app_name'] ?? 'Balangkare Sur Trading';
$pageTitle = $pageTitle ?? $appName;
$user = current_user();
$cartItems = is_array($_SESSION['cart'] ?? null) ? $_SESSION['cart'] : [];
$cartCount = count($cartItems);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="icon" type="image/png" href="/assets/images/balangkare-icon.png">
  <title><?= e($pageTitle) ?> | <?= e($appName) ?></title>
  <link rel="stylesheet" href="/assets/css/style.css?v=<?= (int) filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<header class="site-header"><a class="brand" href="/index.php"><img class="brand-logo" src="/assets/images/main-logo.png" alt="Balangkare Sur Rice Trading logo"><span><strong>BALANGKARE SUR TRADING</strong><small>Nueva Ecija</small></span></a>
  <button class="menu-toggle" aria-label="Open menu" aria-expanded="false">☰</button>
  <nav class="main-nav"><a href="/market.php">Market</a><a href="/how-it-works.php">How it works</a><a href="/privacy.php">Privacy</a><?php if (($user['role'] ?? '') === 'buyer'): ?><a class="cart-nav-link" href="/cart.php" aria-label="Shopping cart, <?= $cartCount ?> different products"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 1.9-1.4L21 8H6"></path><circle cx="10" cy="20" r="1"></circle><circle cx="18" cy="20" r="1"></circle></svg><span>Cart</span><span class="cart-badge"><?= $cartCount ?></span></a><?php endif; ?>
  <?php if ($user): ?><a href="<?= $user['role'] === 'farmer' ? '/farmer/dashboard.php' : '/orders.php' ?>"><?= $user['role'] === 'farmer' ? 'My farm' : 'My orders' ?></a><a class="profile-link" href="/profile.php" aria-label="My profile"><?php if (!empty($user['profile_image_path'])): ?><img class="nav-avatar" src="<?= e($user['profile_image_path']) ?>" alt=""><?php else: ?><span class="nav-avatar nav-avatar-fallback" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?></span><?php endif; ?><span class="nav-user">Hi, <?= e(explode(' ', $user['full_name'])[0]) ?></span></a><form class="logout-form" method="post" action="/logout.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="button button-small button-quiet" type="submit">Log out</button></form>
  <?php else: ?><a href="/login.php">Log in</a><a class="button button-small" href="/register.php">Join the market</a><?php endif; ?></nav>
</header>
<main class="page-shell">
<?php if (!empty($_SESSION['flash'])): $notice = $_SESSION['flash']; unset($_SESSION['flash']); ?><div class="notice <?= e($notice['kind']) ?>"><?= e($notice['message']) ?></div><?php endif; ?>
