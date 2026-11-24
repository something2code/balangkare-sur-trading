<?php
declare(strict_types=1);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self'; connect-src 'self'");

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    $isHttps = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$config = require __DIR__ . '/../config/config.php';
try {
    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'],
        $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Start MySQL in XAMPP and import database/schema.sql.');
}

function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function money(float|string $amount): string { return '₱' . number_format((float)$amount, 2); }
function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function verify_csrf(): void {
    $expected = is_string($_SESSION['csrf'] ?? null) ? $_SESSION['csrf'] : '';
    $submitted = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : '';
    if ($expected === '' || !hash_equals($expected, $submitted)) {
        http_response_code(403); exit('This form expired. Go back and try again.');
    }
}
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(?string $role = null): array {
    $user = current_user();
    if (!$user) { header('Location: /login.php'); exit; }
    if ($role && $user['role'] !== $role) { http_response_code(403); exit('This page is for ' . e($role) . ' accounts.'); }
    return $user;
}
function flash(string $message, string $kind = 'success'): void { $_SESSION['flash'] = ['message' => $message, 'kind' => $kind]; }

function request_rate_limit(string $scope, int $limit, int $windowSeconds, string $identity = ''): bool
{
    global $pdo, $config;

    $secret = is_string($config['rate_limit_secret'] ?? null) ? trim($config['rate_limit_secret']) : '';
    if ($secret === '' || $secret === 'replace-with-a-long-random-private-value') {
        $environmentSecret = getenv('APP_RATE_LIMIT_SECRET');
        if (is_string($environmentSecret)) $secret = trim($environmentSecret);
    }
    if ($secret === '' || $secret === 'replace-with-a-long-random-private-value') {
        throw new RuntimeException('The private rate-limit secret is not configured.');
    }

    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    if (filter_var($ip, FILTER_VALIDATE_IP) === false) $ip = 'unknown';
    $bucket = hash_hmac('sha256', $scope . "\0" . $ip . "\0" . $identity, $secret);

    $pdo->beginTransaction();
    try {
        $insert = $pdo->prepare('INSERT IGNORE INTO request_rate_limits(bucket_hash,window_started_at,request_count) VALUES(?,CURRENT_TIMESTAMP,0)');
        $insert->execute([$bucket]);
        $select = $pdo->prepare('SELECT request_count,TIMESTAMPDIFF(SECOND,window_started_at,CURRENT_TIMESTAMP) AS window_age FROM request_rate_limits WHERE bucket_hash=? FOR UPDATE');
        $select->execute([$bucket]);
        $row = $select->fetch();
        if (!$row) throw new RuntimeException('Could not read the request limit bucket.');

        if ((int)$row['window_age'] >= $windowSeconds) {
            $update = $pdo->prepare('UPDATE request_rate_limits SET window_started_at=CURRENT_TIMESTAMP,request_count=1 WHERE bucket_hash=?');
            $update->execute([$bucket]);
            $allowed = true;
        } elseif ((int)$row['request_count'] >= $limit) {
            $allowed = false;
        } else {
            $update = $pdo->prepare('UPDATE request_rate_limits SET request_count=request_count+1 WHERE bucket_hash=?');
            $update->execute([$bucket]);
            $allowed = true;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    if (random_int(1, 100) === 1) {
        try {
            $pdo->exec('DELETE FROM request_rate_limits WHERE window_started_at < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY) LIMIT 500');
        } catch (PDOException $e) {
            error_log('Could not clean expired request rate-limit rows.');
        }
    }

    return $allowed;
}

function image_upload_extension(array $upload, int $maxBytes): string
{
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('The image could not be uploaded. Please try again.');
    }
    if (!isset($upload['size']) || (int)$upload['size'] <= 0 || (int)$upload['size'] > $maxBytes) {
        throw new InvalidArgumentException('The image is empty or larger than the allowed size.');
    }
    $temporaryPath = $upload['tmp_name'] ?? '';
    if (!is_string($temporaryPath) || $temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
        throw new InvalidArgumentException('The uploaded image could not be verified.');
    }

    $mimeTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
    $imageInfo = @getimagesize($temporaryPath);
    if (!isset($mimeTypes[$mime]) || !$imageInfo || ($imageInfo['mime'] ?? '') !== $mime) {
        throw new InvalidArgumentException('Use a valid JPG, PNG, or WebP image.');
    }
    $width = (int)$imageInfo[0];
    $height = (int)$imageInfo[1];
    if ($width < 1 || $height < 1 || $width > 8000 || $height > 8000 || $width * $height > 20000000) {
        throw new InvalidArgumentException('Image dimensions are too large. Use an image under 20 megapixels.');
    }

    return $mimeTypes[$mime];
}
