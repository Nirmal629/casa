<?php

declare(strict_types=1);

use App\Database\Migration;

class M20261115000002AddEventTimezone extends Migration
{
    public function up(\PDO $pdo): void
    {
        $columns = $pdo->query("SHOW COLUMNS FROM ca_events LIKE 'EVENT_TIMEZONE'")->fetchAll();
        if (!$columns) {
            $pdo->exec("ALTER TABLE ca_events ADD COLUMN EVENT_TIMEZONE VARCHAR(64) NULL AFTER EVENT_TIME");
        }
    }

    public function down(\PDO $pdo): void
    {
        $columns = $pdo->query("SHOW COLUMNS FROM ca_events LIKE 'EVENT_TIMEZONE'")->fetchAll();
        if ($columns) {
            $pdo->exec("ALTER TABLE ca_events DROP COLUMN EVENT_TIMEZONE");
        }
    }
}
