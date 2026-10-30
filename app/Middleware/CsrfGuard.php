<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Session;

class CsrfGuard
{
    // Centralized CSRF Middleware Handler.
    //Verifies CSRF token for mutating HTTP methods (POST, PUT, PATCH, DELETE).

    public static function handle(): bool
    {
        if (php_sapi_name() === 'cli') {
            return true;
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_XSRF_TOKEN'] ?? null;

            if (!$token && function_exists('getallheaders')) {
                $headers = getallheaders();
                if (is_array($headers)) {
                    $token = $headers['X-CSRF-TOKEN'] ?? $headers['x-csrf-token'] ?? $headers['X-Csrf-Token'] ?? $headers['X-XSRF-TOKEN'] ?? null;
                }
            }

            if (!$token) {
                $rawInput = @file_get_contents('php://input');
                if (!empty($rawInput)) {
                    $json = @json_decode($rawInput, true);
                    if (is_array($json) && !empty($json['csrf_token'])) {
                        $token = $json['csrf_token'];
                    }
                }
            }

            if (!Session::verifyCsrfToken($token)) {
                $isJson = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
                    || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));

                if ($isJson) {
                    http_response_code(403);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Security token validation failed (CSRF mismatch). Please refresh the page and try again.',
                        'error' => 'CsrfTokenMismatch'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                Session::setFlash('login_error', 'Security validation failed. Please try again.');
                $_SESSION['login_error'] = 'Security validation failed. Please try again.';

                $referer = '/'; // Do not redirect to an untrusted Referer header.
                header("Location: {$referer}");
                exit;
            }
        }

        return true;
    }
}
