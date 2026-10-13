<?php
// Start buffering to prevent accidental whitespace from breaking JSON
ob_start();
header('Content-Type: application/json');

/* --- 1. DATABASE CONNECTION --- */
$host    = 'localhost';
$db      = 'casa_test';
$user    = 'casa_test';
$pass    = 'casa_test123#';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'DB Connection failed']);
    exit;
}

/* --- 2. HELPER FUNCTION --- */
// Converts empty strings to NULL (Critical for Date/Time/Numeric fields)
function clean($val) {
    $v = trim($val);
    return ($v === '') ? null : $v;
}

/* --- 3. MAIN LOGIC --- */
$action = $_POST['action'] ?? '';

try {
    // ACTION: TOGGLE STATUS (For the List Page)
    if ($action === 'toggle_status') {
        $id = $_POST['id'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("UPDATE to_tournaments SET STATUS = ? WHERE ID = ?");
        if ($stmt->execute([$status, $id])) {
            echo json_encode(['status' => 'success', 'message' => 'Status updated']);
        } else {
            throw new Exception("Failed to update status");
        }
    }

    // ACTION: UPDATE TOURNAMENT (For the Edit Page)
    elseif ($action === 'update') {
        $id = $_POST['event_id'];
        $conn->beginTransaction();

        // Skill Mapping (Sync with Add Page logic)
        $raw_skill = $_POST['skill_level'] ?? 'Int';
        $skill_map = ["Adv" => "Advance", "Int+" => "Intermediate", "Int" => "Intermediate", "All" => "All"];
        $gender_skill_level = $skill_map[$raw_skill] ?? 'Intermediate';

        $sql = "UPDATE to_tournaments SET 
                HOST_NAME = ?, CUP_NAME = ?, EVENT_COUNTRY = ?, EVENT_PROVINCE = ?, 
                EVENT_CITY = ?, EVENT_CURRENCY = ?, EVENT_VENUE = ?, EVENT_CATEGORY = ?, 
                GENDER_CATEGORY = ?, EVENT_TYPE = ?, GENDER_SKILL_LEVEL = ?, 
                EVENT_DATE = ?, EVENT_TIME = ?, TO_TIME = ?, 
                CANCEL_DATE = ?, CANCEL_TIME = ?, EVENT_COST = ?, 
                EVENT_DESCRIPTION = ?, EVENT_MESSAGE = ?,
                AMOUNT = ?, PAYMENT_ID = ?, PAYMENT_DEADLINE = ?, 
                REPORTING_TIME = ?, MATCH_START_TIME = ?, DRAW_ANNOUNCEMENT = ?, 
                SHUTTLE_TYPE = ?, MATCH_FORMAT = ?, POPUP_MESSAGE = ?, PAYMENT_MAIL = ?
                WHERE ID = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            clean($_POST['host_name']), 
            clean($_POST['cup_name']), 
            clean($_POST['event_country']), 
            clean($_POST['event_province']),
            clean($_POST['event_city']), 
            clean($_POST['event_currency']), 
            clean($_POST['event_venue']), 
            clean($_POST['event_category']),
            clean($_POST['gender_category']), 
            clean($_POST['event_type']), 
            $gender_skill_level,
            clean($_POST['event_date']), 
            clean($_POST['from_time']), 
            clean($_POST['to_time']), 
            clean($_POST['freeze_date']),   // Maps to CANCEL_DATE
            clean($_POST['freeze_time']),   // Maps to CANCEL_TIME
            clean($_POST['event_cost']),
            $_POST['event_description'], 
            $_POST['event_message'],
            clean($_POST['amount']),
            clean($_POST['payment_id']),
            clean($_POST['payment_deadline']),
            clean($_POST['reporting_time']),
            clean($_POST['match_start_time']),
            clean($_POST['draw_announcement']),
            clean($_POST['shuttle_type']),
            clean($_POST['match_format']),
            $_POST['popup_message'],
            $_POST['payment_mail'],
            $id
        ]);

        // Handle Image Upload
        if (!empty($_FILES['banner']['name']) && $_FILES['banner']['error'] === 0) {
            $targetDir = "../assets/images/tournaments_banner/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            $fileName = "banner_" . $id . "_" . time() . "." . pathinfo($_FILES["banner"]["name"], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES["banner"]["tmp_name"], $targetDir . $fileName)) {
                
                $checkBanner = $conn->prepare("SELECT ID FROM to_tournamet_banners WHERE EVENTS_ID = ?");
                $checkBanner->execute([$id]);
                
                if ($checkBanner->fetch()) {
                    $conn->prepare("UPDATE to_tournamet_banners SET IMGAE = ? WHERE EVENTS_ID = ?")->execute([$fileName, $id]);
                } else {
                    $conn->prepare("INSERT INTO to_tournamet_banners (EVENTS_ID, IMGAE) VALUES (?, ?)")->execute([$id, $fileName]);
                }
            }
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Tournament updated successfully!']);
    }

    // ACTION: SINGLE DELETE
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        $conn->prepare("DELETE FROM to_tournaments WHERE ID = ?")->execute([$id]);
        $conn->prepare("DELETE FROM to_tournamet_banners WHERE EVENTS_ID = ?")->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Deleted successfully']);
    }

    // ACTION: BULK DELETE
    elseif ($action === 'bulk_delete') {
        $ids = $_POST['ids'] ?? [];
        if (!empty($ids)) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $conn->prepare("DELETE FROM to_tournaments WHERE ID IN ($placeholders)")->execute($ids);
            $conn->prepare("DELETE FROM to_tournamet_banners WHERE EVENTS_ID IN ($placeholders)")->execute($ids);
            echo json_encode(['status' => 'success', 'message' => count($ids) . ' items deleted']);
        }
    }

} catch (Exception $e) {
    if ($conn->inTransaction()) { $conn->rollBack(); }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

ob_end_flush();