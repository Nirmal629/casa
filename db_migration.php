<?php
// db_migration.php
include('dbConnection.php');

$log = "=== Starting DB Migration at " . date('Y-m-d H:i:s') . " ===\n";

try {
    // 1. Drop index uq_host_id on ca_clubs if it exists
    $check_index = $conn->query("SHOW INDEX FROM `ca_clubs` WHERE Key_name = 'uq_host_id'");
    if ($check_index && $check_index->num_rows > 0) {
        if ($conn->query("ALTER TABLE `ca_clubs` DROP INDEX `uq_host_id`")) {
            $log .= "✓ Index 'uq_host_id' dropped from ca_clubs\n";
        } else {
            $log .= "✗ Failed to drop index uq_host_id: " . $conn->error . "\n";
        }
    } else {
        $log .= "• Index 'uq_host_id' does not exist or already dropped in ca_clubs\n";
    }

    // 2. Add join_type, address, and area columns to ca_clubs if they don't exist
    $check_columns = $conn->query("SHOW COLUMNS FROM `ca_clubs` LIKE 'join_type'");
    if ($check_columns->num_rows == 0) {
        $conn->query("ALTER TABLE `ca_clubs` ADD COLUMN `join_type` VARCHAR(50) NOT NULL DEFAULT 'A' AFTER `logo`");
        $log .= "✓ Column 'join_type' added to ca_clubs\n";
    } else {
        $log .= "• Column 'join_type' already exists in ca_clubs\n";
    }

    $check_columns = $conn->query("SHOW COLUMNS FROM `ca_clubs` LIKE 'address'");
    if ($check_columns->num_rows == 0) {
        $conn->query("ALTER TABLE `ca_clubs` ADD COLUMN `address` TEXT DEFAULT NULL AFTER `join_type`");
        $log .= "✓ Column 'address' added to ca_clubs\n";
    } else {
        $log .= "• Column 'address' already exists in ca_clubs\n";
    }

    $check_columns = $conn->query("SHOW COLUMNS FROM `ca_clubs` LIKE 'area'");
    if ($check_columns->num_rows == 0) {
        $conn->query("ALTER TABLE `ca_clubs` ADD COLUMN `area` VARCHAR(255) DEFAULT NULL AFTER `address`");
        $log .= "✓ Column 'area' added to ca_clubs\n";
    } else {
        $log .= "• Column 'area' already exists in ca_clubs\n";
    }

    // 2. Drop created_by, address, area fields from ca_clubs if they exist
    foreach (['created_by', 'address', 'area'] as $col) {
        if ($conn->query("SHOW COLUMNS FROM `ca_clubs` LIKE '$col'")->num_rows > 0) {
            if ($conn->query("ALTER TABLE `ca_clubs` DROP COLUMN `$col`")) {
                $log .= "✓ Column '$col' dropped from ca_clubs\n";
            } else {
                throw new Exception("Failed to drop $col: " . $conn->error);
            }
        } else {
            $log .= "• Column '$col' does not exist on ca_clubs\n";
        }
    }

    // 3. Create the ca_player_club_status table
    $create_mapping_table = "
        CREATE TABLE IF NOT EXISTS `ca_player_club_status` (
            `id`          INT(11)       NOT NULL AUTO_INCREMENT,
            `club_id`     INT(11)       NOT NULL,  -- Represents Club ID
            `host_id`     INT(11)       NOT NULL,  -- Represents Host ID
            `player_id`   INT(11)       NOT NULL,  -- Represents Player ID
            `status`      VARCHAR(50)   NOT NULL DEFAULT 'pending',
            `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_club_player` (`club_id`, `player_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($conn->query($create_mapping_table)) {
        $log .= "✓ Table 'ca_player_club_status' is ready\n";
    } else {
        throw new Exception("Failed to create ca_player_club_status: " . $conn->error);
    }

    // 4. Drop redundant player_Host_mapping table if it exists
    if ($conn->query("DROP TABLE IF EXISTS `player_Host_mapping`")) {
        $log .= "✓ Table 'player_Host_mapping' dropped (redundant)\n";
    } else {
        throw new Exception("Failed to drop player_Host_mapping: " . $conn->error);
    }

    // 5. Create host_player_carry_forward table if not exists
    $create_host_player_carry_forward = "CREATE TABLE IF NOT EXISTS `host_player_carry_forward` (
        `id`              INT(11)       NOT NULL AUTO_INCREMENT,
        `host_id`         INT(11)       NOT NULL,
        `player_id`       INT(11)       NOT NULL,
        `carry_month`     TINYINT(2)    NOT NULL COMMENT 'Month that received the carried balance (destination)',
        `carry_year`      SMALLINT(4)   NOT NULL COMMENT 'Year of the carry_month',
        `opening_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `balance_type`    ENUM('DUE','ADVANCE') NOT NULL DEFAULT 'DUE' COMMENT 'DUE = balance owed, ADVANCE = player credit',
        `source_month`    TINYINT(2)    NOT NULL COMMENT 'Month that was carried forward (source)',
        `source_year`     SMALLINT(4)   NOT NULL,
        `is_locked`       TINYINT(1)    NOT NULL DEFAULT 0 COMMENT 'Mirrors existing month-lock rule for carry_month',
        `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_host_player_carry` (`host_id`, `player_id`, `carry_month`, `carry_year`),
        KEY `idx_hpc_player` (`player_id`),
        KEY `idx_hpc_source` (`source_month`, `source_year`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    if ($conn->query($create_host_player_carry_forward)) {
        $log .= "✓ Table 'host_player_carry_forward' is ready\n";
    } else {
        throw new Exception("Failed to create host_player_carry_forward: " . $conn->error);
    }

    // 6. Create ca_host_player_carry_forward_history table if not exists
    $create_ca_host_player_carry_forward_history = "CREATE TABLE IF NOT EXISTS `ca_host_player_carry_forward_history` (
        `id`                 INT(11)       NOT NULL AUTO_INCREMENT,
        `carry_forward_id`   INT(11)       NOT NULL COMMENT 'FK → host_player_carry_forward.id',
        `host_id`            INT(11)       NOT NULL,
        `player_id`          INT(11)       NOT NULL,
        `source_month`       TINYINT(2)    NOT NULL COMMENT 'Month carried FROM',
        `source_year`        SMALLINT(4)   NOT NULL,
        `destination_month`  TINYINT(2)    NOT NULL COMMENT 'Month carried TO',
        `destination_year`   SMALLINT(4)   NOT NULL,
        `opening_balance`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `balance_type`       ENUM('DUE','ADVANCE') NOT NULL DEFAULT 'DUE',
        `action`             ENUM('CREATE','UPDATE') NOT NULL DEFAULT 'CREATE',
        `remarks`            VARCHAR(255)  DEFAULT NULL,
        `created_by`         INT(11)       DEFAULT NULL COMMENT 'Host user who performed the carry',
        `created_at`         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_hcf_carry_forward_id` (`carry_forward_id`),
        KEY `idx_hcf_host_player` (`host_id`, `player_id`),
        KEY `idx_hcf_source` (`source_month`, `source_year`),
        KEY `idx_hcf_destination` (`destination_month`, `destination_year`),
        KEY `idx_hcf_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    if ($conn->query($create_ca_host_player_carry_forward_history)) {
        $log .= "✓ Table 'ca_host_player_carry_forward_history' is ready\n";
    } else {
        throw new Exception("Failed to create ca_host_player_carry_forward_history: " . $conn->error);
    }

    // 7. Add missing columns to ca_payment if they don't exist
    $payment_cols = [
        'MESSAGE' => "ALTER TABLE `ca_payment` ADD COLUMN `MESSAGE` TEXT NOT NULL DEFAULT ''",
        'REVIEWED_BY' => "ALTER TABLE `ca_payment` ADD COLUMN `REVIEWED_BY` INT(11) NULL DEFAULT NULL",
        'REVIEWED_AT' => "ALTER TABLE `ca_payment` ADD COLUMN `REVIEWED_AT` TIMESTAMP NULL DEFAULT NULL",
        'REJECTION_REASON' => "ALTER TABLE `ca_payment` ADD COLUMN `REJECTION_REASON` TEXT NULL DEFAULT NULL"
    ];
    foreach ($payment_cols as $col => $alter_sql) {
        $check = $conn->query("SHOW COLUMNS FROM `ca_payment` LIKE '$col'");
        if ($check && $check->num_rows == 0) {
            if ($conn->query($alter_sql)) {
                $log .= "✓ Column '$col' added to ca_payment\n";
            } else {
                throw new Exception("Failed to add column $col to ca_payment: " . $conn->error);
            }
        } else {
            $log .= "• Column '$col' already exists in ca_payment\n";
        }
    }

    $log .= "=== Migration SUCCESS! ===\n";

} catch (Exception $e) {
    $log .= "✗ Migration failed: " . $e->getMessage() . "\n";
}

file_put_contents(__DIR__ . '/migration_log.txt', $log);
?>
