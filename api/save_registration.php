<?php
session_start();
header('Content-Type: application/json');

$connFile = __DIR__ . "/../dbConnection_PDO.php";
if (!file_exists($connFile)) {
    echo json_encode(['success' => false, 'message' => 'DB Config missing.']);
    exit;
}
include $connFile;

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Security token mismatch.']);
    exit;
}

if (!empty($_POST['b_username'])) {
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $pdo->beginTransaction();

    $event_id = (int)$_POST['event_id'];

    // 1. Create the Team record
    $sqlTeam = "INSERT INTO to_teams (NAME, TOURNAMENT_ID) VALUES (:tname, :tid)";
    $stmtTeam = $pdo->prepare($sqlTeam);
    $stmtTeam->execute([
        ':tname' => strip_tags($_POST['team_name']),
        ':tid'   => $event_id
    ]);

    $new_team_id = $pdo->lastInsertId();

    /**
     * Helper Function: Updated for LEVEL, VERIFIED_LEVEL, LOG_STATUS, and PREMIUM
     */
    function processUserWithTeam($pdo, $p, $team_id)
    {
        $email = trim(filter_var($_POST[$p . '_email'], FILTER_VALIDATE_EMAIL));
        if (!$email) return null;

        $stmtCa = $pdo->prepare("SELECT ID FROM ca_users WHERE EMAIL = ? LIMIT 1");
        $stmtCa->execute([$email]);
        $caUser = $stmtCa->fetch(PDO::FETCH_ASSOC);

        $ca_id = $caUser ? $caUser['ID'] : null;
        $checkboxTicked = (isset($_POST[$p . '_exist']) && $_POST[$p . '_exist'] == 'Y');
        $isExisting = ($ca_id !== null || $checkboxTicked) ? 'Y' : 'N';

        // Get the skill level submitted from the form
        $skill_level = strip_tags($_POST[$p . '_skill'] ?? 'Intermediate +');

        // Prepare the Insert SQL with the new columns added
        $sql = "INSERT INTO to_users (
                    CA_ID, NAME, EMAIL, WHATSAPP_NUMBER, DOB, GENDER, CITY, 
                    COUNTRY, PROVINCE, AREA, EMAIL_PERMISSION, CALL_PERMISSION, 
                    GAMES, ADDRESS, CURRENCY, TIMEZONE_OFFSET, USERTYPE, 
                    TEAM_ID, EXISTING, REFFERAL_SOURCE, PASSWORD, 
                    LEVEL, VERIFIED_LEVEL, LOG_STATUS, PREMIUM
                ) VALUES (
                    :ca_id, :name, :email, :phone, :dob, :gender, :city, 
                    :country, :province, :area, :email_p, :call_p, 
                    :games, :address, :currency, :timezone, :usertype, 
                    :team_id, :existing, :ref, :password, 
                    :level, :verified_level, :log_status, :premium
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ca_id'          => $ca_id,
            ':name'           => strip_tags($_POST[$p . '_name']),
            ':email'          => $email,
            ':phone'          => strip_tags($_POST[$p . '_contact']),
            ':dob'            => $_POST[$p . '_dob'],
            ':gender'         => $_POST[$p . '_gender'],
            ':city'           => strip_tags($_POST[$p . '_city']),
            ':country'        => $_POST[$p . '_country'] ?? 'Canada',
            ':province'       => $_POST[$p . '_province'] ?? 'Ontario',
            ':area'           => $_POST[$p . '_area'] ?? '',
            ':email_p'        => $_POST['EMAIL_PERMISSION'],
            ':call_p'         => $_POST['CALL_PERMISSION'],
            ':games'          => $_POST['GAMES'],
            ':address'        => $_POST['ADDRESS'],
            ':currency'       => $_POST['CURRENCY'],
            ':timezone'       => $_POST['TIMEZONE_OFFSET'],
            ':usertype'       => $_POST['USERTYPE'],
            ':team_id'        => $team_id,
            ':existing'       => $isExisting,
            ':ref'            => $_POST['REFFERAL_SOURCE'],
            ':password'       => 'abcde', // Default Password Saved here
            ':level'          => $skill_level, // From dropdown
            ':verified_level' => $skill_level, // Same as LEVEL
            ':log_status'     => 'Y',          // Hardcoded to Y
            ':premium'        => 'Y'           // Hardcoded to Y
        ]);

        return $pdo->lastInsertId();
    } 
    
    // 2. Process Player 1 and Create Payment Entry
    $sqlPay = "INSERT INTO to_payments (USER_ID, GAME_ID, STATUS) VALUES (:uid, :gid, 'N')";
    $stmtPay = $pdo->prepare($sqlPay);

    $p1_id = processUserWithTeam($pdo, 'p1', $new_team_id);
    if ($p1_id) {
        $stmtPay->execute([':uid' => $p1_id, ':gid' => $event_id]);
    }

    // 3. Process Player 2
    $is_doubles = (isset($_POST['is_doubles']) && $_POST['is_doubles'] == '1');
    if ($is_doubles && !empty($_POST['p2_email'])) {
        $p2_id = processUserWithTeam($pdo, 'p2', $new_team_id);
        if ($p2_id) {
            $stmtPay->execute([':uid' => $p2_id, ':gid' => $event_id]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>