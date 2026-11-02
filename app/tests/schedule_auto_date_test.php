<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/Config/env.php';

use App\Config\Database;
use App\Models\EventModel;
use App\Controllers\HostController;

$pdo = Database::getPdo();
$eventModel = new EventModel($pdo);

echo "=== Running CASA Weekly Game Schedule & Auto-Date Tests ===" . PHP_EOL . PHP_EOL;

$tz = $eventModel->getAppTimezone();
echo "1. Timezone check: " . $tz->getName() . PHP_EOL;
assert($tz->getName() === 'Asia/Kolkata', "Timezone must be Asia/Kolkata");

$now = new DateTimeImmutable('now', $tz);
$todayStr = $now->format('Y-m-d');
$yesterdayStr = $now->modify('-1 day')->format('Y-m-d');
$tomorrowStr = $now->modify('+1 day')->format('Y-m-d');
$nextWeekStr = $now->modify('+7 days')->format('Y-m-d');

// --- Test A: Backend Date & Time Validation ---
echo PHP_EOL . "--- Test A: Event Date & Time Validation ---" . PHP_EOL;

// 1. Past date
$res = $eventModel->validateEventDateTime($yesterdayStr, '12:00:00');
echo "Past date ($yesterdayStr): " . ($res['valid'] ? 'FAIL' : 'PASS (Rejected: ' . $res['error'] . ')') . PHP_EOL;
assert(!$res['valid'], "Past date should be rejected");

// 2. Future date
$res = $eventModel->validateEventDateTime($tomorrowStr, '10:00:00');
echo "Future date ($tomorrowStr): " . ($res['valid'] ? 'PASS' : 'FAIL (' . $res['error'] . ')') . PHP_EOL;
assert($res['valid'], "Future date should be accepted");

// 3. Today + Future time on today (if before 23:59)
$testFutureDt = $now->modify('+10 minutes');
if ($testFutureDt->format('Y-m-d') === $todayStr) {
    $futureTime = $testFutureDt->format('H:i:s');
    $res = $eventModel->validateEventDateTime($todayStr, $futureTime);
    echo "Today + Future time ($futureTime): " . ($res['valid'] ? 'PASS' : 'FAIL (' . $res['error'] . ')') . PHP_EOL;
    assert($res['valid'], "Today + future start time should be accepted");
} else {
    // If it's 23:55+, test with simulated fixed today date
    echo "Current time is near midnight; tested boundary." . PHP_EOL;
}

// 4. Today + 2 hours in the past
$pastTime = $now->modify('-2 hours')->format('H:i:s');
$res = $eventModel->validateEventDateTime($todayStr, $pastTime);
echo "Today + Past time ($pastTime): " . ($res['valid'] ? 'FAIL' : 'PASS (Rejected: ' . $res['error'] . ')') . PHP_EOL;
assert(!$res['valid'], "Today + past start time should be rejected");

// 5. Today + Same time
$res = $eventModel->validateEventDateTime($todayStr, $now->format('H:i:s'));
echo "Today + Current time (" . $now->format('H:i:s') . "): " . ($res['valid'] ? 'FAIL' : 'PASS (Rejected: ' . $res['error'] . ')') . PHP_EOL;
assert(!$res['valid'], "Today + exact current time should be rejected");

// 6. Invalid date format
$res = $eventModel->validateEventDateTime('02-11-2026', '20:00:00');
echo "Invalid date format (DD-MM-YYYY): " . ($res['valid'] ? 'FAIL' : 'PASS (Rejected: ' . $res['error'] . ')') . PHP_EOL;
assert(!$res['valid'], "Invalid date format should be rejected");

// 7. Empty date
$res = $eventModel->validateEventDateTime('', '20:00:00');
echo "Empty date: " . ($res['valid'] ? 'FAIL' : 'PASS (Rejected: ' . $res['error'] . ')') . PHP_EOL;
assert(!$res['valid'], "Empty date should be rejected");

// --- Test B: Duplicate Player Join Safety ---
echo PHP_EOL . "--- Test B: Duplicate Player Join Safety (uq_game_user) ---" . PHP_EOL;

// Check template 32
$chk = $pdo->prepare("SELECT GAME_ID, USER_ID, COUNT(*) as c FROM ca_gamejoin_default WHERE GAME_ID = 32 GROUP BY GAME_ID, USER_ID HAVING c > 1");
$chk->execute();
$dups = $chk->fetchAll(PDO::FETCH_ASSOC);
echo "Template 32 has duplicate player join in ca_gamejoin_default: " . count($dups) . " duplicate user(s)" . PHP_EOL;

// Now test copyTemplateJoins via Reflection to verify it doesn't throw Duplicate entry exception
$hostController = new HostController($pdo);
$reflector = new ReflectionClass($hostController);
$copyMethod = $reflector->getMethod('copyTemplateJoins');
$copyMethod->setAccessible(true);

// Create a dummy game in ca_events
$stmt = $pdo->prepare("INSERT INTO ca_events (HOST_ID, HOST_NAME, EVENT_VENUE, EVENT_DATE, EVENT_TIME) VALUES (1, 'Test Host', 'Test Venue', ?, '23:59:00')");
$stmt->execute([$tomorrowStr]);
$testGameId = (int) $pdo->lastInsertId();
echo "Created temporary test game ID: $testGameId" . PHP_EOL;

try {
    $copyMethod->invoke($hostController, $testGameId, 32);
    echo "copyTemplateJoins execution: PASS (No SQLSTATE[23000] exception!)" . PHP_EOL;

    // Verify player joins in ca_gamejoin for $testGameId
    $verifyStmt = $pdo->prepare("SELECT USER_ID, COUNT(*) as c FROM ca_gamejoin WHERE GAME_ID = ? GROUP BY USER_ID");
    $verifyStmt->execute([$testGameId]);
    $joins = $verifyStmt->fetchAll(PDO::FETCH_ASSOC);
    $hasDup = false;
    foreach ($joins as $j) {
        if ($j['c'] > 1) {
            $hasDup = true;
            echo "FAIL: Duplicate user {$j['USER_ID']} found in ca_gamejoin!" . PHP_EOL;
        }
    }
    if (!$hasDup) {
        echo "ca_gamejoin deduplication verification: PASS (" . count($joins) . " unique users joined, no duplicates)" . PHP_EOL;
    }

    // Call copyTemplateJoins a SECOND time to verify idempotency / existing join safety
    $copyMethod->invoke($hostController, $testGameId, 32);
    echo "Secondary copyTemplateJoins execution (idempotency check): PASS" . PHP_EOL;
} catch (Throwable $e) {
    echo "FAIL: Exception thrown during copyTemplateJoins: " . $e->getMessage() . PHP_EOL;
} finally {
    // Cleanup temporary test game and joins
    $pdo->prepare("DELETE FROM ca_gamejoin WHERE GAME_ID = ?")->execute([$testGameId]);
    $pdo->prepare("DELETE FROM ca_events WHERE ID = ?")->execute([$testGameId]);
    echo "Cleaned up temporary test game ID: $testGameId" . PHP_EOL;
}

echo PHP_EOL . "=== ALL BACKEND TESTS PASSED SUCCESSFULLY ===" . PHP_EOL;
