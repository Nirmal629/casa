<?php
/**
 * API: Join Club
 * Handles POST request from player-hub.php to join a club.
 */

session_start();
include('../dbConnection.php');

header('Content-Type: application/json');

// ── Auth Check ──
if (!isset($_SESSION['user_id']) || trim($_SESSION['usertype']) !== 'Player') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Only players can join clubs.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$player_id = intval($_SESSION['user_id']);
$club_id   = intval($_POST['club_id'] ?? 0);

if ($club_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid club ID.']);
    exit;
}

// ── Fetch Club Details ──
$club_query = $conn->prepare("SELECT id, host_id, country, province, city, join_type FROM ca_clubs WHERE id = ? AND status = 'Active' LIMIT 1");
$club_query->bind_param("i", $club_id);
$club_query->execute();
$club_res = $club_query->get_result();

if ($club_res->num_rows !== 1) {
    echo json_encode(['status' => 'error', 'message' => 'Club not found or inactive.']);
    exit;
}

$club = $club_res->fetch_assoc();
$host_id = intval($club['host_id']);
$join_type = $club['join_type'];

// ── Check if already joined/pending ──
$check_query = $conn->prepare("SELECT status FROM ca_player_club_status WHERE club_id = ? AND player_id = ? LIMIT 1");
$check_query->bind_param("ii", $club_id, $player_id);
$check_query->execute();
$check_res = $check_query->get_result();

if ($check_res->num_rows === 1) {
    $existing = $check_res->fetch_assoc();
    echo json_encode([
        'status' => 'success', 
        'membership_status' => $existing['status'], 
        'message' => 'You already have a ' . $existing['status'] . ' membership/request with this club.'
    ]);
    exit;
}

// ── Fetch Player Details ──
$player_query = $conn->prepare("SELECT COUNTRY, PROVINCE, CITY FROM ca_users WHERE ID = ? LIMIT 1");
$player_query->bind_param("i", $player_id);
$player_query->execute();
$player_res = $player_query->get_result();
$player = $player_res->fetch_assoc();

$player_country  = trim($player['COUNTRY'] ?? '');
$player_province = trim($player['PROVINCE'] ?? '');
$player_city     = trim($player['CITY'] ?? '');

$club_country  = trim($club['country'] ?? '');
$club_province = trim($club['province'] ?? '');
$club_city     = trim($club['city'] ?? '');

// ── Determine Status based on join_type ──
$membership_status = 'pending'; // Default

if ($join_type === 'A') {
    // All: Auto-join approved instantly
    $membership_status = 'accepted';
} elseif ($join_type === 'H') {
    // Home: Auto-join if country, province, city match case-insensitively
    $country_match  = (strcasecmp($player_country, $club_country) === 0);
    $province_match = (strcasecmp($player_province, $club_province) === 0);
    $city_match     = (strcasecmp($player_city, $club_city) === 0);

    if ($country_match && $province_match && $city_match && !empty($player_country)) {
        $membership_status = 'accepted';
    } else {
        $membership_status = 'pending';
    }
} elseif ($join_type === 'R') {
    // Request: Needs Host Moderation
    $membership_status = 'pending';
}

// ── Save Membership Status ──
$insert_query = $conn->prepare("INSERT INTO ca_player_club_status (club_id, host_id, player_id, status) VALUES (?, ?, ?, ?)");
$insert_query->bind_param("iiis", $club_id, $host_id, $player_id, $membership_status);

if ($insert_query->execute()) {

    
    echo json_encode([
        'status' => 'success',
        'membership_status' => $membership_status,
        'message' => ($membership_status === 'accepted') ? 'Successfully joined the club!' : 'Join request submitted. Awaiting host approval.'
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
}

$conn->close();
exit;
