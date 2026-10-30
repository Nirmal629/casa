<?php
/**
 * Legacy Database Connection Helper (DEPRECATED)
 * 
 * @deprecated Use App\Config\Database::getPdo() for all database access.
 * Retained temporarily for backward compatibility with legacy MySQLi scripts.
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/Database.php';
if (file_exists(__DIR__ . '/../Helpers/audit.php')) {
    require_once __DIR__ . '/../Helpers/audit.php';
}

if (!defined('DATABASE_NAME')) define('DATABASE_NAME', env('DB_NAME', 'casa_test'));
if (!defined('USERNAME'))      define('USERNAME', env('DB_USER', 'root'));
if (!defined('PASSWORD'))      define('PASSWORD', env('DB_PASS', ''));

/** @deprecated Use App\Config\Database::getMysqli() or getPdo() */
global $conn;
if (!isset($conn)) {
    $conn = \App\Config\Database::getMysqli();
}

/** @deprecated Use App\Config\Database::getMysqli() */
if (!function_exists('getDbConnection')) {
    function getDbConnection(): mysqli {
        return \App\Config\Database::getMysqli();
    }
}

/** @deprecated Use App\Config\Database::getPdo() */
if (!function_exists('getPdoConnection')) {
    function getPdoConnection(): PDO {
        return \App\Config\Database::getPdo();
    }
}


