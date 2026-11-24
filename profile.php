<?php
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();
$q = $pdo->prepare('SELECT id,full_name,email,phone,address,profile_image_path,password_hash FROM users WHERE id=?');
$q->execute([$user['id']]);
$profile = $q->fetch();
if (!$profile) {
    http_response_code(404);
    exit('Profile not found.');
}

function remove_profile_photo(?string $path): void
{
    if (!$path || strpos($path, '/uploads/profile/') !== 0) return;
    $filename = basename($path);
    if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $filename)) return;
    $filePath = __DIR__ . '/uploads/profile/' . $filename;
    if (is_file($filePath)) unlink($filePath);
}

$errors = [];
$form = [
    'full_name' => $profile['full_name'],
    'email' => $profile['email'],
    'phone' => $profile['phone'],
    'address' => $profile['address'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach (array_keys($form) as $field) {
        $form[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
    }
    $currentPassword = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
    if (mb_strlen($form['full_name']) < 2 || mb_strlen($form['full_name']) > 120) $errors[] = 'Enter a name between 2 and 120 characters.';
    if (strlen($form['email']) > 190 || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (mb_strlen($form['phone']) < 7 || mb_strlen($form['phone']) > 30) $errors[] = 'Enter a phone number between 7 and 30 characters.';
    if (mb_strlen($form['address']) > 255) $errors[] = 'Address must be 255 characters or fewer.';
    $emailChanged = strtolower($form['email']) !== strtolower($profile['email']);
    if ($emailChanged && ($currentPassword === '' || !password_verify($currentPassword, $profile['password_hash']))) {
        $errors[] = 'Enter your current password to change your email address.';
    }

    $upload = $_FILES['profile_photo'] ?? null;
    $extension = null;
    if ($upload && $upload['error'] !== UPLOAD_ERR_NO_FILE) {
        try {
            $extension = image_upload_extension($upload, 2 * 1024 * 1024);
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        $newPhotoPath = $profile['profile_image_path'];
        $storedFile = null;
        if (($_POST['remove_profile_photo'] ?? '') === '1') $newPhotoPath = null;
        if ($extension !== null) {
            $uploadDirectory = __DIR__ . '/uploads/profile';
            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                $errors[] = 'Could not prepare the profile photo folder.';
            } else {
                $filename = bin2hex(random_bytes(16)) . '.' . $extension;
                $storedFile = $uploadDirectory . '/' . $filename;
                if (move_uploaded_file($upload['tmp_name'], $storedFile)) {
                    $newPhotoPath = '/uploads/profile/' . $filename;
                } else {
                    $storedFile = null;
                    $errors[] = 'Could not save the profile photo. Check the uploads folder permissions.';
                }
            }
        }

        if (!$errors) {
            try {
                $q = $pdo->prepare('UPDATE users SET full_name=?,email=?,phone=?,address=?,profile_image_path=? WHERE id=?');
                $q->execute([$form['full_name'], strtolower($form['email']), $form['phone'], $form['address'] ?: null, $newPhotoPath, $profile['id']]);
                if ($newPhotoPath !== $profile['profile_image_path']) remove_profile_photo($profile['profile_image_path']);
                $_SESSION['user']['full_name'] = $form['full_name'];
                $_SESSION['user']['email'] = strtolower($form['email']);
                $_SESSION['user']['phone'] = $form['phone'];
                $_SESSION['user']['address'] = $form['address'] ?: null;
                $_SESSION['user']['profile_image_path'] = $newPhotoPath;
                flash('Your profile has been updated.');
                header('Location: /profile.php');
                exit;
            } catch (PDOException $e) {
                if ($storedFile && is_file($storedFile)) unlink($storedFile);
                $errors[] = $e->getCode() === '23000' ? 'That email address is already used by another account.' : 'Could not update your profile. Please try again.';
            }
        } elseif ($storedFile && is_file($storedFile)) {
            unlink($storedFile);
        }
    }
}

$pageTitle = 'My profile';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-layout"><section class="auth-card profile-card">
  <span class="eyebrow" style="color:var(--green)">Account settings</span><h1>My profile</h1><p>Update the details people use to recognize and contact you.</p>
  <?php foreach ($errors as $error): ?><div class="notice error"><?= e($error) ?></div><?php endforeach; ?>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="profile-photo-preview"><?php if (!empty($profile['profile_image_path'])): ?><img src="<?= e($profile['profile_image_path']) ?>" alt="Current profile photo"><?php else: ?><span><?= e(mb_strtoupper(mb_substr($form['full_name'], 0, 1))) ?></span><?php endif; ?></div>
    <div class="field"><label for="profile_photo">Profile picture</label><input id="profile_photo" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp"><div class="helper">JPG, PNG, or WebP · up to 2 MB</div><?php if (!empty($profile['profile_image_path'])): ?><label class="check-option"><input type="checkbox" name="remove_profile_photo" value="1"> Remove current picture</label><?php endif; ?></div>
    <div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" maxlength="120" value="<?= e($form['full_name']) ?>" autocomplete="name" required></div>
    <div class="field"><label for="email">Email address</label><input id="email" type="email" name="email" maxlength="190" value="<?= e($form['email']) ?>" autocomplete="email" required><div class="helper">Changing your email requires your current password.</div></div>
    <div class="field"><label for="phone">Contact number</label><input id="phone" name="phone" maxlength="30" value="<?= e($form['phone']) ?>" autocomplete="tel" required></div>
    <div class="field"><label for="address">Address</label><input id="address" name="address" maxlength="255" value="<?= e($form['address']) ?>" autocomplete="street-address"><div class="helper">Your address is kept private in your account.</div></div>
    <div class="field"><label for="current_password">Current password</label><input id="current_password" type="password" name="current_password" autocomplete="current-password"><div class="helper">Required only when changing your email.</div></div>
    <button class="button" type="submit">Save profile</button>
  </form>
</section></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
