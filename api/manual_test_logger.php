<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Config/env.php';
require_once __DIR__ . '/../app/Services/Testing/TestLoggerService.php';

use App\Services\Testing\TestLoggerService;

header('X-Content-Type-Options: nosniff');

if (!TestLoggerService::isDev()) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Manual Testing Logger is disabled in production.'], JSON_UNESCAPED_SLASHES);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    // Read JSON payload or fallback to form data
    $rawInput = file_get_contents('php://input');
    $payload = [];
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput, true);
        if (is_array($decoded)) {
            $payload = $decoded;
        }
    }
    if (empty($payload)) {
        $payload = $_POST;
    }

    $action = (string) ($payload['action'] ?? 'log');
    $sessionId = TestLoggerService::sanitizeSessionId((string) ($payload['session_id'] ?? ''));

    header('Content-Type: application/json; charset=utf-8');

    if ($action === 'start') {
        $entry = [
            'seq'         => 0,
            'action_type' => 'TEST_SESSION_START',
            'url'         => (string) ($payload['url'] ?? ''),
            'message'     => 'Manual Testing Session Started',
        ];
        TestLoggerService::writeEntry($sessionId, $entry);

        // Also set a cookie so subsequent synchronous requests can identify the test session
        setcookie('casa_qa_session_id', $sessionId, [
            'expires'  => time() + 86400,
            'path'     => '/',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);

        echo json_encode(['success' => true, 'session_id' => $sessionId], JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'stop') {
        $entry = [
            'seq'         => (int) ($payload['seq'] ?? 99999),
            'action_type' => 'TEST_SESSION_STOP',
            'url'         => (string) ($payload['url'] ?? ''),
            'message'     => 'Manual Testing Session Stopped',
        ];
        TestLoggerService::writeEntry($sessionId, $entry);

        setcookie('casa_qa_session_id', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);

        echo json_encode(['success' => true, 'session_id' => $sessionId], JSON_UNESCAPED_SLASHES);
        exit;
    }

    // Default: write log entry
    $data = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : $payload;
    unset($data['action'], $data['session_id']);

    $saved = TestLoggerService::writeEntry($sessionId, $data);
    echo json_encode(['success' => $saved], JSON_UNESCAPED_SLASHES);
    exit;
}

if ($method === 'GET') {
    $action = (string) ($_GET['action'] ?? 'view');
    $sessionId = TestLoggerService::sanitizeSessionId((string) ($_GET['session_id'] ?? ''));

    if ($action === 'export') {
        $filePath = TestLoggerService::getSessionFilePath($sessionId);
        if (!file_exists($filePath)) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Session log file not found.'], JSON_UNESCAPED_SLASHES);
            exit;
        }

        header('Content-Type: application/x-jsonlines; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $sessionId . '.jsonl"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    if ($action === 'list') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'  => true,
            'sessions' => TestLoggerService::listSessions(),
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    // Default: return recent logs as JSON
    header('Content-Type: application/json; charset=utf-8');
    $limit = isset($_GET['limit']) ? max(1, min(500, (int) $_GET['limit'])) : 150;
    $logs = TestLoggerService::getRecentLogs($sessionId, $limit);

    echo json_encode([
        'success'    => true,
        'session_id' => $sessionId,
        'count'      => count($logs),
        'logs'       => $logs,
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

http_response_code(405);
exit;

