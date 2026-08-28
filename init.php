<?php
/**
 * ============================================
 * Project Autoloader
 * ============================================
 * Include this file at the top of any page to load
 * all helpers, config, and session management.
 * ============================================
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment config
require_once __DIR__ . '/config/env.php';

// Load database config and connections
require_once __DIR__ . '/config/database.php';

// Load session helper
require_once __DIR__ . '/helpers/session.php';

// Load common helpers
require_once __DIR__ . '/helpers/helpers.php';

// Load validators
require_once __DIR__ . '/helpers/validators.php';

// Error reporting (respect .env)
if (env('APP_ENV', 'production') === 'local' || env('APP_ENV') === 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}