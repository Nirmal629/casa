<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// 1. Load Database Config
$connFile = __DIR__ . "/../dbConnection_PDO.php";
if (file_exists($connFile)) {
    include $connFile;
}

// Fallback credentials if include fails
if (empty($user)) {
    $host = 'localhost'; $db = 'casa_test'; $user = 'casa_test'; $pass = 'casa_test123#';
}

// 2. SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login.']);
    exit;
}
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Security token mismatch.']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $ca_id         = $_SESSION['user_id']; // ID from ca_users
    $tournament_id = (int)$_POST['tournament_id']; // This is GAME_ID

    // -----------------------------------------------------------------
    // 3. FIND THE to_users.ID FOR THIS PLAYER IN THIS TOURNAMENT
    // -----------------------------------------------------------------
    $stmtUser = $pdo->prepare("
        SELECT tu.ID 
        FROM to_users tu 
        JOIN to_teams tt ON tu.TEAM_ID = tt.ID 
        WHERE tu.CA_ID = ? AND tt.TOURNAMENT_ID = ? 
        LIMIT 1
    ");
    $stmtUser->execute([$ca_id, $tournament_id]);
    $to_user_id = $stmtUser->fetchColumn();

    if (!$to_user_id) {
        echo json_encode(['success' => false, 'message' => 'Record not found. Ensure you have joined the tournament first.']);
        exit;
    }

    // 4. PRICE VERIFICATION
    $stmtPrice = $pdo->prepare("SELECT AMOUNT FROM to_tournaments WHERE ID = ? LIMIT 1");
    $stmtPrice->execute([$tournament_id]);
    $db_price = $stmtPrice->fetchColumn();

    $paid_amount = filter_var($_POST['payment_amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    if ((float)$paid_amount < (float)$db_price) {
        echo json_encode(['success' => false, 'message' => 'Amount too low. Required: CAD ' . $db_price]);
        exit;
    }

    // -----------------------------------------------------------------
    // 5. UPDATE THE EXISTING PAYMENT ROW
    // -----------------------------------------------------------------
    // We update where USER_ID matches the participant ID and GAME_ID matches the tournament ID
    $sql = "UPDATE to_payments SET 
                AMOUNT = ?, 
                PAYMENT_DATE = ?, 
                PAYMENT_TIME = ?, 
                PAYMENT_TYPE = ?, 
                DETAILS = ?, 
                MESSAGE = ?, 
                STATUS = 'Y' 
            WHERE USER_ID = ? AND GAME_ID = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $paid_amount,
        $_POST['payment_date'],
        $_POST['payment_time'],
        htmlspecialchars($_POST['payment_type']),
        htmlspecialchars($_POST['payment_details']),
        htmlspecialchars($_POST['payment_message']),
        $to_user_id,     // The participant ID found earlier
        $tournament_id   // The GAME_ID
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'paid_amount' => number_format($paid_amount, 2)
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'No payment record found to update. Please contact support.'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}