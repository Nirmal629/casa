<?php
/**
 * API: Save Club Settings
 * Handles POST from host-club-settings.php form.
 * Creates or updates a club row in ca_clubs for the logged-in host.
 */

session_start();
include('../dbConnection.php');

// ── Auth Check ──
if (!isset($_SESSION['user_id']) || (trim($_SESSION['usertype']) !== 'Host' && trim($_SESSION['usertype']) !== 'Trainer')) {
    header('Location: ../index.php');
    exit;
}

// ── Only accept POST ──
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../host-dashboard.php');
    exit;
}

// ── Sanitize Inputs ──
$host_id    = intval($_SESSION['user_id']);
$club_name  = mysqli_real_escape_string($conn, trim($_POST['club_name'] ?? ''));
$game_type  = mysqli_real_escape_string($conn, trim($_POST['game_type'] ?? 'Badminton'));
$category   = mysqli_real_escape_string($conn, trim($_POST['category'] ?? ''));
$club_info  = mysqli_real_escape_string($conn, trim($_POST['club_info'] ?? ''));
$schedule   = mysqli_real_escape_string($conn, trim($_POST['schedule'] ?? ''));
$cost_info  = mysqli_real_escape_string($conn, trim($_POST['cost_info'] ?? ''));
$status     = mysqli_real_escape_string($conn, trim($_POST['status'] ?? 'Active'));

// ── Validate Required Fields ──
if (empty($club_name)) {
    header('Location: ../host-dashboard.php?club_error=Club+name+is+required#ClubSettings');
    exit;
}

if (!in_array($status, ['Active', 'Inactive'])) {
    $status = 'Active';
}

// ── Fetch Host Location Details from ca_users ──
$host_query = mysqli_query($conn, "SELECT COUNTRY, PROVINCE, CITY FROM ca_users WHERE ID = '$host_id' LIMIT 1");
$host_data  = mysqli_fetch_assoc($host_query);

$country  = mysqli_real_escape_string($conn, trim($host_data['COUNTRY'] ?? ''));
$province = mysqli_real_escape_string($conn, trim($host_data['PROVINCE'] ?? ''));
$city     = mysqli_real_escape_string($conn, trim($host_data['CITY'] ?? ''));

$join_type = mysqli_real_escape_string($conn, trim($_POST['join_type'] ?? 'A'));
if (!in_array($join_type, ['A', 'R', 'H'])) {
    $join_type = 'A';
}

// ── Check if club already exists for this host ──
$check = mysqli_query($conn, "SELECT id, logo FROM ca_clubs WHERE host_id = '$host_id' LIMIT 1");
$existing = mysqli_fetch_assoc($check);

// ── Process Club Logo Upload ──
$logo_filename = null;
if (isset($_FILES['club_logo']) && $_FILES['club_logo']['error'] === UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['club_logo']['tmp_name'];
    $file_name = $_FILES['club_logo']['name'];
    $file_size = $_FILES['club_logo']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (in_array($file_ext, $allowed_exts)) {
        if ($file_size <= 2 * 1024 * 1024) { // 2MB max
            // Ensure target directory exists
            $target_dir = '../uploads/clubs/';
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Unique filename to prevent overwriting
            $new_filename = uniqid('club_', true) . '.' . $file_ext;
            $dest_path = $target_dir . $new_filename;

            if (move_uploaded_file($file_tmp, $dest_path)) {
                $logo_filename = $new_filename;

                // Delete previous logo if one existed
                if ($existing && !empty($existing['logo'])) {
                    $old_logo_file = $target_dir . $existing['logo'];
                    if (file_exists($old_logo_file)) {
                        unlink($old_logo_file);
                    }
                }
            } else {
                header('Location: ../host-dashboard.php?club_error=Failed+to+save+uploaded+image.#ClubSettings');
                exit;
            }
        } else {
            header('Location: ../host-dashboard.php?club_error=Image+must+be+less+than+2MB.#ClubSettings');
            exit;
        }
    } else {
        header('Location: ../host-dashboard.php?club_error=Invalid+image+format.+Supported+formats:+JPG,+PNG,+GIF,+WebP.#ClubSettings');
        exit;
    }
}

if ($existing) {
    // Determine logo column updates
    $logo_sql = "";
    if ($logo_filename !== null) {
        $logo_sql = "logo = '$logo_filename',";
    }

    // ── UPDATE existing club ──
    $sql = "UPDATE ca_clubs SET
                club_name  = '$club_name',
                game_type  = '$game_type',
                category   = '$category',
                country    = '$country',
                province   = '$province',
                city       = '$city',
                club_info  = '$club_info',
                schedule   = '$schedule',
                cost_info  = '$cost_info',
                join_type  = '$join_type',
                $logo_sql
                status     = '$status',
                updated_at = CURRENT_TIMESTAMP
            WHERE host_id = '$host_id'";
} else {
    // ── INSERT new club ──
    $inserted_logo = ($logo_filename !== null) ? $logo_filename : '';
    $sql = "INSERT INTO ca_clubs
                (host_id, club_name, game_type, category, country, province, city, club_info, schedule, cost_info, join_type, logo, status)
            VALUES
                ('$host_id', '$club_name', '$game_type', '$category', '$country', '$province', '$city', '$club_info', '$schedule', '$cost_info', '$join_type', '$inserted_logo', '$status')";
}

if (mysqli_query($conn, $sql)) {
    if ($join_type === 'H') {
        // We need the club_id. If we just updated, fetch it. If we inserted, get insert_id.
        $target_club_id = isset($existing['id']) ? $existing['id'] : mysqli_insert_id($conn);
        require_once __DIR__ . '/sync_home_clubs.php';
        syncHomeClubToPlayers($conn, $target_club_id, $host_id, $country, $province, $city, $game_type);
    }

    header('Location: ../host-dashboard.php?club_saved=1#ClubSettings');
} else {
    $error = urlencode(mysqli_error($conn));
    header('Location: ../host-dashboard.php?club_error=' . $error . '#ClubSettings');
}

$conn->close();
exit;
?>
