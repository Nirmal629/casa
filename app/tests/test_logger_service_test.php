<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/Config/env.php';
require_once __DIR__ . '/../../app/Services/Testing/TestLoggerService.php';

use App\Services\Testing\TestLoggerService;

echo "=== Testing TestLoggerService ===" . PHP_EOL . PHP_EOL;

// 1. isDev check
$isDev = TestLoggerService::isDev();
echo "1. isDev(): " . ($isDev ? 'TRUE' : 'FALSE') . PHP_EOL;
assert($isDev, "isDev must be true in development environment");

// 2. sanitizeSessionId check
$cleanId = TestLoggerService::sanitizeSessionId('CASA-TEST-001/../hack!');
echo "2. sanitizeSessionId: " . $cleanId . PHP_EOL;
assert($cleanId === 'CASA-TEST-001hack', "Must strip illegal characters and directory traversal");

// 3. Write entries to a test session
$testSessionId = 'CASA-UNIT-TEST-' . time();

$e1 = [
    'seq'         => 1,
    'action_type' => 'NAVIGATE',
    'url'         => '/host/host-dashboard.php?token=secret123&user=john',
    'referrer'    => '/index.php',
];
$w1 = TestLoggerService::writeEntry($testSessionId, $e1);
echo "3. Write entry 1 (NAVIGATE): " . ($w1 ? 'PASS' : 'FAIL') . PHP_EOL;
assert($w1, "Write entry 1 should succeed");

$e2 = [
    'seq'         => 2,
    'action_type' => 'CLICK',
    'element'     => 'button#create_btn.btn-success "Create"',
    'url'         => '/host/host-dashboard.php',
];
$w2 = TestLoggerService::writeEntry($testSessionId, $e2);
echo "4. Write entry 2 (CLICK): " . ($w2 ? 'PASS' : 'FAIL') . PHP_EOL;
assert($w2, "Write entry 2 should succeed");

$e3 = [
    'seq'         => 3,
    'action_type' => 'API_CALL',
    'api'         => [
        'endpoint' => '/api/create_event_sub.php',
        'method'   => 'POST',
    ],
    'correlation_id' => 'CORR-UNIT-001',
    'password'    => 'mySecretPassword123', // Sensitive field to check redaction
];
$w3 = TestLoggerService::writeEntry($testSessionId, $e3);
echo "5. Write entry 3 (API_CALL with sensitive data): " . ($w3 ? 'PASS' : 'FAIL') . PHP_EOL;
assert($w3, "Write entry 3 should succeed");

// 4. Read back logs and verify content & redaction
$logs = TestLoggerService::getRecentLogs($testSessionId, 50);
echo "6. Read back logs count: " . count($logs) . PHP_EOL;
assert(count($logs) === 3, "Expected 3 log entries");

assert($logs[0]['action_type'] === 'NAVIGATE', "Entry 1 action_type must be NAVIGATE");
assert(str_contains($logs[0]['url'], '[REDACTED]'), "Sensitive query param 'token' must be redacted");
assert(!str_contains($logs[0]['url'], 'secret123'), "Plaintext secret token must not exist in log");

assert($logs[1]['action_type'] === 'CLICK', "Entry 2 action_type must be CLICK");

assert($logs[2]['action_type'] === 'API_CALL', "Entry 3 action_type must be API_CALL");
assert($logs[2]['password'] === '[REDACTED]', "Sensitive field 'password' must be redacted");

// 5. Test logBackendException
$_SERVER['HTTP_X_TEST_SESSION_ID'] = $testSessionId;
$_SERVER['HTTP_X_CORRELATION_ID'] = 'CORR-EXCEPTION-TEST';
$_SERVER['REQUEST_URI'] = '/api/create_event_sub.php';
$_SERVER['REQUEST_METHOD'] = 'POST';

$dummyException = new \Exception('Simulated database deadlock for testing');
$wEx = TestLoggerService::logBackendException($dummyException, 'ERR-TEST-1234');
echo "7. logBackendException: " . ($wEx ? 'PASS' : 'FAIL') . PHP_EOL;
assert($wEx, "logBackendException should succeed");

$updatedLogs = TestLoggerService::getRecentLogs($testSessionId, 50);
echo "8. Updated logs count: " . count($updatedLogs) . PHP_EOL;
assert(count($updatedLogs) === 4, "Expected 4 log entries after exception");
assert($updatedLogs[3]['action_type'] === 'PHP_EXCEPTION', "4th entry must be PHP_EXCEPTION");
assert($updatedLogs[3]['error']['message'] === 'Simulated database deadlock for testing');
assert($updatedLogs[3]['correlation_id'] === 'CORR-EXCEPTION-TEST');

// Clean up test session file
$testFile = TestLoggerService::getSessionFilePath($testSessionId);
if (file_exists($testFile)) {
    unlink($testFile);
}
echo "9. Cleaned up temporary test log file: $testFile" . PHP_EOL;

echo PHP_EOL . "=== ALL LOGGER TESTS PASSED ===" . PHP_EOL;

