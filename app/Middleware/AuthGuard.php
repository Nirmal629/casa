<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Models\UserModel;
use App\Config\Database;
use App\Core\Token;
use Throwable;

class AuthGuard
{
    /**
     * Check if user is currently logged in or auto-logged in via remember cookie.
     */
    public static function check(): bool
    {
        \App\Helpers\Session::start();

        if (!empty($_SESSION['user_id'])) {
            try {
                $user = (new UserModel(Database::getPdo()))->findAuthUserById((int) $_SESSION['user_id']);
                if (!$user) {
                    $_SESSION = [];
                    self::clearRememberCookie();
                    return false;
                }

                // Validate DB-backed security version on every authenticated request
                $expectedVersion = \App\Helpers\Session::computeSecurityVersion($user);
                $sessionVersion = (string) ($_SESSION['security_version'] ?? '');
                if ($sessionVersion !== '' && !hash_equals($expectedVersion, $sessionVersion)) {
                    $_SESSION = [];
                    self::clearRememberCookie();
                    return false;
                }
                if ($sessionVersion === '') {
                    $_SESSION['security_version'] = $expectedVersion;
                }

                $_SESSION['usertype'] = $user['USERTYPE'];
                $_SESSION['level'] = $user['LEVEL'] ?? '';
                $_SESSION['vlevel'] = $user['VERIFIED_LEVEL'] ?? '';
                return true;
            } catch (Throwable $e) {
                error_log('AuthGuard: account validation unavailable.');
                return false;
            }
        }

        return self::attemptRememberLogin();
    }

    /**
     * Get logged-in user ID or null.
     */
    public static function id(): ?int
    {
        if (self::check()) {
            return (int) $_SESSION['user_id'];
        }
        return null;
    }

    /**
     * Get currently logged-in user details array from session.
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id'           => $_SESSION['user_id'] ?? null,
            'name'         => $_SESSION['name'] ?? '',
            'username'     => $_SESSION['username'] ?? '',
            'usertype'     => $_SESSION['usertype'] ?? '',
            'gender'       => $_SESSION['gender'] ?? '',
            'vlevel'       => $_SESSION['vlevel'] ?? '',
            'level'        => $_SESSION['level'] ?? '',
            'country'      => $_SESSION['country'] ?? '',
            'province'     => $_SESSION['province'] ?? '',
            'city'         => $_SESSION['city'] ?? '',
            'games'        => $_SESSION['games'] ?? '',
            'profileImage' => $_SESSION['profileImage'] ?? '',
        ];
    }

    /**
     * Require authenticated session. Redirects or returns 401 if unauthenticated.
     */
    public static function authenticate(string $redirectUrl = '/'): array
    {
        if (!self::check()) {
            self::denyUnauthorized(401, $redirectUrl, 'Authentication required.');
        }

        return self::user() ?? [];
    }

    /**
     * Require authorized role(s). Redirects or returns 403 if role mismatch.
     */
    public static function authorize(string|array $allowedRoles, string $redirectUrl = '/'): array
    {
        $user = self::authenticate($redirectUrl);
        $userType = trim($user['usertype'] ?? '');

        $rolesArray = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
        $normalizedRoles = array_map('strtolower', array_map('trim', $rolesArray));

        if (!in_array(strtolower($userType), $normalizedRoles, true)) {
            if (self::isJsonRequest()) {
                self::sendJsonResponse(403, 'Forbidden access. Insufficient permissions.');
            }
            self::redirectByRole($userType, 'Unauthorized access.');
        }

        return $user;
    }

    /**
     * Guest guard: Ensures user is NOT logged in.
     */
    public static function guest(string $redirectUrl = ''): void
    {
        if (self::check()) {
            $userType = trim($_SESSION['usertype'] ?? '');
            self::redirectByRole($userType);
        }
    }

    /**
     * Middleware handler entry point for routing.
     */
    public static function handle(string|array $roles = []): bool
    {
        if (empty($roles)) {
            self::authenticate();
        } else {
            self::authorize($roles);
        }
        return true;
    }

    /**
     * Clear invalid/expired remember token cookie.
     */
    public static function clearRememberCookie(): void
    {
        \App\Helpers\RememberMe::clearCookie();
    }

    /**
     * Attempt automatic login using the DB-backed selector/validator remember token.
     * Database is the authoritative source for user details and token validation.
     */
    protected static function attemptRememberLogin(): bool
    {
        $userId = \App\Helpers\RememberMe::verify();
        if ($userId === null || $userId <= 0) {
            return false;
        }

        try {
            // Fetch authoritative user record from DB
            $db        = Database::getPdo();
            $userModel = new UserModel($db);
            $user      = $userModel->findAuthUserById($userId);

            if ($user && !empty($user['ID']) && $user['LOG_STATUS'] === 'Y' && $user['DEL_STATUS'] === 'N') {
                \App\Helpers\Session::establishUser($user);

                return true;
            }

            \App\Helpers\RememberMe::clearCookie();
        } catch (Throwable $e) {
            error_log("AuthGuard remember token error: " . $e->getMessage());
            \App\Helpers\RememberMe::clearCookie();
        }

        return false;
    }

    /**
     * Redirect user according to their role.
     */
    protected static function redirectByRole(string $userType, string $message = ''): void
    {
        if (!empty($message)) {
            $_SESSION['auth_error'] = $message;
        }

        $type = strtolower(trim($userType));
        $routes = [
            'host'    => '/host/host-dashboard.php',
            'trainer' => '/host/host-dashboard.php',
            'player'  => '/player/player-hub.php'
        ];

        $location = $routes[$type] ?? '/index.php';
        header("Location: {$location}");
        exit;
    }

    /**
     * Handle unauthenticated response (401 or redirect).
     */
    protected static function denyUnauthorized(int $code, string $redirectUrl, string $message): void
    {
        if (self::isJsonRequest()) {
            self::sendJsonResponse($code, $message);
        }

        $_SESSION['login_error'] = $message;
        header("Location: {$redirectUrl}");
        exit;
    }

    /**
     * Check if current request expects JSON response.
     */
    protected static function isJsonRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $uri    = $_SERVER['REQUEST_URI'] ?? '';
        return str_contains($accept, 'application/json') || str_contains($uri, '/api/');
    }

    /**
     * Send JSON error response and exit.
     */
    protected static function sendJsonResponse(int $code, string $message): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
}
