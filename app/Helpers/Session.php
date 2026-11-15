<?php

declare(strict_types=1);
namespace App\Helpers;

use App\Models\UserModel;

/** Modern Decoupled Session & Auth Helper - Class: App\Helpers\Session */
class Session
{
    /** Start session safely with application-level session isolation. */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_trans_sid', '0');
            if (!headers_sent()) {
                $sessionName = function_exists('env')
                    ? (string) env('APP_SESSION_NAME', 'CASA_STAGING_SESSION')
                    : 'CASA_STAGING_SESSION';
                session_name($sessionName);

                $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

                session_set_cookie_params([
                    'lifetime' => 0,
                    'path' => '/',
                    'domain' => '',
                    'secure' => $isSecure,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
            if (!session_start()) {
                throw new \RuntimeException('Unable to start session.');
            }
        }
        if (!empty($_SESSION['user_id'])) {
            $now = time();
            $idle = max(60, (int) (function_exists('env') ? env('SESSION_IDLE_SECONDS', 1800) : 1800));
            $absolute = max($idle, (int) (function_exists('env') ? env('SESSION_ABSOLUTE_SECONDS', 43200) : 43200));
            $created = (int) ($_SESSION['_authenticated_at'] ?? $now);
            $last = (int) ($_SESSION['_last_activity'] ?? $now);
            if ($now - $last >= $idle || $now - $created >= $absolute) {
                $_SESSION = [];
                if (!session_regenerate_id(true)) throw new \RuntimeException('Unable to renew expired session.');
            } else {
                $_SESSION['_authenticated_at'] = $created;
                $_SESSION['_last_activity'] = $now;
            }
        }
    }

    /** Shared login boundary for password and remember-me authentication. */
    public static function establishUser(array $user, ?string $identity = null): void
    {
        self::start();
        if ((int) ($user['ID'] ?? 0) <= 0) {
            throw new \InvalidArgumentException('A valid user is required.');
        }
        if (!session_regenerate_id(true)) {
            throw new \RuntimeException('Unable to renew session.');
        }
        // Remove privileges and CSRF state belonging to the previous identity.
        $_SESSION = [];
        foreach (['user_id' => 'ID', 'name' => 'NAME', 'username' => 'EMAIL',
            'usertype' => 'USERTYPE', 'gender' => 'GENDER', 'vlevel' => 'VERIFIED_LEVEL',
            'level' => 'LEVEL', 'country' => 'COUNTRY', 'province' => 'PROVINCE',
            'city' => 'CITY', 'games' => 'GAMES', 'profileImage' => 'PROFILE_IMAGE'] as $key => $column) {
            $_SESSION[$key] = $user[$column] ?? '';
        }
        $_SESSION['user_id'] = (int) $user['ID'];
        $_SESSION['username'] = $identity ?? ($user['EMAIL'] ?? '');
        $_SESSION['security_version'] = self::computeSecurityVersion($user);
        $_SESSION['_authenticated_at'] = time();
        $_SESSION['_last_activity'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /** Compute DB-backed security version string for session binding. */
    public static function computeSecurityVersion(array $user): string
    {
        if (!empty($user['SECURITY_VERSION'])) {
            return (string) $user['SECURITY_VERSION'];
        }
        $hashSource = (string) ($user['PASSWORD_HASH'] ?? ($user['PASSWORD'] ?? 'v1'));
        return substr(hash('sha256', $hashSource), 0, 16);
    }

    /** Get a session value. */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /** Set a session value. */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /** Check if a session key exists. */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /** Remove a session key. */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /** Set a flash message for Controllers -> Views. */
    public static function setFlash(string $key, mixed $message): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $message;
    }

    /** Get and clear a flash message. */
   public static function getFlash(string $key,mixed $default = null): mixed {
        self::start();

        if (!isset($_SESSION['_flash'][$key])) {
            return $default;
        }

        $message = $_SESSION['_flash'][$key];

        unset($_SESSION['_flash'][$key]);

        return $message;
    }

    /** Get current logged-in user raw array with in-memory static request caching via UserModel. */
    public static function getCurrentUser($conn = null): ?array
    {
        static $cachedUser = null;
        static $cachedUserId = null;

        $userId = self::getCurrentUserId();

        if ($userId === null) {
            $cachedUser = null;
            $cachedUserId = null;

            return null;
        }

        if ($cachedUser !== null && $cachedUserId === $userId) {
            return $cachedUser;
        }

        $userModel = new UserModel();

        $user = $userModel->findById($userId);

        $cachedUserId = $userId;
        $cachedUser = $user;

        return $user;
    }

    /** Get normalized user object with per-request in-memory static caching. */
    public static function user($conn = null): ?object
    {
        static $cachedNormalizedUser = null;
        static $cachedUserId = null;

        $rawUser = self::getCurrentUser($conn);
        if (!$rawUser) {
            $cachedNormalizedUser = null;
            $cachedUserId = null;
            return null;
        }

        $currentId = (int) ($rawUser['ID'] ?? 0);
        if ($cachedNormalizedUser !== null && $cachedUserId === $currentId) {
            return $cachedNormalizedUser;
        }

        $cachedUserId = $currentId;
        $cachedNormalizedUser = self::normalizeUserData($rawUser);
        return $cachedNormalizedUser;
    }

    /** Get current user ID. */
    public static function getCurrentUserId(): ?int
    {
        self::start();
        $id = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? null : $id;
    }

    /** Alias for getCurrentUserId. */
    public static function userId(): ?int
    {
        return self::getCurrentUserId();
    }

    /** Get current user type. */
    public static function getCurrentUserType(): ?string
    {
        self::start();
        return isset($_SESSION['usertype']) ? (string) $_SESSION['usertype'] : null;
    }

    public static function userType(): ?string
    {
        return self::getCurrentUserType();
    }

    /** Require user to be logged in. */
    public static function requireLogin(string $redirect = 'index.php'): void
    {
        if (!\App\Middleware\AuthGuard::check()) {
            header("Location: $redirect");
            exit;
        }
    }

    /** Require specific user type(s). */
    public static function requireUserType(array $allowedTypes, string $redirect = 'index.php'): void
    {
        self::requireLogin($redirect);
        $currentRole = self::normalizeRole(self::getCurrentUserType());
        $normalizedAllowed = array_map([self::class, 'normalizeRole'], $allowedTypes);
        if (!in_array($currentRole, $normalizedAllowed, true)) {
            header("Location: $redirect");
            exit;
        }
    }

    /** Regenerate session ID safely after login to prevent session fixation attacks. */
    public static function regenerateId(bool $deleteOldSession = true): bool
    {
        self::start();
        if (session_status() === PHP_SESSION_ACTIVE) {
            return session_regenerate_id($deleteOldSession);
        }
        return false;
    }

    /** Logout user and destroy session safely. */
    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'] ?? '/',
                    'domain' => $params['domain'] ?? '',
                    'secure' => $params['secure'] ?? false,
                    'httponly' => $params['httponly'] ?? true,
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }
        session_destroy();
    }

