<?php

declare(strict_types=1);

require_once __DIR__ . '/../init.php';

use App\Database\MigrationRunner;

try {
    $runner = new MigrationRunner();
    $action = strtolower($argv[1] ?? 'run');

    switch ($action) {
        case 'run':
        case 'migrate':
        case 'up':
            echo "Running pending migrations...\n";
            $result = $runner->migrate();
            echo "Status: " . strtoupper($result['status']) . "\n";
            echo $result['message'] . "\n";
            if (!empty($result['applied'])) {
                foreach ($result['applied'] as $mig) {
                    echo "  [+] Applied: $mig\n";
                }
            }
            break;

        case 'rollback':
        case 'down':
            echo "Rolling back last batch of migrations...\n";
            $result = $runner->rollback();
            echo "Status: " . strtoupper($result['status']) . "\n";
            echo $result['message'] . "\n";
            if (!empty($result['rolledBack'])) {
                foreach ($result['rolledBack'] as $mig) {
                    echo "  [-] Rolled Back: $mig\n";
                }
            }
            break;

        case 'status':
            echo "Migration Status:\n";
            echo str_pad("Migration", 50) . str_pad("Status", 12) . str_pad("Batch", 10) . "Executed At\n";
            echo str_repeat("-", 90) . "\n";
            $statusList = $runner->status();
            if (empty($statusList)) {
                echo "No migration files found.\n";
            } else {
                foreach ($statusList as $row) {
                    echo str_pad($row['migration'], 50) .
                        str_pad($row['status'], 12) .
                        str_pad((string)$row['batch'], 10) .
                        $row['executed_at'] . "\n";
                }
            }
            break;

        case 'make':
        case 'create':
            $name = $argv[2] ?? null;
            if (!$name) {
                echo "Error: Please specify migration name (e.g. php app/migrate.php make create_users_table)\n";
                exit(1);
            }
            $filePath = $runner->createMigration($name);
            echo "Created Migration File: " . $filePath . "\n";
            break;

        default:
            echo "Unknown command: $action\n";
            echo "Available commands: run | rollback | status | make <name>\n";
            exit(1);
    }
} catch (Exception $e) {
    echo "\n[ERROR] Migration execution failed:\n" . $e->getMessage() . "\n";
    exit(1);
}
