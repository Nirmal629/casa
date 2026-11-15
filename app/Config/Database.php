<?php

declare(strict_types=1);

namespace App\Config;

use mysqli;
use PDO;
use PDOException;
use RuntimeException;
use function env;

class Database
{
    private static ?PDO $pdo = null;
    private static ?mysqli $mysqli = null;
    private static ?array $config = null;

    private static function config(): array
    {
        if (self::$config !== null) {return self::$config;}

        if (!function_exists('env')) {
            $envPath = __DIR__ . '/env.php';
            if (file_exists($envPath)) {
                require_once $envPath;
            }
        }

        return self::$config = [
            'host' => (string) (function_exists('env') ? \env('DB_HOST', '127.0.0.1') : '127.0.0.1'),
            'dbname' => (string) (function_exists('env') ? \env('DB_NAME','casa_test') : 'casa_test'),
            'user' => (string) (function_exists('env') ? \env('DB_USER','root') : 'root'),
            'pass' => (string) (function_exists('env') ? \env('DB_PASS','') : ''),
            'port' => (int) (function_exists('env') ? \env('DB_PORT','3306') : 3306),
            'charset' => (string) (function_exists('env') ? \env('DB_CHARSET','utf8mb4') : 'utf8mb4'),
        ];
    }

    private static function initTimezone(PDO|mysqli|null $conn = null): void
    {
        if (!function_exists('env')) {
            $envPath = __DIR__ . '/env.php';
            if (file_exists($envPath)) {
                require_once $envPath;
            }
        }

        $appTimezone = function_exists('env') ? \env('APP_TIMEZONE', \env('TIMEZONE', 'Asia/Kolkata')) : 'Asia/Kolkata';
        if (!empty($appTimezone)) {
            @date_default_timezone_set((string) $appTimezone);
        }

        if ($conn !== null) {
            try {
                $now = new \DateTime('now', new \DateTimeZone(date_default_timezone_get()));
                $offset = $now->format('P');
                if ($conn instanceof PDO) {
                    $conn->exec("SET time_zone = '{$offset}'");
                } elseif ($conn instanceof mysqli) {
                    $conn->query("SET time_zone = '{$offset}'");
                }
            } catch (\Throwable $e) {
                // Ignore if database time_zone offset fails
            }
        }
    }

    public static function getPdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = self::config();

        $dsn = "mysql:"
            . "host={$config['host']};"
            . "port={$config['port']};"
            . "dbname={$config['dbname']};"
            . "charset={$config['charset']}";

        try {
            self::$pdo = new PDO(
                $dsn,
                $config['user'],
                $config['pass'],
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_TIMEOUT            => 5,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
            self::initTimezone(self::$pdo);
            return self::$pdo;
        } catch (PDOException $e) {
            error_log("[Database Connection Error] " . $e->getMessage());
            throw new RuntimeException('Database connection failed. Please try again later.', 0, $e);
        }
    }

    /**
     * Execute a callback inside a Database PDO Transaction.
     * Automatically commits on success and rolls back on exception.
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::getPdo();
        $wasInTransaction = $pdo->inTransaction();

        if (!$wasInTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $result = $callback($pdo);

            if (!$wasInTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }

            return $result;
        } catch (\Throwable $e) {
            if (!$wasInTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function beginTransaction(): bool
    {
        return self::getPdo()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::getPdo()->commit();
    }

    public static function rollBack(): bool
    {
        return self::getPdo()->rollBack();
    }

    public static function inTransaction(): bool
    {
        return self::getPdo()->inTransaction();
    }
    public static function getMysqli(): mysqli
    {
        if (self::$mysqli instanceof mysqli) {
            return self::$mysqli;
        }

        $config = self::config();

        mysqli_report(MYSQLI_REPORT_OFF);

        self::$mysqli = new mysqli(
            $config['host'],
            $config['user'],
            $config['pass'],
            $config['dbname'],
            $config['port']
        );

        if (self::$mysqli->connect_errno) {
            throw new RuntimeException('Database connection failed: '. self::$mysqli->connect_error);
        }

        if (!self::$mysqli->set_charset($config['charset'])) {
            throw new RuntimeException('Failed to set database charset.');
        }

        self::initTimezone(self::$mysqli);

        return self::$mysqli;
    }
}