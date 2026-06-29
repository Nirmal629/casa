<?php
// Clear any previous output buffers to ensure clean JSON
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
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
    exit;
}

/* --- 2. HELPER FUNCTION --- */
// Converts empty strings to NULL (Critical for Date/Time/Numeric fields)
function clean($val) {
    $v = trim($val);
    return ($v === '') ? null : $v;
}

/* --- 3. MAIN LOGIC --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Map and Clean Form Data
        $host_id           = 1;
        $host_name         = clean($_POST['host_name'] ?? '');
        $cup_name          = clean($_POST['cup_name'] ?? '');
        $event_country     = clean($_POST['event_country'] ?? '');
        $event_province    = clean($_POST['event_province'] ?? '');
        $event_city        = clean($_POST['event_city'] ?? '');
        $event_currency    = clean($_POST['event_currency'] ?? '');
        $event_venue       = clean($_POST['event_venue'] ?? '');
        $event_category    = clean($_POST['event_category'] ?? '');
        $event_type        = clean($_POST['event_type'] ?? '');

        // Skill Level Mapping
        $raw_skill = $_POST['skill_level'] ?? 'Int';
        $skill_map = [
            "Adv" => "Advance", // Matches your DB Enum 'Advance'
            "Int+" => "Intermediate", // Adjust based on your exact DB Enum string
            "Int" => "Intermediate", 
            "All" => "All"
        ];
        $gender_skill_level = $skill_map[$raw_skill] ?? 'Intermediate';
        $gender_category    = clean($_POST['gender_category'] ?? 'Mixed');

        // Dates & Times (Using clean() to ensure empty = NULL)
        $event_date        = clean($_POST['event_date']);
        $event_time        = clean($_POST['from_time']);
        $to_time           = clean($_POST['to_time']);
        $freeze_date       = clean($_POST['freeze_date']);
        $freeze_time       = clean($_POST['freeze_time']);
        
        // Numbers
        $event_cost        = clean($_POST['event_cost']) ?? 0;
        $amount_display    = clean($_POST['amount']) ?? 0;

        // Schedule & Match Fields
        $reporting_time    = clean($_POST['reporting_time']);
        $match_start_time  = clean($_POST['match_start_time']);
        $draw_announcement = clean($_POST['draw_announcement']);
        $shuttle_type      = clean($_POST['shuttle_type']) ?? 'Feather';
        $match_format      = clean($_POST['match_format']);
        $payment_id        = clean($_POST['payment_id']);
        $payment_deadline  = clean($_POST['payment_deadline']);

        // Rich Text (CKEditor)
        $event_description = $_POST['event_description'] ?? '';
        $event_message     = $_POST['event_message'] ?? '';
        $popup_message     = $_POST['popup_message'] ?? '';
        $payment_mail      = $_POST['payment_mail'] ?? '';

        // 3. Updated SQL Query
        $sqlEvent = "INSERT INTO `to_tournaments` (
                        `HOST_ID`, `HOST_NAME`, `CUP_NAME`, `EVENT_COUNTRY`, `EVENT_PROVINCE`, 
                        `EVENT_CITY`, `EVENT_CURRENCY`, `EVENT_VENUE`, `EVENT_CATEGORY`, 
                        `GENDER_CATEGORY`, `GENDER_SKILL_LEVEL`, `EVENT_TYPE`, `EVENT_DATE`, 
                        `EVENT_TIME`, `TO_TIME`, `CANCEL_DATE`, `CANCEL_TIME`, `EVENT_COST`, 
                        `EVENT_DESCRIPTION`, `EVENT_MESSAGE`, `STATUS`, `AMOUNT`, 
                        `PAYMENT_ID`, `PAYMENT_DEADLINE`, `REPORTING_TIME`, `MATCH_START_TIME`, 
                        `DRAW_ANNOUNCEMENT`, `SHUTTLE_TYPE`, `MATCH_FORMAT`, `POPUP_MESSAGE`, 
                        `PAYMENT_MAIL`, `AUTOMATION`
                    ) VALUES (
                        :host_id, :host_name, :cup_name, :country, :province, 
                        :city, :currency, :venue, :cat, 
                        :gender, :skill, :type, :e_date, 
                        :e_time, :to_time, :c_date, :c_time, :cost, 
                        :descr, :msg, 'Active', :amt, 
                        :pay_id, :pay_dl, :rep_time, :m_start, 
                        :draw_ann, :shuttle, :format, :popup, 
                        :mail, 'Y'
                    )";

        $stmt = $conn->prepare($sqlEvent);
        $stmt->execute([
            ':host_id'   => $host_id,
            ':host_name' => $host_name,
            ':cup_name'  => $cup_name,
            ':country'   => $event_country,
            ':province'  => $event_province,
            ':city'      => $event_city,
            ':currency'  => $event_currency,
            ':venue'     => $event_venue,
            ':cat'       => $event_category,
            ':gender'    => $gender_category,
            ':skill'     => $gender_skill_level,
            ':type'      => $event_type,
            ':e_date'    => $event_date,
            ':e_time'    => $event_time,
            ':to_time'   => $to_time,
            ':c_date'    => $freeze_date,
            ':c_time'    => $freeze_time,
            ':cost'      => $event_cost,
            ':descr'     => $event_description,
            ':msg'       => $event_message,
            ':amt'       => $amount_display,
            ':pay_id'    => $payment_id,
            ':pay_dl'    => $payment_deadline,
            ':rep_time'  => $reporting_time,
            ':m_start'   => $match_start_time,
            ':draw_ann'  => $draw_announcement,
            ':shuttle'   => $shuttle_type,
            ':format'    => $match_format,
            ':popup'     => $popup_message,
            ':mail'      => $payment_mail
        ]);

        $eventId = $conn->lastInsertId();

        // 4. Banner Upload Logic
        if (isset($_FILES['banner']) && $_FILES['banner']['error'] === 0) {
            $uploadDir = "../assets/images/tournaments_banner/";
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }

            $ext = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
            $fileName = "banner_" . $eventId . "_" . time() . "." . $ext;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['banner']['tmp_name'], $targetPath)) {
                $sqlBanner = "INSERT INTO `to_tournamet_banners` (`EVENTS_ID`, `IMGAE`) VALUES (:eid, :img)";
                $stmtBanner = $conn->prepare($sqlBanner);
                $stmtBanner->execute([':eid' => $eventId, ':img' => $fileName]);
            }
        }

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Tournament created successfully!"]);
    } catch (Exception $e) {
        if ($conn->inTransaction()) { $conn->rollBack(); }
        echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
    }
}
ob_end_flush();