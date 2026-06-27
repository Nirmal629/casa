<?php

session_start();

header('Content-Type: application/json');



// --- DATABASE CONNECTION ---

$db_host = "localhost"; $db_name = "casa_test"; $db_user = "casa_test"; $db_pass = "casa_test123#";

try {

    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [

        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC

    ]);

} catch (PDOException $e) {

    echo json_encode(['status' => 'error', 'message' => 'Critical: Database connection failed.']); 

    exit;

}

 

// --- SECURITY: CSRF CHECK ---

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {

    echo json_encode(['status' => 'error', 'message' => 'Security Error: Invalid session token.']); 

    exit;

}



// --- DATA PROCESSING ---

try {

    $email = filter_var($_POST['EMAIL'], FILTER_VALIDATE_EMAIL);

    if (!$email) { throw new Exception("Please provide a valid email address."); }



    // 2. DUPLICATE CHECK

    $stmt = $pdo->prepare("SELECT ID FROM ca_users WHERE EMAIL = ? LIMIT 1");

    $stmt->execute([$email]);

    if ($stmt->fetch()) {

        echo json_encode(['status' => 'error', 'message' => 'This email is already registered.']);

        exit;

    }



    // 3. SANITIZATION & LOGIC

    $name     = strip_tags(trim($_POST['NAME']));

    $whatsapp = preg_replace('/[^0-9+]/', '', $_POST['WHATSAPP_NUMBER']);

    $dob      = $_POST['DOB'];

    $gender   = $_POST['GENDER'];

    $level    = strip_tags(trim($_POST['LEVEL'])); // Handles "Intermediate +" safely



    //$level    = $_POST['LEVEL'];

    $city     = $_POST['CITY'];

    $province = $_POST['PROVINCE'];

    $country  = $_POST['COUNTRY'];

    $referral = strip_tags(trim($_POST['REFERRAL'] ?? ''));

    

    // Logic for Area

    $area = ($country === "Canada") ? strip_tags(trim($_POST['AREA'] ?? '')) : "";

    

    // Default Password

    $password = "abcde";



    // 4. INSERT NEW USER (19 Columns total)

    $sql = "INSERT INTO ca_users (

                NAME, EMAIL, PASSWORD, LEVEL, EMAIL_PERMISSION, WHATSAPP_NUMBER, 

                CALL_PERMISSION, DOB, GENDER, GAMES, ADDRESS, CITY, 

                COUNTRY, PROVINCE, AREA, REFERRAL, CURRENCY, TIMEZONE_OFFSET, USERTYPE, created_at

            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    

    $params = [

        $name, 

        $email, 

        $password, 

        $level, 

        $_POST['EMAIL_PERMISSION'], 

        $whatsapp, 

        $_POST['CALL_PERMISSION'],

        $dob, 

        $gender, 

        $_POST['GAMES'], 

        $_POST['ADDRESS'], 

        $city, 

        $country, 

        $province, 

        $area, 

        $referral,

        $_POST['CURRENCY'], 

        $_POST['TIMEZONE_OFFSET'], 

        $_POST['USERTYPE']

    ];



    $pdo->prepare($sql)->execute($params);



    echo json_encode([

        'status' => 'success', 

        'message' => 'You are registered successfully. Contact admin on Email: "info.casagames@gmail.com" to receive the credentials.'

    ]);



} catch (Exception $e) {

    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);

}