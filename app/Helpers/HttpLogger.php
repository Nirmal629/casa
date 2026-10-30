<?php

declare(strict_types=1);

namespace App\Helpers;

class HttpLogger
{
    private static bool $initialized = false;
    private static float $startTime = 0.0;
    private static array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'pass',
        'pwd',
        'token',
        'access_token',
        'refresh_token',
        'auth_token',
        'authorization',
        'cookie',
        'phpsessid',
        'session_id',
        'csrf_token',
        '_token',
        'api_key',
        'secret',
        'private_key',
        'smtp_pass',
        'db_pass',
        'db_password'
    ];

    // Initialize request buffering and shutdown logger if debug/dev mode is active.
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        // Skip background CLI scripts (unless running via dev web server)
        if (php_sapi_name() === 'cli' && empty($_SERVER['REQUEST_METHOD'])) {
            return;
        }

        $appDebug = filter_var(env('APP_DEBUG', true), FILTER_VALIDATE_BOOLEAN);
        $appEnv = strtolower((string) env('APP_ENV', 'local'));
        $isDev = $appDebug || in_array($appEnv, ['local', 'dev', 'development', 'staging', 'stage'], true);

        if (!$isDev) {
            return;
        }

        self::$initialized = true;
        self::$startTime = microtime(true);

        // Start output buffering to capture response body safely
        if (ob_get_level() === 0) {
            ob_start();
        }

        // Register shutdown function to log request & response on completion
        register_shutdown_function([self::class, 'log']);
    }

    //Called on request termination via register_shutdown_function.

    public static function log(): void
    {
        $durationMs = round((microtime(true) - self::$startTime) * 1000, 2);
        $statusCode = http_response_code() ?: 200;

        // Capture response buffer contents without discarding
        $responseBody = '';
        if (ob_get_level() > 0) {
            $responseBody = (string) ob_get_contents();
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Extract Request Data
        $requestData = self::captureRequestData();
        $formattedRequest = self::formatData($requestData);

        // Extract Response Data
        $formattedResponse = self::formatResponseData($responseBody);

        // Format Terminal Box
        $isError = $statusCode >= 400;
        $headerTitle = $isError ? 'HTTP ERROR' : 'HTTP REQUEST';

        $output = "\n" . str_repeat('═', 44) . "\n";
        $output .= "{$headerTitle}\n";
        $output .= str_repeat('═', 44) . "\n";
        $output .= "{$method} {$path}\n";
        $output .= "Status: {$statusCode}\n";
        $output .= "Duration: {$durationMs} ms\n\n";

        if (!empty($formattedRequest) && $formattedRequest !== '{}' && $formattedRequest !== '[]') {
            $output .= "Request:\n{$formattedRequest}\n\n";
        }

        if (!empty($formattedResponse)) {
            $label = $isError ? 'Error / Response:' : 'Response:';
            $output .= "{$label}\n{$formattedResponse}\n";
        }

        $output .= str_repeat('═', 44) . "\n";

        // Write directly to stderr/stdout so PHP built-in server shows it in terminal
        if (defined('STDERR') && is_resource(STDERR)) {
            fwrite(STDERR, $output);
        } else {
            file_put_contents('php://stderr', $output);
        }
    }

    //Capture and sanitize POST, GET, or raw JSON body input.

    private static function captureRequestData(): array
    {
        $data = [];

        if (!empty($_GET)) {
            $data['query'] = self::sanitize($_GET);
        }

        if (!empty($_FILES)) {
            $filesInfo = [];
            foreach ($_FILES as $key => $file) {
                $filesInfo[$key] = [
                    'name' => $file['name'] ?? '',
                    'type' => $file['type'] ?? '',
                    'size' => ($file['size'] ?? 0) . ' bytes',
                ];
            }
            $data['files'] = $filesInfo;
        }

        if (!empty($_POST)) {
            $data['body'] = self::sanitize($_POST);
        } else {
            $rawInput = @file_get_contents('php://input');
            if (!empty($rawInput)) {
                $decoded = @json_decode($rawInput, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data['body'] = self::sanitize($decoded);
                } else if (strlen($rawInput) < 1000) {
                    $data['body'] = '[Raw Input] ' . $rawInput;
                } else {
                    $data['body'] = '[Raw Input truncated - ' . strlen($rawInput) . ' bytes]';
                }
            }
        }

        return $data;
    }

    //Format and sanitize response body.

    private static function formatResponseData(string $body): string
    {
        if (trim($body) === '') {
            return '[Empty Response]';
        }

        $length = strlen($body);
        if ($length > 50000) {
            return sprintf('[Response Body truncated — %d bytes total]', $length);
        }

        // Try JSON decode
        $decoded = @json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $sanitized = self::sanitize($decoded);
            $json = json_encode($sanitized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (strlen($json) > 2000) {
                return substr($json, 0, 2000) . "\n... [Response JSON truncated (" . strlen($json) . " bytes total)]";
            }
            return $json;
        }

        // HTML or Plain text response truncation
        $cleanBody = trim(strip_tags($body));
        if (strlen($cleanBody) > 500) {
            return substr($cleanBody, 0, 500) . "... [Response HTML/Text truncated]";
        }

        return $cleanBody ?: '[HTML/Binary Output]';
    }

    //Recursively sanitize sensitive keys in arrays.

    public static function sanitize(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        $clean = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);
            if (in_array($lowerKey, self::$sensitiveKeys, true)) {
                $clean[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $clean[$key] = self::sanitize($value);
            } elseif (is_string($value) && (str_starts_with($value, '{') || str_starts_with($value, '['))) {
                // Nested JSON string
                $jsonDecoded = @json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonDecoded)) {
                    $clean[$key] = json_encode(self::sanitize($jsonDecoded));
                } else {
                    $clean[$key] = $value;
                }
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    // Format data to pretty-printed JSON string.

    private static function formatData(array $data): string
    {
        if (empty($data)) {
            return '';
        }
        return (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
