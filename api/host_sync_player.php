<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!in_array(trim((string)($_SESSION['usertype'] ?? '')), ['Host', 'Trainer'], true)) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Not authorized.']); exit; }
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Invalid security token.']); exit; }

require_once __DIR__ . '/../dbConnection_PDO.php';
$hostId = (int)($_SESSION['user_id'] ?? 0);

function hostTournament(PDO $pdo, int $tournamentId, int $hostId): void {
    $check = $pdo->prepare('SELECT ID FROM to_tournaments WHERE ID = ? AND HOST_ID = ? LIMIT 1');
    $check->execute([$tournamentId, $hostId]);
    if (!$check->fetchColumn()) { throw new RuntimeException('You do not have permission to manage this tournament.'); }
}
function hostRegistration(PDO $pdo, int $userId, int $hostId): array {
    $check = $pdo->prepare('SELECT u.*, t.ID AS TEAM_ID_VAL, t.TOURNAMENT_ID FROM to_users u JOIN to_teams t ON u.TEAM_ID = t.ID JOIN to_tournaments tr ON t.TOURNAMENT_ID = tr.ID WHERE u.ID = ? AND tr.HOST_ID = ? LIMIT 1');
    $check->execute([$userId, $hostId]); $row = $check->fetch(PDO::FETCH_ASSOC);
    if (!$row) { throw new RuntimeException('Registration not found or access denied.'); }
    return $row;
}
function createCasaUser(PDO $pdo, array $user): int {
    $existing = $pdo->prepare('SELECT ID FROM ca_users WHERE EMAIL = ? LIMIT 1'); $existing->execute([trim((string)$user['EMAIL'])]);
    $id = $existing->fetchColumn(); if ($id) { return (int)$id; }
    $insert = $pdo->prepare('INSERT INTO ca_users (NAME, EMAIL, PASSWORD, LEVEL, VERIFIED_LEVEL, EMAIL_PERMISSION, WHATSAPP_NUMBER, CALL_PERMISSION, DOB, GENDER, GAMES, ADDRESS, CITY, COUNTRY, PROVINCE, AREA, CURRENCY, TIMEZONE_OFFSET, USERTYPE, PROFILE_IMAGE, PREMIUM, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())');
    $insert->execute([$user['NAME'], $user['EMAIL'], $user['PASSWORD'], $user['LEVEL'], $user['VERIFIED_LEVEL'], $user['EMAIL_PERMISSION'], $user['WHATSAPP_NUMBER'], $user['CALL_PERMISSION'], $user['DOB'], $user['GENDER'], $user['GAMES'], $user['ADDRESS'], $user['CITY'], $user['COUNTRY'], $user['PROVINCE'], $user['AREA'], $user['CURRENCY'], $user['TIMEZONE_OFFSET'], $user['USERTYPE'], $user['PROFILE_IMAGE'], $user['PREMIUM']]);
    return (int)$pdo->lastInsertId();
}

try {
    $action = $_POST['action'] ?? ''; $tournamentId = (int)($_POST['tournament_id'] ?? 0);
    if ($hostId <= 0) { throw new RuntimeException('Invalid host session.'); }
    switch ($action) {
        case 'approve_payment':
        case 'decline_payment':
            $user = hostRegistration($pdo, (int)($_POST['user_id'] ?? 0), $hostId);
            hostTournament($pdo, (int)$user['TOURNAMENT_ID'], $hostId);
            if ($action === 'approve_payment') { $stmt=$pdo->prepare("UPDATE to_payments SET STATUS='Y', APPROVED_BY=?, APPROVED_DATE=NOW(), DECLINED_DATE=NULL WHERE USER_ID=? AND GAME_ID=?"); $stmt->execute([$hostId,$user['ID'],$user['TOURNAMENT_ID']]); }
            else { $stmt=$pdo->prepare("UPDATE to_payments SET STATUS='N', DECLINED_DATE=NOW(), APPROVED_DATE=NULL, APPROVED_BY=NULL WHERE USER_ID=? AND GAME_ID=?"); $stmt->execute([$user['ID'],$user['TOURNAMENT_ID']]); }
            echo json_encode(['success'=>true]); break;
        case 'get':
        case 'create':
            $user = hostRegistration($pdo, (int)($_POST['id'] ?? 0), $hostId);
            if ($action === 'get') { $stmt=$pdo->prepare('SELECT ID FROM ca_users WHERE EMAIL = ? LIMIT 1'); $stmt->execute([trim((string)$user['EMAIL'])]); $caId=$stmt->fetchColumn(); if (!$caId) { throw new RuntimeException('Email not found in CASA users.'); } $status='SUCCESSFUL'; }
            else { $caId=createCasaUser($pdo,$user); $status='PLAYER CREATED'; }
            $pdo->prepare('UPDATE to_users SET CA_ID = ? WHERE ID = ?')->execute([$caId,$user['ID']]); echo json_encode(['success'=>true,'ca_id'=>$caId,'status_text'=>$status]); break;
        case 'delete_team':
            $user = hostRegistration($pdo, (int)($_POST['id'] ?? 0), $hostId); $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM to_users WHERE TEAM_ID = ?')->execute([$user['TEAM_ID_VAL']]); $pdo->prepare('DELETE FROM to_teams WHERE ID = ?')->execute([$user['TEAM_ID_VAL']]); $pdo->commit(); echo json_encode(['success'=>true]); break;
        case 'update_team':
            hostTournament($pdo, $tournamentId, $hostId); $teamId=(int)($_POST['team_id'] ?? 0); $teamName=trim($_POST['team_name'] ?? ''); $players=$_POST['players'] ?? [];
            if ($teamId<=0 || $teamName==='' || !is_array($players) || !$players) { throw new RuntimeException('Team name and player details are required.'); }
            $check=$pdo->prepare('SELECT t.ID FROM to_teams t JOIN to_tournaments tr ON t.TOURNAMENT_ID = tr.ID WHERE t.ID = ? AND tr.ID = ? AND tr.HOST_ID = ?'); $check->execute([$teamId,$tournamentId,$hostId]); if (!$check->fetchColumn()) { throw new RuntimeException('Team not found or access denied.'); }
            $pdo->beginTransaction(); $pdo->prepare('UPDATE to_teams SET NAME = ? WHERE ID = ?')->execute([$teamName,$teamId]);
            $playerCheck=$pdo->prepare('SELECT ID FROM to_users WHERE ID = ? AND TEAM_ID = ?'); $update=$pdo->prepare('UPDATE to_users SET NAME=?, WHATSAPP_NUMBER=?, EMAIL=?, DOB=?, GENDER=?, CITY=?, EXISTING=? WHERE ID=? AND TEAM_ID=?');
            foreach ($players as $playerId=>$player) { $playerId=(int)$playerId; $playerCheck->execute([$playerId,$teamId]); if (!$playerCheck->fetchColumn()) { throw new RuntimeException('A player does not belong to this team.'); } $name=trim($player['name'] ?? ''); $email=trim($player['email'] ?? ''); if ($name==='' || $email==='') { throw new RuntimeException('Player name and email are required.'); } $update->execute([$name,trim($player['whatsapp'] ?? ''),$email,($player['dob'] ?? '') ?: null,($player['gender'] ?? '')==='Female'?'Female':'Male',trim($player['city'] ?? ''),isset($player['exist'])?'Y':'N',$playerId,$teamId]); }
            $pdo->commit(); echo json_encode(['success'=>true]); break;
        default: throw new RuntimeException('Invalid action.');
    }
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(400); echo json_encode(['success'=>false,'message'=>$exception->getMessage()]);
}
