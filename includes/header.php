<?php
$appName = $config['app_name'] ?? 'Balangkare Sur Trading';
$pageTitle = $pageTitle ?? $appName;
$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="icon" type="image/png" href="/assets/images/balangkare-icon.png">
  <title><?= e($pageTitle) ?> | <?= e($appName) ?></title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="site-header"><a class="brand" href="/index.php"><img class="brand-logo" src="/assets/images/balangkare-icon.png" alt="Balangkare Sur Rice Trading logo"><span><strong>BALANGKARE SUR TRADING</strong><small>Nueva Ecija</small></span></a>
  <button class="menu-toggle" aria-label="Open menu" aria-expanded="false">☰</button>
  <nav class="main-nav"><a href="/market.php">Market</a><a href="/how-it-works.php">How it works</a><a href="/assistant.php">Ask the Farm Guide</a>
  <?php if ($user): ?><a href="<?= $user['role'] === 'farmer' ? '/farmer/dashboard.php' : '/orders.php' ?>"><?= $user['role'] === 'farmer' ? 'My farm' : 'My orders' ?></a><span class="nav-user">Hi, <?= e(explode(' ', $user['full_name'])[0]) ?></span><a class="button button-small button-quiet" href="/logout.php">Log out</a>
  <?php else: ?><a href="/login.php">Log in</a><a class="button button-small" href="/register.php">Join the market</a><?php endif; ?></nav>
</header>
<main class="page-shell">
<?php if (!empty($_SESSION['flash'])): $notice = $_SESSION['flash']; unset($_SESSION['flash']); ?><div class="notice <?= e($notice['kind']) ?>"><?= e($notice['message']) ?></div><?php endif; ?>
