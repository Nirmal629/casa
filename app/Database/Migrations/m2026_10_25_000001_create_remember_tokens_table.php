<?php

declare(strict_types=1);

use App\Database\Migration;

class M20261025000001CreateRememberTokensTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS ca_remember_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            selector VARCHAR(64) NOT NULL UNIQUE,
            validator_hash CHAR(64) NOT NULL,
            user_id INT NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_used_at DATETIME NULL DEFAULT NULL,
            revoked_at DATETIME NULL DEFAULT NULL,
            INDEX idx_selector (selector),
            INDEX idx_user_id (user_id),
            INDEX idx_expires_at (expires_at),
            INDEX idx_revoked_at (revoked_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS ca_remember_tokens;");
    }
}
