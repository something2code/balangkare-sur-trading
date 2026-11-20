<?php
require __DIR__ . '/includes/bootstrap.php';
if (current_user()) { header('Location: /'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $q = $pdo->prepare('SELECT id,full_name,email,phone,address,profile_image_path,password_hash,role FROM users WHERE email=?');
    $q->execute([$email]);
    $u = $q->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true);
        unset($u['password_hash']);
        $_SESSION['user'] = $u;
        header('Location: ' . ($u['role'] === 'farmer' ? '/farmer/dashboard.php' : '/market.php'));
        exit;
    }
    $error = 'Email or password was not recognized.';
}
$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-layout"><section class="auth-card"><span class="eyebrow" style="color:var(--green)">Welcome back</span><h1>Log in to the market</h1><p>Manage your harvest listings or check your orders.</p>
<?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <div class="field"><label for="email">Email address</label><input id="email" type="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>"></div>
  <div class="field"><label for="password">Password</label><div class="password-control"><input id="password" type="password" name="password" required autocomplete="current-password"><button class="password-toggle" type="button" data-password-toggle aria-controls="password" aria-label="Show password" aria-pressed="false"><svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a14 14 0 0 1-3 3.7M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.2 0 2.3-.2 3.3-.6"/></svg></button></div></div>
  <button class="button">Log in</button>
</form><p class="helper">New to the market? <a href="/register.php"><strong>Create an account</strong></a></p></section></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
