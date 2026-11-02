<?php

declare(strict_types=1);

// Environment — loads .env files and defines env() helper
require_once __DIR__ . '/Config/env.php';

// Composer autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// PSR-4 autoloader for App\ namespace
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = $baseDir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Helpers & Session initialization
require_once __DIR__ . '/Helpers/Session.php';
require_once __DIR__ . '/Helpers/session_functions.php';
require_once __DIR__ . '/Helpers/calendar_helper.php';
\App\Helpers\Session::start();

// Database — explicitly load the singleton class before autoloader kicks in
require_once __DIR__ . '/Config/Database.php';
require_once __DIR__ . '/Config/db.php'; // Legacy db functions

use App\Config\Database;

require_once __DIR__ . '/Helpers/helpers.php';
require_once __DIR__ . '/Helpers/validators.php';

// Error reporting & debug mode based on environment and APP_DEBUG setting
// Error reporting & debug mode based on environment and APP_DEBUG setting
$appDebug = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
$appEnv = strtolower((string) env('APP_ENV', 'local'));
$isDev = $appDebug && in_array($appEnv, ['local', 'dev', 'development'], true);

if ($isDev) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
}

set_exception_handler(function (\Throwable $e) use ($isDev) {
    $errRef = 'ERR-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    error_log(sprintf('[%s] [Uncaught Exception] %s: %s in %s:%d', $errRef, get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));

    if ($isDev && class_exists('App\Services\Testing\TestLoggerService')) {
        \App\Services\Testing\TestLoggerService::logBackendException($e, $errRef);
    }

    $isJson = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
        || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));

    if (ob_get_length()) {
        @ob_clean();
    }

    if (!headers_sent()) {
        http_response_code(500);
    }

    if ($isJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $isDev ? $e->getMessage() : 'Something went wrong. Reference ID: ' . $errRef,
            'ref_id'  => $errRef,
            'error'   => $isDev ? get_class($e) : 'InternalServerError'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($isDev) {
        echo '<div style="background:#1e1e2e; color:#f38ba8; padding:20px; font-family:Consolas, monospace; border-left:6px solid #f38ba8; margin:20px; border-radius:8px; box-shadow:0 4px 15px rgba(0,0,0,0.3); z-index:99999; position:relative;">';
        echo '<h2 style="color:#f38ba8; margin-top:0;">⚠️ [Debug Exception] ' . htmlspecialchars(get_class($e)) . '</h2>';
        echo '<p style="color:#cdd6f4; font-size:16px;"><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p style="color:#a6adc8;"><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ' (Line <strong>' . $e->getLine() . '</strong>)</p>';
        echo '<h3 style="color:#89b4fa;">Stack Trace:</h3>';
        echo '<pre style="background:#11111b; color:#a6e3a1; padding:15px; border-radius:6px; overflow-x:auto; font-size:14px; white-space:pre-wrap;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</div>';
    } else {
        echo '<div style="max-width:600px; margin:50px auto; text-align:center; font-family:sans-serif; padding:40px; border-radius:12px; background:#f8f9fa; border:1px solid #dee2e6;">';
        echo '<h1 style="color:#dc3545; font-size:36px; margin-bottom:10px;">500</h1>';
        echo '<h2 style="color:#343a40; font-size:20px;">Something went wrong</h2>';
        echo '<p style="color:#6c757d;">An internal server error occurred while processing your request.</p>';
        echo '<p style="color:#6c757d; font-size:12px; font-family:monospace;">Reference ID: ' . htmlspecialchars($errRef) . '</p>';
        echo '<a href="/" style="display:inline-block; margin-top:15px; padding:10px 20px; background:#0d6efd; color:#fff; text-decoration:none; border-radius:6px;">Return to Home</a>';
        echo '</div>';
    }
    exit;
});

// Boot Router
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

$request = new Request();
$response = new Response();
$router = new Router($request, $response);

// Load modular routes binder
require_once __DIR__ . '/routes/index.php';

// Dispatch
$router->resolve();
