<?php
// Central Application Bootstrapper
require_once __DIR__ . '/app/Config/env.php';
require_once __DIR__ . '/app/Helpers/Session.php';
\App\Helpers\Session::start();

// Composer autoloader if available
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// PSR-4 Autoloader for App\ namespace
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = $baseDir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Enable Debug & Error reporting if APP_DEBUG is true or in development mode
if (filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN) && in_array(strtolower((string) env('APP_ENV', 'local')), ['local', 'dev', 'development'], true)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');

    if (!function_exists('debug_exception_handler_registered')) {
        function debug_exception_handler_registered() {}
        set_exception_handler(function (\Throwable $e) {
            if (ob_get_length()) {
                @ob_clean();
            }
            if (!headers_sent()) {
                http_response_code(500);
            }
            echo '<div style="background:#1e1e2e; color:#f38ba8; padding:20px; font-family:Consolas, monospace; border-left:6px solid #f38ba8; margin:20px; border-radius:8px; box-shadow:0 4px 15px rgba(0,0,0,0.3); z-index:99999; position:relative;">';
            echo '<h2 style="color:#f38ba8; margin-top:0;">⚠️ [Debug Exception] ' . htmlspecialchars(get_class($e)) . '</h2>';
            echo '<p style="color:#cdd6f4; font-size:16px;"><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p style="color:#a6adc8;"><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ' (Line <strong>' . $e->getLine() . '</strong>)</p>';
            echo '<h3 style="color:#89b4fa;">Stack Trace:</h3>';
            echo '<pre style="background:#11111b; color:#a6e3a1; padding:15px; border-radius:6px; overflow-x:auto; font-size:14px; white-space:pre-wrap;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            echo '</div>';
            exit;
        });
    }
}

require_once __DIR__ . '/app/Config/Database.php';
require_once __DIR__ . '/app/Config/db.php'; // getDbConnection(), getPdoConnection()

// Load Helpers
require_once __DIR__ . '/app/Helpers/Session.php';
require_once __DIR__ . '/app/Helpers/session_functions.php';
require_once __DIR__ . '/app/Helpers/calendar_helper.php';
require_once __DIR__ . '/app/Helpers/helpers.php';
require_once __DIR__ . '/app/Helpers/validators.php';
require_once __DIR__ . '/app/Helpers/audit.php';
require_once __DIR__ . '/app/Helpers/Mailer.php';
require_once __DIR__ . '/app/Helpers/mail_helper.php';
require_once __DIR__ . '/app/Helpers/player_ledger_helper.php';

