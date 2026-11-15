<?php

declare(strict_types=1);

namespace App\Database;

use App\Config\Database;
use PDO;
use Exception;

/**
 * MigrationRunner — Central Database Migration Engine
 */
class MigrationRunner
{
    protected PDO $pdo;
    protected string $migrationsPath;

    public function __construct(?PDO $pdo = null, ?string $migrationsPath = null)
    {
        $this->pdo = $pdo ?? Database::getPdo();
        $this->migrationsPath = $migrationsPath ?? __DIR__ . '/Migrations';

        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }

        $this->ensureMigrationsTableExists();
    }

    /**
     * Ensure schema_migrations table exists in database.
     */
    protected function ensureMigrationsTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            batch INT NOT NULL,
            executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $this->pdo->exec($sql);
    }

    /**
     * Get list of already executed migrations with batch details.
     */
    public function getExecutedMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT migration, batch, executed_at FROM schema_migrations ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get current max batch number.
     */
    protected function getNextBatchNumber(): int
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) AS max_batch FROM schema_migrations");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ((int)($row['max_batch'] ?? 0)) + 1;
    }

    /**
     * Get all available migration file names.
     */
    public function getAllMigrationFiles(): array
    {
        $files = glob($this->migrationsPath . '/*.php');
        if ($files === false) {
            return [];
        }

        $migrations = [];
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            $migrations[$filename] = $file;
        }

        ksort($migrations);
        return $migrations;
    }

    /**
     * Run all pending migrations.
     */
    public function migrate(): array
    {
        $executed = array_column($this->getExecutedMigrations(), 'migration');
        $allFiles = $this->getAllMigrationFiles();
        $pending = array_diff(array_keys($allFiles), $executed);

        if (empty($pending)) {
            return ['status' => 'info', 'message' => 'No pending migrations to run.'];
        }

        $batch = $this->getNextBatchNumber();
        $applied = [];

        foreach ($pending as $migrationName) {
            $filePath = $allFiles[$migrationName];
            require_once $filePath;

            $className = $this->getClassNameFromFileName($migrationName);
            if (!class_exists($className)) {
                throw new Exception("Migration class '$className' not found in file: $filePath");
            }

            $migrationInstance = new $className();
            if (!($migrationInstance instanceof Migration)) {
                throw new Exception("Class '$className' must extend App\\Database\\Migration");
            }

            $this->pdo->beginTransaction();
            try {
                $migrationInstance->up($this->pdo);

                $stmt = $this->pdo->prepare("INSERT INTO schema_migrations (migration, batch) VALUES (:migration, :batch)");
                $stmt->execute([
                    ':migration' => $migrationName,
                    ':batch'     => $batch
                ]);

                if ($this->pdo->inTransaction()) {
                    $this->pdo->commit();
                }
                $applied[] = $migrationName;
            } catch (Exception $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                throw new Exception("Migration failed [$migrationName]: " . $e->getMessage(), (int)$e->getCode(), $e);
            }
        }

        return [
            'status'  => 'success',
            'batch'   => $batch,
            'applied' => $applied,
            'message' => 'Successfully ran ' . count($applied) . ' migration(s).'
        ];
    }

    /**
     * Rollback the last batch of migrations.
     */
    public function rollback(): array
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) AS max_batch FROM schema_migrations");
        $lastBatch = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['max_batch'] ?? 0);

        if ($lastBatch === 0) {
            return ['status' => 'info', 'message' => 'Nothing to rollback.'];
        }

        $stmt = $this->pdo->prepare("SELECT migration FROM schema_migrations WHERE batch = :batch ORDER BY id DESC");
        $stmt->execute([':batch' => $lastBatch]);
        $migrationsToRollback = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $allFiles = $this->getAllMigrationFiles();
        $rolledBack = [];

        foreach ($migrationsToRollback as $migrationName) {
            if (!isset($allFiles[$migrationName])) {
                throw new Exception("Migration file for '$migrationName' not found.");
            }

            $filePath = $allFiles[$migrationName];
            require_once $filePath;

            $className = $this->getClassNameFromFileName($migrationName);
            if (!class_exists($className)) {
                throw new Exception("Migration class '$className' not found.");
            }

            $migrationInstance = new $className();
            $this->pdo->beginTransaction();
            try {
                $migrationInstance->down($this->pdo);

                $delStmt = $this->pdo->prepare("DELETE FROM schema_migrations WHERE migration = :migration");
                $delStmt->execute([':migration' => $migrationName]);

                if ($this->pdo->inTransaction()) {
                    $this->pdo->commit();
                }
                $rolledBack[] = $migrationName;
            } catch (Exception $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                throw new Exception("Rollback failed [$migrationName]: " . $e->getMessage(), (int)$e->getCode(), $e);
            }
        }

        return [
            'status'     => 'success',
            'batch'      => $lastBatch,
            'rolledBack' => $rolledBack,
            'message'    => 'Successfully rolled back batch ' . $lastBatch . ' (' . count($rolledBack) . ' migration(s)).'
        ];
    }

    /**
     * Get detailed status of all migrations.
     */
    public function status(): array
    {
        $executedRows = $this->getExecutedMigrations();
        $executedMap = [];
        foreach ($executedRows as $row) {
            $executedMap[$row['migration']] = $row;
        }

        $allFiles = $this->getAllMigrationFiles();
        $statusList = [];

        foreach ($allFiles as $name => $filePath) {
            $isRan = isset($executedMap[$name]);
            $statusList[] = [
                'migration'   => $name,
                'status'      => $isRan ? 'Ran' : 'Pending',
                'batch'       => $isRan ? $executedMap[$name]['batch'] : '-',
                'executed_at' => $isRan ? $executedMap[$name]['executed_at'] : '-'
            ];
        }

        return $statusList;
    }

    /**
     * Create a new migration file stub.
     */
    public function createMigration(string $name): string
    {
        $cleanName = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower(trim($name)));
        $timestamp = date('Y_m_d_His');
        $fileName = "m{$timestamp}_{$cleanName}.php";
        $filePath = $this->migrationsPath . '/' . $fileName;

        $className = $this->getClassNameFromFileName("m{$timestamp}_{$cleanName}");

        $stub = <<<PHP
<?php

declare(strict_types=1);

use App\Database\Migration;

class {$className} extends Migration
{
    public function up(PDO \$pdo): void
    {
        // \$sql = "CREATE TABLE example (id INT AUTO_INCREMENT PRIMARY KEY);";
        // \$pdo->exec(\$sql);
    }

    public function down(PDO \$pdo): void
    {
        // \$sql = "DROP TABLE IF EXISTS example;";
        // \$pdo->exec(\$sql);
    }
}
PHP;

        file_put_contents($filePath, $stub);
        return $filePath;
    }

    /**
     * Convert migration file name to CamelCase Class Name.
     * Example: m2026_09_26_120000_create_users_table -> M2026_09_26_120000_CreateUsersTable
     */
    protected function getClassNameFromFileName(string $filename): string
    {
        $parts = explode('_', $filename);
        $classNameParts = array_map('ucfirst', $parts);
        return implode('', $classNameParts);
    }
}
