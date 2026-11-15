<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Core\Controller;
use App\Helpers\RememberMe;
use App\Helpers\Session;
use App\Models\UserModel;
use PDO;
use PDOException;

class AuthController extends Controller
{
    protected PDO $db;
    protected UserModel $userModel;

    public function __construct(?UserModel $userModel = null)
    {
        parent::__construct();
        $this->db = Database::getPdo();
        $this->userModel = $userModel ?? new UserModel($this->db);
    }

    public function login(): void
    {
        Session::start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /");
            exit;
        }

        $clientIp = $this->getClientIp();

        // Layer 1: Request Rate Limiting (IP Level)
        $ipLimit = (int) (function_exists('env') ? env('LOGIN_IP_RATE_LIMIT', 20) : 20);
        $ipWindow = (int) (function_exists('env') ? env('LOGIN_IP_RATE_WINDOW', 60) : 60);

        try {
            $retryAfter = \App\Helpers\LoginThrottle::consume('req_ip:' . $clientIp, $ipLimit, $ipWindow);
        } catch (\Throwable $e) {
            http_response_code(503);
            exit('Login service temporarily unavailable.');
        }

        if ($retryAfter > 0) {
            http_response_code(429);
            header('Retry-After: ' . $retryAfter);
            if ($this->isJsonRequest()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'error' => 'Too many login requests. Please try again later.',
                    'retry_after' => $retryAfter,
                ], JSON_UNESCAPED_SLASHES);
                exit;
            }
            exit("Too many login requests from this network. Please try again in {$retryAfter} seconds.");
        }

        // CSRF Protection
        $submittedCsrf = (string) ($_POST['csrf_token'] ?? '');
        if (!Session::verifyCsrfToken($submittedCsrf)) {
            $this->logActivity(0, 'SECURITY_CSRF_FAIL', 'Login CSRF validation failed');
            $_SESSION['login_error'] = 'Security validation failed. Please try again.';
            header("Location: /");
            exit;
        }

        // Input Validation & Sanitization

        $rawIdentity = $_POST['username'] ?? '';
        $rawPassword = $_POST['password'] ?? '';

        $identity = is_string($rawIdentity) ? strtolower(trim($rawIdentity)) : '';
        $password = is_string($rawPassword) ? $rawPassword : '';

        if ($identity === '' || $password === '' || strlen($identity) > 255 || strlen($password) > 255) {
            $_SESSION['login_error'] = 'Please enter both username/email and password.';
            header("Location: /");
            exit;
        }

        // Layer 2: Failed Login Protection (Pre-Auth Lockout Check)
        $identityHash = hash('sha256', $identity);
        $ipAccKey = 'fail_ip_acc:' . $clientIp . ':' . $identityHash;
        $accKey   = 'fail_acc:' . $identityHash;
        $ipKey    = 'fail_ip:' . $clientIp;

        $maxFailedIpAcc = (int) (function_exists('env') ? env('LOGIN_MAX_FAILED_IP_ACC', 5) : 5);
        $maxFailedAcc   = (int) (function_exists('env') ? env('LOGIN_MAX_FAILED_ACC', 10) : 10);
        $maxFailedIp    = (int) (function_exists('env') ? env('LOGIN_MAX_FAILED_IP', 15) : 15);
        $lockoutSeconds = (int) (function_exists('env') ? env('LOGIN_LOCKOUT_SECONDS', 300) : 300);

        $lockIpAcc = \App\Helpers\LoginThrottle::check($ipAccKey, $maxFailedIpAcc);
        $lockAcc   = \App\Helpers\LoginThrottle::check($accKey, $maxFailedAcc);
        $lockIp    = \App\Helpers\LoginThrottle::check($ipKey, $maxFailedIp);
        $activeLock = max($lockIpAcc, $lockAcc, $lockIp);

        if ($activeLock > 0) {
            $lockMinutes = (int) ceil($activeLock / 60);
            $_SESSION['login_error'] = "Too many failed login attempts. Account temporarily locked for {$lockMinutes} minute(s).";
            header("Location: /");
            exit;
        }

        // Layer 3: Authenticate Credentials & Account Status
        $user = $this->authenticate($identity, $password);

        if (!$user) {
            $lockIpAccNow = \App\Helpers\LoginThrottle::increment($ipAccKey, $maxFailedIpAcc, $lockoutSeconds);
            $lockAccNow   = \App\Helpers\LoginThrottle::increment($accKey, $maxFailedAcc, $lockoutSeconds);
            $lockIpNow    = \App\Helpers\LoginThrottle::increment($ipKey, $maxFailedIp, $lockoutSeconds);
            $newLock = max($lockIpAccNow, $lockAccNow, $lockIpNow);

            $this->logActivity(0, 'LOGIN_FAILED', 'Failed login attempt for identity hash: ' . substr($identityHash, 0, 8));

            if ($newLock > 0) {
                $lockMinutes = (int) ceil($newLock / 60);
                $_SESSION['login_error'] = "Too many failed login attempts. Account temporarily locked for {$lockMinutes} minute(s).";
            } else {
                $_SESSION['login_error'] = 'Invalid username or password.';
            }

            header("Location: /");
            exit;
        }

        // Authentication Successful
        // Reset failed-attempt counters for this account and IP+account
        \App\Helpers\LoginThrottle::clear($ipAccKey);
        \App\Helpers\LoginThrottle::clear($accKey);

        // Retire any previous persistent login session
        RememberMe::revokeCurrent();

        // Regenerate session ID and establish clean user session
        $this->createSession($user, $identity);

        // Handle Remember Me token
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        $rememberCookieKey = (function_exists('env') ? env('APP_SESSION_NAME', 'CASA_STAGING') : 'CASA_STAGING') . '_REMEMBER_USER';

        if (!empty($_POST['rememberMe'])) {
            $rememberDays = (int) (function_exists('env') ? env('REMEMBER_ME_DAYS', 30) : 30);
            $this->rememberUser($user, $identity, $rememberDays);
        } else {
            // Unset identity prefill cookie if user unchecked Remember Me
            setcookie($rememberCookieKey, '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            unset($_COOKIE[$rememberCookieKey]);
        }

        // Audit Log
        $this->logActivity((int) $user['ID'], 'LOGIN', 'Successful login');

        // Redirect based on authorized USERTYPE
        $roleType = strtolower(trim((string) ($user['USERTYPE'] ?? '')));
        if ($roleType === 'host' || $roleType === 'trainer') {
            header("Location: /host/host-dashboard.php");
        } elseif ($roleType === 'player') {
            $this->syncHomeClubs(
                (int) $user['ID'],
                (string) ($user['COUNTRY'] ?? ''),
                (string) ($user['PROVINCE'] ?? ''),
                (string) ($user['CITY'] ?? ''),
                (string) ($user['GAMES'] ?? '')
            );
            header("Location: /player/player-hub.php");
        } else {
            // Unknown or unauthorized role: NEVER grant host permissions!
            Session::logout();
            $_SESSION['login_error'] = 'Account is not authorized for access.';
            header("Location: /");
        }
        exit;
    }

    /**
     * Authenticate user credentials.
     * Returns user array on success, or false on invalid credentials.
     */
    public function authenticate(string $identity, string $password): array|false
    {
        $identity = strtolower(trim($identity));
        if (empty($identity) || empty($password)) {
            return false;
        }

        $user = $this->userModel->findByUsernameOrEmail($identity);
        if (!$user) {
            // Timing side-channel mitigation: perform dummy verification
            password_verify($password, '$2y$10$cf170138380d60d3d5271uQf7c23aD4pE76Y9gE9Y7Y4f4h8i6g6i');
            return false;
        }

        if (!$this->verifyPassword($password, $user)) {
            return false;
        }

        // Account status check (Layer 3)
        if (($user['LOG_STATUS'] ?? '') !== 'Y' || ($user['DEL_STATUS'] ?? '') !== 'N') {
            return false;
        }

        // Authorized role check (Layer 3)
        $roleType = strtolower(trim((string) ($user['USERTYPE'] ?? '')));
        if (!in_array($roleType, ['player', 'host', 'trainer'], true)) {
            return false;
        }

        // Upgrade legacy password to bcrypt hash automatically if needed
        if (empty($user['PASSWORD_HASH']) || password_needs_rehash((string) $user['PASSWORD_HASH'], PASSWORD_DEFAULT)) {
            $this->migrateLegacyPassword((int) $user['ID'], $password);
        }

        return $user;
    }

    /**
     * Verify password hash against input password.
     */
    public function verifyPassword(string $password, array $user): bool
    {
        // A modern hash is authoritative; never accept a stale legacy password.
        if (!empty($user['PASSWORD_HASH'])) {
            return password_verify($password, (string) $user['PASSWORD_HASH']);
        }
        $legacyPassword = (string) ($user['PASSWORD'] ?? '');
        if ($legacyPassword !== '') {
            if ((password_get_info($legacyPassword)['algoName'] ?? 'unknown') !== 'unknown') {
                return password_verify($password, $legacyPassword);
            }
            return hash_equals($legacyPassword, $password);
        }

        return false;
    }

    //Change user password securely.
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        if ($userId <= 0) {
            return ['success' => false, 'message' => 'Unauthorized.', 'code' => 401];
        }

        if (strlen($newPassword) < 8 || strlen($newPassword) > 72) {
            return ['success' => false, 'message' => 'New password must be between 8 and 72 characters.', 'code' => 400];
        }

        $user = $this->userModel->findById($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.', 'code' => 404];
        }

        if (!$this->verifyPassword($currentPassword, $user)) {
            return ['success' => false, 'message' => 'Current password does not match.', 'code' => 400];
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $success = $this->userModel->updatePasswordHash($userId, $newHash);

        if (!$success) {
            return ['success' => false, 'message' => 'Failed to update password.', 'code' => 500];
        }

        \error_log(sprintf('[AUDIT] User %d changed password. All other sessions revoked.', $userId));
        $this->logActivity($userId, 'PASSWORD_CHANGE', 'Password changed; all other sessions revoked');

        return ['success' => true, 'message' => 'Password changed successfully. All other sessions have been revoked.'];
    }

    //Migrate legacy plain password to bcrypt hash.
    public function migrateLegacyPassword(int $userId, string $password): bool
    {
        $bcryptHash = password_hash($password, PASSWORD_DEFAULT);
        return $this->userModel->updatePasswordHash($userId, $bcryptHash);
    }

    //Initialize user session after successful authentication.
    public function createSession(array $user, string $identity): void
    {
        Session::establishUser($user, $identity);
    }

    //Set secure Remember-Me cookie and store selector/validator in DB.
    public function rememberUser(array $user, string $identity, int $days = 30): void
    {
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
        $cookieName = (function_exists('env') ? env('APP_SESSION_NAME', 'CASA_STAGING') : 'CASA_STAGING') . '_REMEMBER_USER';

        setcookie($cookieName, $identity, [
            'expires' => time() + (86400 * $days),
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        RememberMe::create((int) $user['ID'], $days);
    }

    //Terminate user session and revoke active remember tokens.
    public function logout(): void
    {
        Session::start();

        if (isset($_SESSION['user_id'])) {
            RememberMe::revokeAllUserTokens((int) $_SESSION['user_id']);
        } else {
            RememberMe::revokeCurrent();
        }

        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
        $cookieName = (function_exists('env') ? env('APP_SESSION_NAME', 'CASA_STAGING') : 'CASA_STAGING') . '_REMEMBER_USER';

        setcookie($cookieName, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        Session::logout();
        header('Location: /');
        exit;
    }

    protected function handleFailedAttempt(string $attemptKey, string $lockoutKey): void
    {
        $attempts = ($_SESSION[$attemptKey] ?? 0) + 1;
        $_SESSION[$attemptKey] = $attempts;

        $maxAttempts = (int) env('LOGIN_MAX_ATTEMPTS', '5');
        $lockoutSeconds = (int) env('LOGIN_LOCKOUT_SECONDS', '900');

        if ($attempts >= $maxAttempts) {
            $_SESSION[$lockoutKey] = time() + $lockoutSeconds;
            $lockoutMinutes = (int) ceil($lockoutSeconds / 60);
            $_SESSION['login_error'] = "Too many failed login attempts. Account temporarily locked for {$lockoutMinutes} minutes.";
        } else {
            $_SESSION['login_error'] = 'Invalid username or password.';
        }
    }

    //Resolves client IP address securely.
    //Proxy headers (e.g. X-Forwarded-For) are ONLY trusted if the remote address
    //is explicitly defined in the TRUSTED_PROXIES environment variable.
    public function getClientIp(): string
    {
        $remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $remoteIp = filter_var($remoteAddr, FILTER_VALIDATE_IP) ? $remoteAddr : '127.0.0.1';

        $trustedConfig = function_exists('env') ? (string) env('TRUSTED_PROXIES', '') : '';
        if ($trustedConfig === '') {
            return $remoteIp;
        }

        $trustedProxies = array_filter(array_map('trim', explode(',', $trustedConfig)));
        if (!in_array($remoteIp, $trustedProxies, true)) {
            return $remoteIp;
        }

        $proxyHeaders = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'];
        foreach ($proxyHeaders as $header) {
            if (!empty($_SERVER[$header]) && is_string($_SERVER[$header])) {
                $ips = array_map('trim', explode(',', $_SERVER[$header]));
                foreach ($ips as $candidate) {
                    if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                        return $candidate;
                    }
                }
            }
        }

        return $remoteIp;
    }

    //Determine if request expects a JSON response.
    protected function isJsonRequest(): bool
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $requestedWith = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');

        return str_contains($accept, 'application/json')
            || strtolower($requestedWith) === 'xmlhttprequest'
            || str_contains($contentType, 'application/json')
            || str_contains($uri, '/api/');
    }

    private function logActivity(int $userId, string $activityType, string $description): void
    {
        try {
            $ip = $this->getClientIp();

            $stmt = $this->db->prepare("INSERT INTO ca_player_logs (USER_ID, ACTIVITY_TYPE, DESCRIPTION, IP_ADDRESS) VALUES (:user_id, :activity_type, :description, :ip)");
            $stmt->execute([
                ':user_id' => $userId,
                ':activity_type' => $activityType,
                ':description' => $description,
                ':ip' => $ip
            ]);
        } catch (PDOException $e) {
            error_log('[AuthController::logActivity] Database error: ' . $e->getMessage());
        }
    }

    private function syncHomeClubs(int $playerId, string $country, string $province, string $city, string $games): void
    {
        $country = trim($country);
        $city = trim($city);
        $province = trim($province);

        if (empty($country) || empty($city)) {
            return;
        }

        try {
            $stmt = $this->db->prepare("
                SELECT id, host_id, game_type 
                FROM ca_clubs 
                WHERE join_type = 'H' 
                  AND status = 'Active' 
                  AND LOWER(country) = LOWER(:country) 
                  AND LOWER(province) = LOWER(:province) 
                  AND LOWER(city) = LOWER(:city)
            ");

            $stmt->execute([
                ':country' => $country,
                ':province' => $province,
                ':city' => $city
            ]);

            $clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($clubs)) {
                return;
            }

            $insertStmt = $this->db->prepare("INSERT IGNORE INTO ca_player_club_status (club_id, host_id, player_id, status) VALUES (:club_id, :host_id, :player_id, 'accepted')");

            foreach ($clubs as $club) {
                $clubGame = trim((string) $club['game_type']);
                if (!empty($clubGame) && stripos($games, $clubGame) !== false) {
                    $insertStmt->execute([
                        ':club_id' => $club['id'],
                        ':host_id' => $club['host_id'],
                        ':player_id' => $playerId
                    ]);
                }
            }
        } catch (PDOException $e) {
            // Silently fail for background sync
        }
    }
}
