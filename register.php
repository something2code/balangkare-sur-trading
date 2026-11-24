<?php
require __DIR__ . '/includes/bootstrap.php';
if (current_user()) { header('Location: /'); exit; }
$errors = [];
$postedString = static fn(string $key, string $default = ''): string => is_string($_POST[$key] ?? null) ? $_POST[$key] : $default;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($postedString('full_name'));
    $email = strtolower(trim($postedString('email')));
    $phone = trim($postedString('phone'));
    $role = $postedString('role');
    $password = $postedString('password');
    $passwordConfirm = $postedString('password_confirm');
    $privacyConsent = $postedString('privacy_consent');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) $errors[] = 'Enter your name (2 to 120 characters).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (mb_strlen($phone) < 7 || mb_strlen($phone) > 30) $errors[] = 'Enter a phone number we can use for order coordination.';
    if (!in_array($role, ['farmer','buyer'], true)) $errors[] = 'Choose Farmer or Buyer.';
    if (strlen($password) < 8) $errors[] = 'Use a password with at least 8 characters.';
    if ($password !== $passwordConfirm) $errors[] = 'The passwords do not match. Please check both password fields.';
    if ($privacyConsent !== '1') $errors[] = 'Please read and agree to the Privacy Notice before creating your account.';
    if (!$errors) {
        try {
            if (!request_rate_limit('registration-ip', 10, 3600)) {
                http_response_code(429);
                $errors[] = 'Too many accounts were created from this connection. Please try again in an hour.';
            }
        } catch (Throwable $e) {
            error_log('Registration request throttling is unavailable.');
            http_response_code(503);
            $errors[] = 'Registration is temporarily unavailable. Please try again later.';
        }
    }
    if (!$errors) {
        try {
            $q = $pdo->prepare('INSERT INTO users(full_name,email,phone,password_hash,role) VALUES(?,?,?,?,?)');
            $q->execute([$name,$email,$phone,password_hash($password,PASSWORD_DEFAULT),$role]);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id'=>(int)$pdo->lastInsertId(),'full_name'=>$name,'email'=>$email,'phone'=>$phone,'address'=>null,'profile_image_path'=>null,'role'=>$role];
            flash('Welcome! Your account is ready.');
            header('Location: ' . ($role === 'farmer' ? '/farmer/dashboard.php' : '/market.php'));
            exit;
        } catch (PDOException $e) {
            $errors[] = $e->getCode() === '23000' ? 'That email already has an account. Try logging in.' : 'Could not create the account. Please try again.';
        }
    }
}
$pageTitle = 'Join the market';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-layout"><section class="auth-card"><span class="eyebrow" style="color:var(--green)">Join the community</span><h1>Create your account</h1><p>Choose whether you are here to sell a harvest or find one.</p>
<?php foreach ($errors as $error): ?><div class="notice error"><?= e($error) ?></div><?php endforeach; ?>
<dialog class="privacy-announcement" id="privacy-announcement" aria-labelledby="privacy-announcement-title" aria-describedby="privacy-announcement-intro">
  <div class="privacy-announcement__content">
    <span class="eyebrow" style="color:var(--green)">Before you join</span>
    <h2 id="privacy-announcement-title">Privacy Notice</h2>
    <p id="privacy-announcement-intro" class="privacy-announcement__intro">Here’s what this capstone prototype stores and shares when you use it.</p>
    <div class="privacy-announcement__body privacy-content"><?php require __DIR__ . '/includes/privacy-notice-content.php'; ?></div>
    <div class="privacy-consent"><label class="check-option" for="privacy_consent"><input id="privacy_consent" type="checkbox" name="privacy_consent" value="1" form="registration-form" required <?= $postedString('privacy_consent') === '1' ? 'checked' : '' ?>><span>I have read and agree to the Privacy Notice.</span></label><a href="/privacy.php">Read the full Privacy Notice</a></div>
    <button class="button privacy-announcement__continue" type="button" data-close-privacy>Continue to account creation</button>
  </div>
</dialog>
<form id="registration-form" method="post" data-password-match-form><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" required maxlength="120" value="<?= e($postedString('full_name')) ?>" autocomplete="name"></div>
  <div class="field"><label for="role">I am a</label><select id="role" name="role" required><option value="">Choose one</option><option value="farmer" <?= ($_POST['role'] ?? $_GET['role'] ?? '') === 'farmer' ? 'selected' : '' ?>>Farmer — I want to list products</option><option value="buyer" <?= ($_POST['role'] ?? $_GET['role'] ?? '') === 'buyer' ? 'selected' : '' ?>>Buyer — I want to find products</option></select></div>
  <div class="field"><label for="email">Email address</label><input id="email" type="email" name="email" required maxlength="190" value="<?= e($postedString('email')) ?>" autocomplete="email"></div>
  <div class="field"><label for="phone">Phone number</label><input id="phone" name="phone" required maxlength="30" value="<?= e($postedString('phone')) ?>" autocomplete="tel"><div class="helper">Shared with the other person after an order request so you can coordinate.</div></div>
  <div class="field"><label for="password">Password</label><div class="password-control"><input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" data-password-primary><button class="password-toggle" type="button" data-password-toggle aria-controls="password" aria-label="Show password" aria-pressed="false"><svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a14 14 0 0 1-3 3.7M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.2 0 2.3-.2 3.3-.6"/></svg></button></div></div>
  <div class="field"><label for="password_confirm">Confirm password</label><div class="password-control"><input id="password_confirm" type="password" name="password_confirm" required minlength="8" autocomplete="new-password" data-password-confirm><button class="password-toggle" type="button" data-password-toggle aria-controls="password_confirm" aria-label="Show password" aria-pressed="false"><svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a14 14 0 0 1-3 3.7M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.2 0 2.3-.2 3.3-.6"/></svg></button></div><p class="helper password-match-message" data-password-message aria-live="polite"></p></div>
  <button class="button">Create account</button>
</form><p class="helper">Already joined? <a href="/login.php"><strong>Log in</strong></a></p></section></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
