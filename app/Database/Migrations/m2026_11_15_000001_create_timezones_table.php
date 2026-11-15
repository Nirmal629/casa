<?php

declare(strict_types=1);

use App\Database\Migration;

class M20261115000001CreateTimezonesTable extends Migration
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS ca_timezones (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            TIMEZONE_NAME VARCHAR(64) NOT NULL UNIQUE,
            TIMEZONE_LABEL VARCHAR(128) NOT NULL,
            UTC_OFFSET VARCHAR(6) NOT NULL,
            IS_ACTIVE TINYINT(1) NOT NULL DEFAULT 1,
            CREATED_AT TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UPDATED_AT TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $stmt = $pdo->prepare("INSERT INTO ca_timezones
            (TIMEZONE_NAME, TIMEZONE_LABEL, UTC_OFFSET, IS_ACTIVE)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                TIMEZONE_LABEL = VALUES(TIMEZONE_LABEL),
                UTC_OFFSET = VALUES(UTC_OFFSET),
                IS_ACTIVE = 1");

        foreach ([
            ['UTC', 'UTC', '+00:00'],
            ['Asia/Kolkata', 'India Standard Time (IST)', '+05:30'],
            ['America/Toronto', 'Eastern Time (Canada/US)', '-05:00'],
            ['America/New_York', 'Eastern Time (US)', '-05:00'],
            ['America/Chicago', 'Central Time (US)', '-06:00'],
            ['America/Denver', 'Mountain Time (US)', '-07:00'],
            ['America/Los_Angeles', 'Pacific Time (US)', '-08:00'],
            ['Europe/London', 'United Kingdom Time', '+00:00'],
            ['Europe/Paris', 'Central European Time', '+01:00'],
            ['Asia/Dubai', 'Gulf Standard Time (GST)', '+04:00'],
            ['Asia/Singapore', 'Singapore Standard Time (SGT)', '+08:00'],
        ] as $timezone) {
            $stmt->execute($timezone);
        }
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS ca_timezones;');
    }
}
