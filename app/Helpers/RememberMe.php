<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Config\Database;
use PDO;
use Exception;
use Throwable;

class RememberMe
{
    /** Get application-scoped remember cookie name. */
    public static function getCookieName(): string
    {
        if (function_exists('env')) {
            return (string) env('APP_REMEMBER_COOKIE', 'CASA_STAGING_REMEMBER');
        }
        return 'CASA_STAGING_REMEMBER';
    }

    /**
     * Create a new secure remember-me token (selector + validator) and store in DB & Cookie.
     */
    public static function create(int $userId, int $days = 30): bool
    {
        try {
            $selector  = bin2hex(random_bytes(12)); // 24 chars
            $validator = bin2hex(random_bytes(32)); // 64 chars
            $hash      = hash('sha256', $validator);

            $expiresSeconds = 86400 * max(1, $days);
            $expiresAt      = date('Y-m-d H:i:s', time() + $expiresSeconds);

            $pdo = Database::getPdo();
            $stmt = $pdo->prepare("
                INSERT INTO ca_remember_tokens (selector, validator_hash, user_id, expires_at, created_at)
                VALUES (:selector, :validator_hash, :user_id, :expires_at, NOW())
            ");

            $stmt->execute([
                ':selector'       => $selector,
                ':validator_hash' => $hash,
                ':user_id'        => $userId,
                ':expires_at'     => $expiresAt,
            ]);

            $cookieValue = $selector . '.' . $validator;
            self::setCookie($cookieValue, time() + $expiresSeconds);

            return true;
        } catch (Throwable $e) {
            error_log('[RememberMe::create] Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify remember-me cookie against database.
     * Returns user_id if valid, or null if invalid/expired/revoked.
     */
    public static function verify(): ?int
    {
        $cookieName = self::getCookieName();
        $rawCookie = $_COOKIE[$cookieName] ?? '';
        if (!is_string($rawCookie) || !preg_match('/\A[0-9a-f]{24}\.[0-9a-f]{64}\z/D', $rawCookie)) {
            return null;
        }

        $parts = explode('.', $rawCookie, 2);
        if (count($parts) !== 2) {
            self::clearCookie();
            return null;
        }

        [$selector, $validator] = $parts;

        if (strlen($selector) < 16 || strlen($validator) < 32) {
            self::clearCookie();
            return null;
        }

        try {
            $pdo = Database::getPdo();
            $stmt = $pdo->prepare("
                SELECT id, user_id, validator_hash, expires_at 
                FROM ca_remember_tokens 
                WHERE selector = :selector 
                  AND revoked_at IS NULL 
                  AND expires_at > NOW() 
                LIMIT 1
            ");
            $stmt->execute([':selector' => $selector]);
            $token = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$token) {
                self::clearCookie();
                return null;
            }

            $calcHash = hash('sha256', $validator);

            // Anti-tamper check
            if (!hash_equals($token['validator_hash'], $calcHash)) {
                // Potential token theft: Revoke all active tokens for this user!
                self::revokeAllUserTokens((int) $token['user_id']);
                self::clearCookie();
                error_log("[RememberMe Security Alert] Token mismatch for user ID {$token['user_id']}. Revoked all user tokens.");
                return null;
            }

            // Consume the validator atomically so a successfully used cookie cannot be replayed.
            $newValidator = bin2hex(random_bytes(32));
            $newHash = hash('sha256', $newValidator);
            $updateStmt = $pdo->prepare("UPDATE ca_remember_tokens SET validator_hash = :new_hash, last_used_at = NOW() WHERE id = :id AND validator_hash = :old_hash AND revoked_at IS NULL AND expires_at > NOW()");
            $updateStmt->execute([':new_hash' => $newHash, ':id' => $token['id'], ':old_hash' => $token['validator_hash']]);
            if ($updateStmt->rowCount() !== 1) {
                self::clearCookie();
                return null;
            }
            self::setCookie($selector . '.' . $newValidator, strtotime($token['expires_at']));

            return (int) $token['user_id'];
        } catch (Throwable $e) {
            error_log('[RememberMe::verify] Exception: ' . $e->getMessage());
            self::clearCookie();
            return null;
        }
    }

    /**
     * Revoke current remember-me token by selector.
     */
    public static function revokeCurrent(): void
    {
        $cookieName = self::getCookieName();
        $rawCookie = $_COOKIE[$cookieName] ?? '';
        if (is_string($rawCookie) && preg_match('/\A[0-9a-f]{24}\.[0-9a-f]{64}\z/D', $rawCookie)) {
            [$selector] = explode('.', $rawCookie, 2);
            try {
                $pdo = Database::getPdo();
                $stmt = $pdo->prepare("UPDATE ca_remember_tokens SET revoked_at = NOW() WHERE selector = :selector AND revoked_at IS NULL");
                $stmt->execute([':selector' => $selector]);
            } catch (Throwable $e) {
                error_log('[RememberMe::revokeCurrent] Exception: ' . $e->getMessage());
            }
        }
        self::clearCookie();
    }

    /**
     * Revoke all remember tokens for a specific user ID.
     */
    public static function revokeAllUserTokens(int $userId): void
    {
        try {
            $pdo = Database::getPdo();
            $stmt = $pdo->prepare("UPDATE ca_remember_tokens SET revoked_at = NOW() WHERE user_id = :user_id AND revoked_at IS NULL");
            $stmt->execute([':user_id' => $userId]);
        } catch (Throwable $e) {
            error_log('[RememberMe::revokeAllUserTokens] Exception: ' . $e->getMessage());
        }
        self::clearCookie();
    }

    /**
     * Helper to set cookie with proper Security flags.
     */
    protected static function setCookie(string $value, int $expiryTimestamp): void
    {
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
        setcookie(self::getCookieName(), $value, [
            'expires'  => $expiryTimestamp,
            'path'     => '/',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Helper to clear cookie.
     */
    public static function clearCookie(): void
    {
        $cookieName = self::getCookieName();
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
        setcookie($cookieName, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[$cookieName]);

        // Clean legacy cookie if present
        if (isset($_COOKIE['remember_password'])) {
            setcookie('remember_password', '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            unset($_COOKIE['remember_password']);
        }
    }
}
