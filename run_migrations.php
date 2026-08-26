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

    $output .= "=== Migration Finished ===\n";

} catch (Exception $e) {
    $output .= "Exception occurred: " . $e->getMessage() . "\n";
}

file_put_contents($logFile, $output);
echo "Migration log written to $logFile.\n";
echo $output;
$conn->close();
?>
