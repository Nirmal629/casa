<?php
session_start();
error_reporting(0); 
header('Content-Type: application/json');

/* --- 1. DATABASE CONNECTION (PDO) --- */
$host    = 'localhost';
$db      = 'casa_test';
$user    = 'casa_test';
$pass    = 'casa_test123#';
$charset = 'utf8mb4';

if (empty($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid Security Token']); exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'approve_payment':
            $uid = (int)$_POST['user_id'];
            $gid = (int)$_POST['game_id'];
            $admin_id = $_SESSION['user_id'] ?? 0;

            $stmt = $pdo->prepare("UPDATE to_payments SET 
                STATUS = 'Y', 
                APPROVED_BY = ?, 
                APPROVED_DATE = NOW(), 
                DECLINED_DATE = NULL 
                WHERE USER_ID = ? AND GAME_ID = ?");
            $stmt->execute([$admin_id, $uid, $gid]);
            
            echo json_encode(['success' => true, 'message' => 'Payment Approved Successfully']);
            break;

        case 'decline_payment':
            $uid = (int)$_POST['user_id'];
            $gid = (int)$_POST['game_id'];

            $stmt = $pdo->prepare("UPDATE to_payments SET 
                STATUS = 'N', 
                DECLINED_DATE = NOW(), 
                APPROVED_DATE = NULL, 
                APPROVED_BY = NULL 
                WHERE USER_ID = ? AND GAME_ID = ?");
            $stmt->execute([$uid, $gid]);
            
            echo json_encode(['success' => true, 'message' => 'Payment Declined Successfully']);
            break;

        case 'get':
        case 'create': 
            $id = (int)$_POST['id'];
            $st = $pdo->prepare("SELECT * FROM to_users WHERE ID = ?"); $st->execute([$id]);
            $u = $st->fetch(PDO::FETCH_ASSOC);
            if(!$u) throw new Exception("User not found");

            $ca = $pdo->prepare("SELECT ID FROM ca_users WHERE EMAIL = ?"); $ca->execute([trim($u['EMAIL'])]);
            $ca_row = $ca->fetch(PDO::FETCH_ASSOC);

            if ($action == 'get') {
                if(!$ca_row) throw new Exception("Email not found in central DB");
                $new_id = $ca_row['ID']; $status = 'SUCCESSFUL';
            } else {
                if($ca_row) { 
                    $new_id = $ca_row['ID']; 
                } 
                else {
                    // Updated INSERT to include all profile fields from to_users
                    $ins = $pdo->prepare("INSERT INTO ca_users (
                        NAME, EMAIL, PASSWORD, LEVEL, VERIFIED_LEVEL, 
                        EMAIL_PERMISSION, WHATSAPP_NUMBER, CALL_PERMISSION, 
                        DOB, GENDER, GAMES, ADDRESS, CITY, COUNTRY, 
                        PROVINCE, AREA, CURRENCY, TIMEZONE_OFFSET, 
                        USERTYPE, PROFILE_IMAGE, PREMIUM, created_at
                    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
                    
                    $ins->execute([
                        $u['NAME'], 
                        $u['EMAIL'], 
                        $u['PASSWORD'], 
                        $u['LEVEL'], 
                        $u['VERIFIED_LEVEL'],
                        $u['EMAIL_PERMISSION'], 
                        $u['WHATSAPP_NUMBER'], 
                        $u['CALL_PERMISSION'],
                        $u['DOB'], 
                        $u['GENDER'], 
                        $u['GAMES'], 
                        $u['ADDRESS'], 
                        $u['CITY'], 
                        $u['COUNTRY'],
                        $u['PROVINCE'], 
                        $u['AREA'], 
                        $u['CURRENCY'], 
                        $u['TIMEZONE_OFFSET'],
                        $u['USERTYPE'], 
                        $u['PROFILE_IMAGE'], 
                        $u['PREMIUM']
                    ]);
                    $new_id = $pdo->lastInsertId();
                }
                $status = 'PLAYER CREATED';
            }
            $pdo->prepare("UPDATE to_users SET CA_ID = ? WHERE ID = ?")->execute([$new_id, $id]);
            echo json_encode(['success' => true, 'ca_id' => $new_id, 'status_text' => $status]);
            break;

        case 'delete_team':
            $st = $pdo->prepare("SELECT TEAM_ID FROM to_users WHERE ID = ?"); $st->execute([(int)$_POST['id']]);
            $tid = $st->fetchColumn();
            if($tid) {
                $pdo->prepare("DELETE FROM to_users WHERE TEAM_ID = ?")->execute([$tid]);
                $pdo->prepare("DELETE FROM to_teams WHERE ID = ?")->execute([$tid]);
            }
            echo json_encode(['success' => true]);
            break;

        default: throw new Exception("Invalid Action");
    }
} catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }