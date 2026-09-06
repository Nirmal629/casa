<?php
// run_migrations.php
// PHP script to run the required SQL database updates and write log output to migration_output.txt

include('dbConnection.php');

$logFile = 'migration_output.txt';
$output = "=== Starting DB Migration: " . date('Y-m-d H:i:s') . " ===\n";

function runQuery($conn, $sql, &$output) {
    $output .= "Running: " . substr(preg_replace('/\s+/', ' ', $sql), 0, 100) . "...\n";
    if ($conn->query($sql)) {
        $output .= "✓ SUCCESS\n\n";
        return true;
    } else {
        $output .= "✗ FAILED: " . $conn->error . "\n\n";
        return false;
    }
}

try {
    // 1. Drop the unique constraint on host_id in ca_clubs if it exists
    // We try to check if the index exists, or we just drop it and catch errors
    $index_check = $conn->query("SHOW INDEX FROM `ca_clubs` WHERE Key_name = 'uq_host_id'");
    if ($index_check && $index_check->num_rows > 0) {
        runQuery($conn, "ALTER TABLE `ca_clubs` DROP INDEX `uq_host_id`", $output);
    } else {
        $output .= "• Index 'uq_host_id' does not exist or was already dropped.\n\n";
    }

    // 2. Add join_type field to ca_clubs if not exists
    $cols_check = $conn->query("SHOW COLUMNS FROM `ca_clubs` LIKE 'join_type'");
    if ($cols_check && $cols_check->num_rows === 0) {
        runQuery($conn, "ALTER TABLE `ca_clubs` ADD COLUMN `join_type` ENUM('A', 'R', 'H') NOT NULL DEFAULT 'A' AFTER `logo`", $output);
    } else {
        $output .= "• Column 'join_type' already exists on ca_clubs.\n\n";
    }

    // 3. Drop created_by, address, and area fields from ca_clubs if they exist
    foreach (['created_by', 'address', 'area'] as $col) {
        $check = $conn->query("SHOW COLUMNS FROM `ca_clubs` LIKE '$col'");
        if ($check && $check->num_rows > 0) {
            runQuery($conn, "ALTER TABLE `ca_clubs` DROP COLUMN `$col`", $output);
        } else {
            $output .= "• Column '$col' does not exist on ca_clubs.\n\n";
        }
    }

    // 4. Create ca_player_club_status table
    $create_ca_player_club_status = "CREATE TABLE IF NOT EXISTS `ca_player_club_status` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `club_id` INT(11) NOT NULL,
      `host_id` INT(11) NOT NULL,
      `player_id` INT(11) NOT NULL,
      `status` ENUM('pending', 'accepted') NOT NULL DEFAULT 'pending',
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_club_player` (`club_id`, `player_id`),
      FOREIGN KEY (`club_id`) REFERENCES `ca_clubs`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    runQuery($conn, $create_ca_player_club_status, $output);

    // 5. Drop redundant player_Host_mapping table if it exists
    $drop_player_Host_mapping = "DROP TABLE IF EXISTS `player_Host_mapping`";
    runQuery($conn, $drop_player_Host_mapping, $output);

    // 6. Create host_player_carry_forward table if not exists
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
    runQuery($conn, $create_host_player_carry_forward, $output);

    // 7. Create ca_host_player_carry_forward_history table if not exists
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
    runQuery($conn, $create_ca_host_player_carry_forward_history, $output);

    // 8. Add missing columns to ca_payment if they don't exist
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
                $output .= "✓ Column '$col' added to ca_payment\n\n";
            } else {
                throw new Exception("Failed to add column $col to ca_payment: " . $conn->error);
            }
        } else {
            $output .= "• Column '$col' already exists in ca_payment\n\n";
        }
    }

    // 9. Host Audit Log — extend ca_player_logs with host/game/context + indexes
    $apl_cols = [
        'HOST_ID'    => "ALTER TABLE `ca_player_logs` ADD COLUMN `HOST_ID` INT(11) NULL DEFAULT NULL AFTER `USER_ID`",
        'GAME_ID'    => "ALTER TABLE `ca_player_logs` ADD COLUMN `GAME_ID` INT(11) NULL DEFAULT NULL AFTER `HOST_ID`",
        'META'       => "ALTER TABLE `ca_player_logs` ADD COLUMN `META` TEXT NULL DEFAULT NULL AFTER `DESCRIPTION`",
        'IP_ADDRESS' => "ALTER TABLE `ca_player_logs` ADD COLUMN `IP_ADDRESS` VARCHAR(45) NULL DEFAULT NULL AFTER `META`",
    ];
    foreach ($apl_cols as $col => $alter_sql) {
        $check = $conn->query("SHOW COLUMNS FROM `ca_player_logs` LIKE '$col'");
        if ($check && $check->num_rows == 0) {
            if ($conn->query($alter_sql)) {
                $output .= "✓ Column '$col' added to ca_player_logs\n\n";
            } else {
                throw new Exception("Failed to add column $col to ca_player_logs: " . $conn->error);
            }
        } else {
            $output .= "• Column '$col' already exists in ca_player_logs\n\n";
        }
    }

    $apl_indexes = [
        'idx_apl_user_created' => "ALTER TABLE `ca_player_logs` ADD INDEX `idx_apl_user_created` (`USER_ID`, `CREATED_AT`)",
        'idx_apl_host_created' => "ALTER TABLE `ca_player_logs` ADD INDEX `idx_apl_host_created` (`HOST_ID`, `CREATED_AT`)",
        'idx_apl_type_created' => "ALTER TABLE `ca_player_logs` ADD INDEX `idx_apl_type_created` (`ACTIVITY_TYPE`, `CREATED_AT`)",
        'idx_apl_game'         => "ALTER TABLE `ca_player_logs` ADD INDEX `idx_apl_game` (`GAME_ID`)",
    ];
    foreach ($apl_indexes as $idx => $alter_sql) {
        $check = $conn->query("SHOW INDEX FROM `ca_player_logs` WHERE Key_name = '$idx'");
        if ($check && $check->num_rows == 0) {
            if ($conn->query($alter_sql)) {
                $output .= "✓ Index '$idx' added to ca_player_logs\n\n";
            } else {
                throw new Exception("Failed to add index $idx to ca_player_logs: " . $conn->error);
            }
        } else {
            $output .= "• Index '$idx' already exists on ca_player_logs\n\n";
        }
    }

    // 10. Host monthly subscription — records a per-player monthly subscription
    //     charge that is written into ca_expense (so the existing ledger picks it
    //     up automatically). Apply locks the row; unlock + rollback reverses it.
    $create_ca_host_subscription = "CREATE TABLE IF NOT EXISTS `ca_host_subscription` (
        `id`         INT(11)       NOT NULL AUTO_INCREMENT,
        `host_id`    INT(11)       NOT NULL,
        `player_id`  INT(11)       NOT NULL,
        `sub_year`   SMALLINT(4)   NOT NULL,
        `sub_month`  TINYINT(2)    NOT NULL,
        `amount`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `currency`   VARCHAR(10)   NOT NULL DEFAULT 'CAD',
        `games_count` INT(11)      NOT NULL DEFAULT 0 COMMENT 'completed games that month at apply time',
        `expense_id` INT(11)       DEFAULT NULL COMMENT 'FK to ca_expense row created on apply',
        `status`     ENUM('APPLIED','ROLLED_BACK') NOT NULL DEFAULT 'APPLIED',
        `is_locked`  TINYINT(1)    NOT NULL DEFAULT 1,
        `applied_by` INT(11)       DEFAULT NULL,
        `applied_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_hs_period` (`host_id`, `player_id`, `sub_year`, `sub_month`),
        KEY `idx_hs_host_period` (`host_id`, `sub_year`, `sub_month`),
        KEY `idx_hs_expense` (`expense_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    runQuery($conn, $create_ca_host_subscription, $output);

    $output .= "=== Migration Finished ===\n";

} catch (Exception $e) {
    $output .= "Exception occurred: " . $e->getMessage() . "\n";
}

file_put_contents($logFile, $output);
echo "Migration log written to $logFile.\n";
echo $output;
$conn->close();
?>
