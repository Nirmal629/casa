<?php

declare(strict_types=1);

namespace App\Services\Testing;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * Service for Development-only Manual Testing Journey Logging.
 * Saves structured JSONL event records for QA, manual test tracking, and post-session analysis.
 * Strictly inactive in production.
 */
class TestLoggerService
{
    private const string LOG_DIR = __DIR__ . '/../../../../storage/logs/manual-testing';
    private const array SENSITIVE_KEYS = [
        'password', 'pwd', 'pass', 'token', 'csrf', 'csrf_token', 'session',
        'auth', 'secret', 'key', 'card', 'cvv', 'card_number', 'ssn', 'cookie', 'authorization'
    ];

    /**
     * Check if application is currently in development mode.
     */
    public static function isDev(): bool
    {
        $appDebug = function_exists('env') ? filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN) : false;
        $appEnv = function_exists('env') ? strtolower((string) env('APP_ENV', 'local')) : 'local';

        return $appDebug && in_array($appEnv, ['local', 'dev', 'development'], true);
    }

    /**
     * Sanitize session ID to safe characters.
     */
    public static function sanitizeSessionId(?string $sessionId): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_\-]/', '', trim((string) $sessionId));
        return !empty($clean) ? $clean : 'CASA-TEST-DEFAULT';
    }

    /**
     * Get the directory path for testing logs.
     */
    public static function getLogDirectory(): string
    {
        $dir = realpath(self::LOG_DIR);
        if (!$dir) {
            $dir = self::LOG_DIR;
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
        return $dir;
    }

    /**
     * Get the file path for a session log.
     */
    public static function getSessionFilePath(string $sessionId): string
    {
        $safeId = self::sanitizeSessionId($sessionId);
        $dir = self::getLogDirectory();
        return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeId . '.jsonl';
    }

    /**
     * Write a structured log entry into the session's JSONL file.
     */
    public static function writeEntry(string $sessionId, array $entry): bool
    {
        if (!self::isDev()) {
            return false;
        }

        try {
            $safeId = self::sanitizeSessionId($sessionId);
            $filePath = self::getSessionFilePath($safeId);

            // Ensure timestamp exists in IST
            if (empty($entry['timestamp'])) {
                $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata'));
                $entry['timestamp'] = $now->format('Y-m-d\TH:i:s.vP');
            }

            $entry['session_id'] = $safeId;
            $sanitized = self::sanitizeData($entry);

            $line = json_encode($sanitized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
            return (bool) @file_put_contents($filePath, $line, FILE_APPEND | LOCK_EX);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Log a backend PHP exception correlated with the current test session.
     */
    public static function logBackendException(Throwable $e, string $refId = '', ?string $correlationId = null): bool
    {
        if (!self::isDev()) {
            return false;
        }

        $sessionId = $_SERVER['HTTP_X_TEST_SESSION_ID'] ?? ($_COOKIE['casa_qa_session_id'] ?? null);
        if (!$sessionId) {
            return false;
        }

        $corrId = $correlationId ?? ($_SERVER['HTTP_X_CORRELATION_ID'] ?? 'CORR-BACKEND-' . substr(bin2hex(random_bytes(4)), 0, 8));

        $category = 'PhpException';
        if ($e instanceof \PDOException || str_contains($e->getMessage(), 'SQLSTATE')) {
            $category = 'DatabaseError';
        }

        $entry = [
            'seq'            => -1, // Backend flag
            'action_type'    => 'PHP_EXCEPTION',
            'url'            => $_SERVER['REQUEST_URI'] ?? '',
            'referrer'       => $_SERVER['HTTP_REFERER'] ?? null,
            'element'        => null,
            'api'            => [
                'endpoint' => parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH),
                'method'   => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            ],
            'status_code'    => 500,
            'duration_ms'    => null,
            'error'          => [
                'category' => $category,
                'class'    => get_class($e),
                'message'  => self::sanitizeMessage($e->getMessage()),
                'file'     => self::cleanFilePath($e->getFile()),
                'line'     => $e->getLine(),
                'ref_id'   => $refId,
            ],
            'correlation_id' => $corrId,
        ];

        return self::writeEntry($sessionId, $entry);
    }

    /**
     * Read recent entries for a session.
     */
    public static function getRecentLogs(string $sessionId, int $limit = 200): array
    {
        if (!self::isDev()) {
            return [];
        }

        $filePath = self::getSessionFilePath($sessionId);
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [];
        }

        $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return [];
        }

        if (count($lines) > $limit) {
            $lines = array_slice($lines, -$limit);
        }

        $logs = [];
        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $logs[] = $decoded;
            }
        }

        return $logs;
    }

    /**
     * List all saved test sessions.
     */
    public static function listSessions(): array
    {
        if (!self::isDev()) {
            return [];
        }

        $dir = self::getLogDirectory();
        if (!is_dir($dir)) {
            return [];
        }

        $files = glob(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.jsonl');
        $sessions = [];

        foreach ($files ?: [] as $file) {
            $id = basename($file, '.jsonl');
            $sessions[] = [
                'session_id' => $id,
                'size_bytes' => filesize($file),
                'updated_at' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        usort($sessions, fn($a, $b) => strcmp($b['updated_at'], $a['updated_at']));
        return $sessions;
    }

    /**
     * Recursively sanitize data array removing sensitive fields.
     */
    private static function sanitizeData(mixed $data): mixed
    {
        if (!is_array($data)) {
            return is_string($data) ? self::sanitizeMessage($data) : $data;
        }

        $cleaned = [];
        foreach ($data as $key => $val) {
            $keyLower = strtolower((string) $key);
            $isSensitive = false;
            foreach (self::SENSITIVE_KEYS as $sensitive) {
                if (str_contains($keyLower, $sensitive)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $cleaned[$key] = '[REDACTED]';
            } else {
                $cleaned[$key] = self::sanitizeData($val);
            }
        }
        return $cleaned;
    }

    /**
     * Scrub sensitive values from string messages (passwords, tokens, query params).
     */
    private static function sanitizeMessage(string $msg): string
    {
        // Strip common query string tokens
        $pattern = '/([?&](?:password|token|csrf|key|secret)=)[^&#\s]+/i';
        $cleaned = preg_replace($pattern, '$1[REDACTED]', $msg) ?? $msg;

        return mb_substr($cleaned, 0, 500);
    }

    /**
     * Strip absolute path prefixes for clean readability.
     */
    private static function cleanFilePath(string $fullPath): string
    {
        $base = realpath(__DIR__ . '/../../../../');
        if ($base && str_starts_with($fullPath, $base)) {
            return ltrim(substr($fullPath, strlen($base)), '/\\');
        }
        return basename($fullPath);
    }
}
