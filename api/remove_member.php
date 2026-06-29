<?php
// api/remove_member.php
// Rejects a pending join request or removes an accepted member from the roster.

session_start();
include('../dbConnection.php');

header('Content-Type: application/json');

// Auth Check: must be logged in as Host or Trainer
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype'], ['Host', 'Trainer'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Only hosts can modify the roster.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$action = trim($_POST['action'] ?? 'remove'); // 'reject' or 'remove'

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid record ID.']);
    exit;
}

// Fetch record details and verify ownership
$stmt = $conn->prepare("SELECT club_id, host_id, player_id, status FROM ca_player_club_status WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
    echo json_encode(['status' => 'error', 'message' => 'Roster record not found.']);
    exit;
}

$record = $res->fetch_assoc();
$host_id = intval($record['host_id']);
$player_id = intval($record['player_id']);

if ($host_id !== intval($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. You do not own this roster context.']);
    exit;
}

// Delete from ca_player_club_status
$del_stmt = $conn->prepare("DELETE FROM ca_player_club_status WHERE id = ?");
$del_stmt->bind_param("i", $id);

if ($del_stmt->execute()) {


    $msg = ($action === 'reject') ? 'Join request rejected.' : 'Member removed from roster.';
    echo json_encode([
        'status' => 'success',
        'message' => $msg
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database deletion failed: ' . $conn->error]);
}

$conn->close();
exit;
?>
