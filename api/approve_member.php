<?php
// api/approve_member.php
// Approves a player join request and maps them to the host context.

session_start();
include('../dbConnection.php');

header('Content-Type: application/json');

// Auth Check: must be logged in as Host or Trainer
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype'], ['Host', 'Trainer'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Only hosts can approve members.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$request_id = intval($_POST['request_id'] ?? 0);

if ($request_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request ID.']);
    exit;
}

// Fetch request details and verify ownership
$stmt = $conn->prepare("SELECT club_id, host_id, player_id, status FROM ca_player_club_status WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
    echo json_encode(['status' => 'error', 'message' => 'Request not found.']);
    exit;
}

$req = $res->fetch_assoc();
$host_id = intval($req['host_id']);
$player_id = intval($req['player_id']);

if ($host_id !== intval($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. You do not own this club request.']);
    exit;
}

// Update status to accepted
$update_stmt = $conn->prepare("UPDATE ca_player_club_status SET status = 'accepted' WHERE id = ?");
$update_stmt->bind_param("i", $request_id);

if ($update_stmt->execute()) {


    echo json_encode([
        'status' => 'success',
        'message' => 'Player request approved successfully!'
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to approve request: ' . $conn->error]);
}

$conn->close();
exit;
?>
