<?php
require_once __DIR__ . '/_security.php';
requireApiUser([]);
/**
 * api/log_tab_activity.php
 * Endpoint for recording generic tab, sub-tab, and modal activity into ca_player_logs.
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {

}

// Include DB connection
$dbFile = __DIR__ . '/dbConnection.php';
if (!file_exists($dbFile)) {
    $dbFile = __DIR__ . '/../dbConnection.php';
}
include_once($dbFile);

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Read payload (supports FormData POST and raw JSON input)
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $input = $json;
        }
    }
}

// Extract attributes
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (int)($input['user_id'] ?? 0);
$rawHostId = isset($input['host_id']) && (int)$input['host_id'] > 0 ? (int)$input['host_id'] : null;
$gameId = isset($input['game_id']) && (int)$input['game_id'] > 0 ? (int)$input['game_id'] : null;

// Dynamically resolve host ID if missing or under host context
$resolvedHost = function_exists('auditResolveHostId') 
    ? auditResolveHostId($conn, $userId, $gameId, $rawHostId) 
    : ($rawHostId ?: 0);
$hostId = $resolvedHost > 0 ? $resolvedHost : null;

$mainTab    = trim((string)($input['main_tab'] ?? ''));
$nestedTab  = trim((string)($input['nested_tab'] ?? ''));
$actionType = strtoupper(trim((string)($input['action_type'] ?? '')));
$entityName = trim((string)($input['entity_name'] ?? ''));
$page       = trim((string)($input['page'] ?? ''));

if (empty($mainTab) && empty($nestedTab) && empty($actionType)) {
    echo json_encode(['status' => 'ignored', 'message' => 'No tab or action content specified']);
    exit;
}

// Determine ACTIVITY_TYPE
if (empty($actionType)) {
    if (!empty($nestedTab)) {
        $actionType = 'SUB_TAB_CLICK';
    } elseif (!empty($entityName)) {
        $actionType = 'MODAL_VIEW';
    } else {
        $actionType = 'TAB_CLICK';
    }
}

// Clean activity type string
$actionType = preg_replace('/[^A-Z0-9_]/', '', $actionType);
if (strlen($actionType) < 3) {
    $actionType = 'TAB_CLICK';
}

// Build human-readable Description
if (!empty($nestedTab)) {
    $description = "Clicked sub-tab: " . $nestedTab . ($mainTab ? " in " . $mainTab : "");
} elseif (!empty($entityName)) {
    $description = "Viewed " . ($mainTab ? $mainTab : "modal") . ": " . $entityName;
} else {
    $description = "Clicked tab: " . $mainTab;
}

// Build Meta JSON
$metaData = array_filter([
    'main_tab'    => $mainTab ?: null,
    'nested_tab'  => $nestedTab ?: null,
    'entity_name' => $entityName ?: null,
    'page'        => $page ?: null,
], function ($v) { return $v !== null; });

$metaJson = !empty($metaData) ? json_encode($metaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;

// IP Address
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ipParts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ipAddress = trim($ipParts[0]);
}

// Insert into ca_player_logs
$stmt = $conn->prepare("
    INSERT INTO ca_player_logs (USER_ID, HOST_ID, GAME_ID, ACTIVITY_TYPE, DESCRIPTION, META, IP_ADDRESS, CREATED_AT)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");

if ($stmt) {
    // Convert nulls for bind_param
    $bindUserId = $userId > 0 ? $userId : 0;
    $stmt->bind_param("iiissss", $bindUserId, $hostId, $gameId, $actionType, $description, $metaJson, $ipAddress);
    if ($stmt->execute()) {
        $logId = $stmt->insert_id;
        $stmt->close();
        echo json_encode(['status' => 'success', 'log_id' => $logId]);
        exit;
    } else {
        $error = $stmt->error;
        $stmt->close();
        echo json_encode(['status' => 'error', 'message' => $error]);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
    exit;
}
