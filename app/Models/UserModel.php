<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class UserModel extends Model
{
    protected string $table = 'ca_users';
    protected string $primaryKey = 'ID';
    
    protected array $fillable = [
        'NAME', 'EMAIL', 'PASSWORD', 'PASSWORD_HASH', 'LEVEL', 'VERIFIED_LEVEL',
        'EMAIL_PERMISSION', 'WHATSAPP_NUMBER', 'CALL_PERMISSION', 'DOB', 'GENDER',
        'GAMES', 'ADDRESS', 'CITY', 'COUNTRY', 'PROVINCE', 'AREA', 'REFERRAL',
        'CURRENCY', 'TIMEZONE_OFFSET', 'USERTYPE', 'PROFILE_IMAGE', 'LOG_STATUS',
        'DEL_STATUS', 'CURRENT_RANKING', 'PREV_RANKING', 'REMEMBER_TOKEN', 'PREMIUM'
    ];

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ID, NAME, EMAIL, LEVEL, VERIFIED_LEVEL,
                    EMAIL_PERMISSION, WHATSAPP_NUMBER, CALL_PERMISSION, DOB, GENDER,
                    GAMES, ADDRESS, CITY, COUNTRY, PROVINCE, AREA, REFERRAL,
                    CURRENCY, TIMEZONE_OFFSET, USERTYPE, PROFILE_IMAGE, LOG_STATUS,
                    DEL_STATUS, CURRENT_RANKING, PREV_RANKING, PREMIUM
             FROM ca_users
             WHERE ID = ? AND DEL_STATUS = 'N'
             LIMIT 1"
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    private static ?bool $hasSecurityVersionCol = null;

    public function hasSecurityVersionColumn(): bool
    {
        if (self::$hasSecurityVersionCol !== null) {
            return self::$hasSecurityVersionCol;
        }
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM ca_users LIKE 'SECURITY_VERSION'");
            self::$hasSecurityVersionCol = (bool) ($stmt && $stmt->fetch());
        } catch (\Throwable) {
            self::$hasSecurityVersionCol = false;
        }
        return self::$hasSecurityVersionCol;
    }

    public function findAuthUserById(int $id): ?array
    {
        $extraCols = $this->hasSecurityVersionColumn() ? ', SECURITY_VERSION' : '';
        $stmt = $this->db->prepare(
            "SELECT ID, NAME, EMAIL, USERTYPE, GENDER, VERIFIED_LEVEL, LEVEL,
                    COUNTRY, PROVINCE, CITY, GAMES, PROFILE_IMAGE, LOG_STATUS, DEL_STATUS, PREMIUM,
                    PASSWORD_HASH, PASSWORD{$extraCols}
             FROM ca_users
             WHERE ID = ? AND LOG_STATUS = 'Y' AND DEL_STATUS = 'N'
             LIMIT 1"
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ID, NAME, EMAIL, PASSWORD, PASSWORD_HASH, LEVEL, VERIFIED_LEVEL,
                    GENDER, GAMES, CITY, COUNTRY, PROVINCE, USERTYPE, PROFILE_IMAGE,
                    LOG_STATUS, DEL_STATUS, PREMIUM
             FROM ca_users
             WHERE EMAIL = ? AND DEL_STATUS = 'N'
             LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function findByUsernameOrEmail(string $identity): ?array
    {
        $extraCols = $this->hasSecurityVersionColumn() ? ', SECURITY_VERSION' : '';
        $stmt = $this->db->prepare(
            "SELECT ID, NAME, EMAIL, PASSWORD, PASSWORD_HASH, LEVEL, VERIFIED_LEVEL,
                    GENDER, GAMES, CITY, COUNTRY, PROVINCE, USERTYPE, PROFILE_IMAGE,
                    LOG_STATUS, DEL_STATUS, PREMIUM{$extraCols}
             FROM ca_users
             WHERE (EMAIL = ? OR NAME = ?) AND LOG_STATUS = 'Y' AND DEL_STATUS = 'N'
             LIMIT 1"
        );
        $stmt->execute([$identity, $identity]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function updateProfileImage(int $userId, string $imageName): bool
    {
        return $this->update($userId, ['PROFILE_IMAGE' => $imageName]);
    }

    public function updatePasswordHash(int $userId, string $passwordHash): bool
    {
        try {
            if ($this->hasSecurityVersionColumn()) {
                $stmt = $this->db->prepare("UPDATE ca_users SET PASSWORD_HASH = :hash, PASSWORD = '', SECURITY_VERSION = COALESCE(SECURITY_VERSION, 0) + 1 WHERE ID = :id");
            } else {
                $stmt = $this->db->prepare("UPDATE ca_users SET PASSWORD_HASH = :hash, PASSWORD = '' WHERE ID = :id");
            }
            $updated = $stmt->execute([':hash' => $passwordHash, ':id' => $userId]);

            if ($updated) {
                // Invalidate all remember-me tokens for this user
                \App\Helpers\RememberMe::revokeAllUserTokens($userId);

                // If current session belongs to this user, rotate session ID and bind to new security version
                if (\App\Helpers\Session::userId() === $userId) {
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        session_regenerate_id(true);
                    }
                    $updatedUser = $this->findAuthUserById($userId);
                    if ($updatedUser) {
                        $_SESSION['security_version'] = \App\Helpers\Session::computeSecurityVersion($updatedUser);
                    }
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                }
            }

            return $updated;
        } catch (\Throwable $e) {
            error_log('[UserModel::updatePasswordHash Error] ' . $e->getMessage());
            return false;
        }
    }

    public function updateRememberToken(int $userId, ?string $token): bool
    {
        return $this->update($userId, ['REMEMBER_TOKEN' => $token]);
    }

    public function findByRememberToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ID, NAME, EMAIL, USERTYPE, GENDER, VERIFIED_LEVEL, LEVEL,
                    COUNTRY, PROVINCE, CITY, GAMES, PROFILE_IMAGE, LOG_STATUS, DEL_STATUS
             FROM ca_users
             WHERE REMEMBER_TOKEN = ? AND REMEMBER_TOKEN IS NOT NULL AND REMEMBER_TOKEN != '' AND LOG_STATUS = 'Y' AND DEL_STATUS = 'N'
             LIMIT 1"
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function getAllUniqueGenders(?string $userType = null): array
    {
        static $cache = [];
        $key = (string) ($userType ?? '__ALL__');
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $sql = "SELECT DISTINCT GENDER 
                FROM {$this->table} 
                WHERE DEL_STATUS = 'N' 
                  AND GENDER IS NOT NULL 
                  AND TRIM(GENDER) != ''";
        $params = [];
        if ($userType !== null && $userType !== '') {
            $sql .= " AND USERTYPE = :usertype";
            $params[':usertype'] = $userType;
        }
        $sql .= " ORDER BY FIELD(GENDER, 'Male', 'Female', 'Mix', 'Kid'), GENDER ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $cache[$key] = ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    public function getAllUniqueVerifiedLevels(?string $userType = null): array
    {
        static $cache = [];
        $key = (string) ($userType ?? '__ALL__');
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $sql = "SELECT DISTINCT VERIFIED_LEVEL 
                FROM {$this->table} 
                WHERE DEL_STATUS = 'N' 
                  AND VERIFIED_LEVEL IS NOT NULL 
                  AND TRIM(VERIFIED_LEVEL) != ''";
        $params = [];
        if ($userType !== null && $userType !== '') {
            $sql .= " AND USERTYPE = :usertype";
            $params[':usertype'] = $userType;
        }
        $sql .= " ORDER BY FIELD(VERIFIED_LEVEL, 'Beginner', 'Amateur', 'Intermediate', 'Intermediate +', 'Advance', 'Advanced', 'Pro'), VERIFIED_LEVEL ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $cache[$key] = ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    public function adjustAndSaveRanking(int $playerId, string $newLevel, int $newRank): array
    {
        $stmt = $this->db->prepare("SELECT CURRENT_RANKING, VERIFIED_LEVEL FROM {$this->table} WHERE ID = ? LIMIT 1");
        $stmt->execute([$playerId]);
        $curr = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $oldRank = (int) ($curr['CURRENT_RANKING'] ?? 0);
        $oldLevel = (string) ($curr['VERIFIED_LEVEL'] ?? '');

        if ($oldLevel !== '' && $oldLevel !== $newLevel && $oldRank > 0) {
            // Level changed:
            // 1. Fill gap in old level by moving players ranked higher up (-1)
            $this->shiftRankings($oldLevel, -1, 'CURRENT_RANKING > ?', [$oldRank], $playerId);
            // 2. Make room in new level by moving players at or below newRank down (+1)
            $this->shiftRankings($newLevel, +1, 'CURRENT_RANKING >= ?', [$newRank], $playerId);
        } elseif ($oldRank > 0 && $newRank !== $oldRank) {
            if ($newRank < $oldRank) {
                // Promoted in same level: shift players between newRank and oldRank - 1 down (+1)
                $this->shiftRankings($newLevel, +1, 'CURRENT_RANKING >= ? AND CURRENT_RANKING < ?', [$newRank, $oldRank], $playerId);
            } else {
                // Demoted in same level: shift players between oldRank + 1 and newRank up (-1)
                $this->shiftRankings($newLevel, -1, 'CURRENT_RANKING <= ? AND CURRENT_RANKING > ?', [$newRank, $oldRank], $playerId);
            }
        } elseif ($oldRank <= 0) {
            // New entry without previous ranking: shift existing from newRank downward (+1)
            $this->shiftRankings($newLevel, +1, 'CURRENT_RANKING >= ?', [$newRank], $playerId);
        }

        // Update the rated player's verified level, previous ranking, and current ranking
        $updateStmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET VERIFIED_LEVEL = ?, 
                PREV_RANKING = ?, 
                CURRENT_RANKING = ? 
            WHERE ID = ?
        ");
        $updateStmt->execute([$newLevel, ($oldRank > 0 ? $oldRank : null), $newRank, $playerId]);

        return [
            'player_id' => $playerId,
            'old_rank'  => $oldRank,
            'old_level' => $oldLevel,
            'new_rank'  => $newRank,
            'new_level' => $newLevel,
        ];
    }

    /**
     * Generic helper to shift player rankings in a skill level based on a condition.
     */
    public function shiftRankings(string $level, int $offset, string $condition, array $params, int $excludeId): bool
    {
        $direction = $offset >= 0 ? "+ {$offset}" : "- " . abs($offset);
        $sql = "UPDATE {$this->table} 
                SET PREV_RANKING = CURRENT_RANKING, 
                    CURRENT_RANKING = CURRENT_RANKING {$direction} 
                WHERE VERIFIED_LEVEL = ? 
                  AND {$condition} 
                  AND ID != ?";
        $binds = array_merge([$level], $params, [$excludeId]);
        return $this->db->prepare($sql)->execute($binds);
    }
}
