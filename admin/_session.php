<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/Config/env.php';
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    session_name((string) env('APP_SESSION_NAME', 'CASA_STAGING_SESSION') . '_ADMIN');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/admin',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443,
        'httponly' => true, 'samesite' => 'Lax']);
    if (!session_start()) throw new RuntimeException('Unable to start admin session.');
}
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
function requireAdminSession(): void {
    if (empty($_SESSION['admin_authenticated']) || empty($_SESSION['user_id'])) {
        http_response_code(401);
        exit('Administrator authentication required.');
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== realpath(__DIR__ . '/index.php')) {
    requireAdminSession();
    if (in_array(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403); exit('Invalid security token.');
        }
    }
}
