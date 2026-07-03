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

    $log .= "=== Migration SUCCESS! ===\n";

} catch (Exception $e) {
    $log .= "✗ Migration failed: " . $e->getMessage() . "\n";
}

file_put_contents(__DIR__ . '/migration_log.txt', $log);
?>
