<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

/**
 * Abstract Base Class for Database Migrations
 */
abstract class Migration
{
    /**
     * Run the migration (apply changes).
     */
    abstract public function up(PDO $pdo): void;

    /**
     * Reverse the migration (rollback changes).
     */
    abstract public function down(PDO $pdo): void;
}
