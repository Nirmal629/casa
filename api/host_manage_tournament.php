<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!in_array(trim((string)($_SESSION['usertype'] ?? '')), ['Host', 'Trainer'], true)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'You are not authorized to edit tournaments.']);
    exit;
}

$hostId = (int)($_SESSION['user_id'] ?? 0);
$tournamentId = (int)($_POST['event_id'] ?? 0);
if ($hostId <= 0 || $tournamentId <= 0) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Invalid tournament request.']);
    exit;
}

require_once __DIR__ . '/../dbConnection_PDO.php';

function valueOrNull($value) {
    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

try {
    $ownership = $pdo->prepare('SELECT ID FROM to_tournaments WHERE ID = ? AND HOST_ID = ? LIMIT 1');
    $ownership->execute([$tournamentId, $hostId]);
    if (!$ownership->fetch()) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'You can only edit your own tournaments.']);
        exit;
    }

    $skillMap = ['Adv' => 'Advance', 'Int+' => 'Intermediate+', 'Int' => 'Intermediate', 'All' => 'All'];
    $skill = $skillMap[$_POST['skill_level'] ?? 'Int'] ?? 'Intermediate';

    $pdo->beginTransaction();
    $sql = 'UPDATE to_tournaments SET
        HOST_NAME = ?, CUP_NAME = ?, EVENT_COUNTRY = ?, EVENT_PROVINCE = ?, EVENT_CITY = ?, EVENT_CURRENCY = ?,
        EVENT_VENUE = ?, EVENT_CATEGORY = ?, GENDER_CATEGORY = ?, EVENT_TYPE = ?, GENDER_SKILL_LEVEL = ?,
        EVENT_DATE = ?, EVENT_TIME = ?, TO_TIME = ?, CANCEL_DATE = ?, CANCEL_TIME = ?, EVENT_COST = ?,
        EVENT_DESCRIPTION = ?, EVENT_MESSAGE = ?, AMOUNT = ?, PAYMENT_ID = ?, PAYMENT_DEADLINE = ?,
        REPORTING_TIME = ?, MATCH_START_TIME = ?, DRAW_ANNOUNCEMENT = ?, SHUTTLE_TYPE = ?, MATCH_FORMAT = ?,
        POPUP_MESSAGE = ?, PAYMENT_MAIL = ?
        WHERE ID = ? AND HOST_ID = ?';
    $statement = $pdo->prepare($sql);
    $statement->execute([
        valueOrNull($_POST['host_name'] ?? ''), valueOrNull($_POST['cup_name'] ?? ''), valueOrNull($_POST['event_country'] ?? ''), valueOrNull($_POST['event_province'] ?? ''), valueOrNull($_POST['event_city'] ?? ''), valueOrNull($_POST['event_currency'] ?? ''),
        valueOrNull($_POST['event_venue'] ?? ''), valueOrNull($_POST['event_category'] ?? ''), valueOrNull($_POST['gender_category'] ?? ''), valueOrNull($_POST['event_type'] ?? ''), $skill,
        valueOrNull($_POST['event_date'] ?? ''), valueOrNull($_POST['from_time'] ?? ''), valueOrNull($_POST['to_time'] ?? ''), valueOrNull($_POST['freeze_date'] ?? ''), valueOrNull($_POST['freeze_time'] ?? ''), valueOrNull($_POST['event_cost'] ?? ''),
        $_POST['event_description'] ?? '', $_POST['event_message'] ?? '', valueOrNull($_POST['amount'] ?? ''), valueOrNull($_POST['payment_id'] ?? ''), valueOrNull($_POST['payment_deadline'] ?? ''),
        valueOrNull($_POST['reporting_time'] ?? ''), valueOrNull($_POST['match_start_time'] ?? ''), valueOrNull($_POST['draw_announcement'] ?? ''), valueOrNull($_POST['shuttle_type'] ?? ''), valueOrNull($_POST['match_format'] ?? ''),
        $_POST['popup_message'] ?? '', $_POST['payment_mail'] ?? '', $tournamentId, $hostId
    ]);

    if (!empty($_FILES['banner']['name']) && ($_FILES['banner']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $extension = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            throw new RuntimeException('Please upload a valid image file.');
        }
        $uploadDirectory = __DIR__ . '/../assets/images/tournaments_banner/';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
            throw new RuntimeException('Unable to prepare the banner upload folder.');
        }
        $fileName = 'banner_' . $tournamentId . '_' . time() . '.' . $extension;
        if (!move_uploaded_file($_FILES['banner']['tmp_name'], $uploadDirectory . $fileName)) {
            throw new RuntimeException('Unable to upload the banner image.');
        }
        $existingBanner = $pdo->prepare('SELECT ID FROM to_tournamet_banners WHERE EVENTS_ID = ? LIMIT 1');
        $existingBanner->execute([$tournamentId]);
        if ($existingBanner->fetch()) {
            $banner = $pdo->prepare('UPDATE to_tournamet_banners SET IMGAE = ? WHERE EVENTS_ID = ?');
            $banner->execute([$fileName, $tournamentId]);
        } else {
            $banner = $pdo->prepare('INSERT INTO to_tournamet_banners (EVENTS_ID, IMGAE) VALUES (?, ?)');
            $banner->execute([$tournamentId, $fileName]);
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Tournament updated successfully.']);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $exception->getMessage()]);
}
