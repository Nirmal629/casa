<?php
header('Content-Type: application/json');

/* --- 1. DATABASE CONNECTION (PDO) --- */
$host    = 'localhost';
$db      = 'casa_db';
$user    = 'casa_sports';
$pass    = 'C@sa_sports24#';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Enables error reporting
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Sets default fetch to associative array
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Uses real prepared statements
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
    exit;
}

/* --- 2. MAIN LOGIC --- */
$response = ["status" => "error", "message" => "An unknown error occurred."];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Map fields and handle defaults (no need for mysqli_real_escape_string)
        $name             = $_POST['name'] ?? '';
        $email            = $_POST['email'] ?? '';
        $password         = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
        $level            = $_POST['level'] ?? '';
        $verified_level   = $_POST['verified_level'] ?? '';
        $whatsapp_number  = $_POST['whatsapp_number'] ?? '';
        $dob              = $_POST['dob'] ?? '';
        $city             = $_POST['city'] ?? '';
        $country          = $_POST['country'] ?? '';
        $province         = $_POST['province'] ?? '';
        $currency         = $_POST['currency'] ?? '';
        $timezone         = $_POST['timezone'] ?? '';
        $gender           = $_POST['gender'] ?? '';
        $usertype         = $_POST['usertype'] ?? '';
        
        // Handling ENUM values
        $email_permission = (isset($_POST['email_permission']) && $_POST['email_permission'] == 'Y') ? 'Yes' : 'No';
        $call_permission  = (isset($_POST['call_permission']) && $_POST['call_permission'] == 'Y') ? 'Yes' : 'No';
        $premium          = (isset($_POST['premium']) && $_POST['premium'] == 'Y') ? 'Y' : 'N';

        // Handle Image Upload
        $profile_image = ""; 
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = time() . "_" . uniqid() . "." . $ext;
            $target = "../assets/" . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $profile_image = $fileName;
            } else {
                throw new Exception("Failed to upload image to assets folder.");
            }
        }

        // 3. Database Insertion using PDO Prepared Statements
        $sql = "INSERT INTO ca_users (
                    NAME, EMAIL, PASSWORD, LEVEL, VERIFIED_LEVEL, 
                    EMAIL_PERMISSION, WHATSAPP_NUMBER, CALL_PERMISSION, 
                    DOB, GENDER, CITY, COUNTRY, PROVINCE, 
                    CURRENCY, TIMEZONE_OFFSET, USERTYPE, PROFILE_IMAGE, PREMIUM
                ) VALUES (
                    :name, :email, :password, :level, :verified_level, 
                    :email_perm, :whatsapp, :call_perm, 
                    :dob, :gender, :city, :country, :province, 
                    :currency, :timezone, :usertype, :img, :premium
                )";

        $stmt = $conn->prepare($sql);

        // Bind and Execute
        $stmt->execute([
            ':name'           => $name,
            ':email'          => $email,
            ':password'       => $password,
            ':level'          => $level,
            ':verified_level' => $verified_level,
            ':email_perm'     => $email_permission,
            ':whatsapp'       => $whatsapp_number,
            ':call_perm'      => $call_permission,
            ':dob'            => $dob,
            ':gender'         => $gender,
            ':city'           => $city,
            ':country'        => $country,
            ':province'       => $province,
            ':currency'       => $currency,
            ':timezone'       => $timezone,
            ':usertype'       => $usertype,
            ':img'            => $profile_image,
            ':premium'        => $premium
        ]);

        $response = [
            "status" => "success", 
            "message" => "User " . htmlspecialchars($name) . " has been added successfully!"
        ];

    } catch (PDOException $e) {
        $response = ["status" => "error", "message" => "Database Error: " . $e->getMessage()];
    } catch (Exception $e) {
        $response = ["status" => "error", "message" => $e->getMessage()];
    }
}

echo json_encode($response);
?>