    /** Normalize role string to lowercase. */
    public static function normalizeRole(?string $rawRole): string
    {
        return strtolower(trim((string) ($rawRole ?? 'player')));
    }

    /** Normalize user profile object from DB or Session with case-insensitive key resolution. */
    public static function normalizeUserData(array $user): object
    {
        self::start();
        $role = $user['USERTYPE'] ?? $user['usertype'] ?? $_SESSION['usertype'] ?? 'Player';

        return (object) [
            'id' => (int) ($user['ID'] ?? $user['id'] ?? $_SESSION['user_id'] ?? 0),
            'name' => $user['NAME'] ?? $user['name'] ?? $_SESSION['name'] ?? '',
            'email' => $user['EMAIL'] ?? $user['email'] ?? $_SESSION['email'] ?? '',
            'contact' => $user['CONTACT'] ?? $user['contact'] ?? $user['WHATSAPP_NUMBER'] ?? $user['whatsapp_number'] ?? $_SESSION['contact'] ?? '',
            'dob' => $user['DOB'] ?? $user['dob'] ?? '',
            'gender' => $user['GENDER'] ?? $user['gender'] ?? '',
            'level' => $user['LEVEL'] ?? $user['level'] ?? '',
            'country' => $user['COUNTRY'] ?? $user['country'] ?? null,
            'province' => $user['PROVINCE'] ?? $user['province'] ?? null,
            'city' => $user['CITY'] ?? $user['city'] ?? null,
            'area' => $user['AREA'] ?? $user['area'] ?? null,
            'rawRole' => $role,
            'role' => self::normalizeRole($role),
            'profileImage' => $user['PROFILE_IMAGE'] ?? $user['profile_image'] ?? $_SESSION['profileImage'] ?? '',
            'callPermission' => strtolower((string) ($user['CALL_PERMISSION'] ?? $user['call_permission'] ?? 'yes')),
            'textPermission' => strtolower((string) ($user['TEXT_PERMISSION'] ?? $user['text_permission'] ?? 'yes')),
            'chatPermission' => strtolower((string) ($user['CHAT_PERMISSION'] ?? $user['chat_permission'] ?? 'yes')),
        ];
    }

