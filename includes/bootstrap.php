<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
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
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
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
