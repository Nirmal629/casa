<?php

header('Content-Type: text/plain; charset=utf-8');

require __DIR__ . '/config/env.php';

const EVENT_DAYS_AHEAD  = 45;
const CANCEL_DAYS_BEHIND = 3;
const DRAW_DAYS_BEHIND   = 5;

try {
    $pdo = new PDO(
        'mysql:host=' . env('DB_HOST', 'localhost') .
        ';dbname='    . env('DB_NAME', 'casa_test') .
        ';charset='   . env('DB_CHARSET', 'utf8mb4'),
        env('DB_USER', 'root'),
        env('DB_PASS', ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $today = $pdo->query('SELECT CURDATE()')->fetchColumn();
    echo "DB CURDATE() = {$today}\n\n";

    $before = $pdo->query(
        "SELECT ID, CUP_NAME, EVENT_CATEGORY, GENDER_CATEGORY,
                EVENT_DATE, CANCEL_DATE, DRAW_ANNOUNCEMENT
         FROM to_tournaments
         WHERE STATUS = 'Active'
         ORDER BY ID"
    )->fetchAll();

    if (!$before) {
        echo "No Active tournaments found - nothing to do.\n";
        exit;
    }

    $updated = $pdo->exec(
        "UPDATE to_tournaments
            SET EVENT_DATE        = DATE_ADD(CURDATE(), INTERVAL " . EVENT_DAYS_AHEAD . " DAY),
                CANCEL_DATE       = DATE_SUB(CURDATE(), INTERVAL " . CANCEL_DAYS_BEHIND . " DAY),
                DRAW_ANNOUNCEMENT = DATE_SUB(CURDATE(), INTERVAL " . DRAW_DAYS_BEHIND . " DAY)
          WHERE STATUS = 'Active'"
    );

    $after = $pdo->query(
        "SELECT ID, EVENT_DATE, CANCEL_DATE, DRAW_ANNOUNCEMENT
         FROM to_tournaments WHERE STATUS = 'Active' ORDER BY ID"
    )->fetchAll();
    $afterById = array_column($after, null, 'ID');

    foreach ($before as $b) {
        $a = $afterById[$b['ID']];
        printf(
            "ID %-3d %-24s %-5s %-7s\n" .
            "        EVENT  %s -> %s\n" .
            "        CANCEL %s -> %s   (Registration Closed)\n" .
            "        DRAW   %s -> %s\n",
            $b['ID'], $b['CUP_NAME'], $b['EVENT_CATEGORY'], $b['GENDER_CATEGORY'],
            $b['EVENT_DATE'], $a['EVENT_DATE'],
            $b['CANCEL_DATE'] ?: '-', $a['CANCEL_DATE'],
            $b['DRAW_ANNOUNCEMENT'] ?: '-', $a['DRAW_ANNOUNCEMENT']
        );
    }

    echo "\nDone - {$updated} row(s) updated. Reload player-hub.php (Ctrl+F5).\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