    /** Generate initials from user name (Multibyte-safe for UTF-8). */
    public static function getUserInitials(string $userName): string
    {
        $trimmed = trim($userName);
        if ($trimmed === '') {
            return 'U';
        }

        $words = preg_split('/\s+/u', $trimmed);
        if (\is_array($words) && \count($words) >= 2) {
            $first = $words[0];
            $last = end($words);
            if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
                return mb_strtoupper(mb_substr($first, 0, 1, 'UTF-8') . mb_substr($last, 0, 1, 'UTF-8'), 'UTF-8');
            }
            return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
        }

        if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
            return mb_strtoupper(mb_substr($trimmed, 0, 2, 'UTF-8'), 'UTF-8');
        }
        return strtoupper(substr($trimmed, 0, 2));
    }

    /** CSRF token generator. */
    public static function getCsrfToken(): string
    {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** CSRF token generator alias. */
    public static function csrfToken(): string
    {
        return self::getCsrfToken();
    }

    /** CSRF token verification. */
    public static function verifyCsrfToken(mixed $token): bool
    {
        self::start();
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        return is_string($token) && is_string($sessionToken) && $token !== '' && $sessionToken !== '' && hash_equals($sessionToken, $token);
    }

    /** Check product listing permission. */
    public static function canViewProductListing(): bool
    {
        self::start();
        $userType = self::normalizeRole($_SESSION['usertype'] ?? '');
        if (empty($_SESSION['user_id']) || $userType === 'player' || $userType === '') {
            return true;
        }

        if ($userType === 'host' || $userType === 'h') {
            $level = strtoupper(trim((string) ($_SESSION['level'] ?? $_SESSION['vlevel'] ?? '')));
            $role = strtoupper(trim((string) ($_SESSION['role'] ?? '')));
            return !in_array($level, ['A', 'R'], true) && !in_array($role, ['A', 'R'], true);
        }

        return false;
    }

    /** Rate-limiting helper to prevent brute-force attacks. */
    public static function checkRateLimit(string $key, int $maxAttempts = 5, int $decaySeconds = 60): bool
    {
        self::start();
        $now = time();
        $limitData = $_SESSION['_rate_limits'][$key] ?? ['attempts' => 0, 'reset_at' => $now + $decaySeconds];

        if ($now > $limitData['reset_at']) {
            $limitData = ['attempts' => 0, 'reset_at' => $now + $decaySeconds];
        }

        $limitData['attempts']++;
        $_SESSION['_rate_limits'][$key] = $limitData;

        return $limitData['attempts'] <= $maxAttempts;
    }

    /** Clear rate-limiting records for a specific key. */
    public static function clearRateLimit(string $key): void
    {
        self::start();
        unset($_SESSION['_rate_limits'][$key]);
    }
}
