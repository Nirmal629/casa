<?php

declare(strict_types=1);

use App\Database\Migration;

class M20260926000001InitDatabase extends Migration
{
    public function up(PDO $pdo): void
    {
        // Example initial baseline table check/creation
        $sql = "CREATE TABLE IF NOT EXISTS ca_system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $pdo->exec($sql);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS ca_system_settings;");
    }
}
