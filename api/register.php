<?php
header('Content-Type: application/json');
include('dbConnection.php');
$data = json_decode(file_get_contents('php://input'), true);

$NAME = trim($data['name']);
$EMAIL = $data['email'];
$EMAIL_PERMISSION = $data['email_permission'];
$WHATSAPP_NUMBER = $data['whatsapp_number'];
$CALL_PERMISSION = $data['call_permission'];
$DATE_OF_BIRTH = $data['date_of_birth'];
$GENDER = $data['gender'];
$GAMES = $data['games'];
$ADDRESS = trim($data['address']);
$CITY = trim($data['city']);
$COUNTRY = trim($data['country']);
$PROVINCE = trim($data['province']);
$CURRENCY = trim($data['currency']);
$TIMEZONE_OFFSET = trim($data['timezone_offset']);
$USERTYPE = $data['usertype'];
$LEVEL = $data['level'];
$PASSWORD = 'abcd';

// Validation
if (!$NAME || !$EMAIL || !$EMAIL_PERMISSION || !$WHATSAPP_NUMBER || !$CALL_PERMISSION  || !$GENDER || !$GAMES  || !$CITY || !$COUNTRY || !$PROVINCE || !$CURRENCY || !$TIMEZONE_OFFSET || !$USERTYPE) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data.']);
    exit;
}
// echo "INSERT INTO registrations (NAME, EMAIL, EMAIL_PERMISSION, WHATSAPP_NUMBER, CALL_PERMISSION, DATE_OF_BIRTH, GENDER, GAMES, ADDRESS, CITY, COUNTRY, PROVINCE, CURRENCY, TIMEZONE_OFFSET, USERTYPE) VALUES ('$NAME', '$EMAIL', '$EMAIL_PERMISSION', '$WHATSAPP_NUMBER', '$CALL_PERMISSION', '$DATE_OF_BIRTH', '$GENDER', '$GAMES', '$ADDRESS', '$CITY', '$COUNTRY', '$PROVINCE', '$CURRENCY', '$TIMEZONE_OFFSET', '$USERTYPE')";
// Check if the email already exists
$email_check_query = $conn->prepare("SELECT ID FROM ca_users WHERE EMAIL = ?");
$email_check_query->bind_param('s', $EMAIL);
$email_check_query->execute();
$email_check_query->store_result();

if ($email_check_query->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email already exists. Please use a different email.']);
    $email_check_query->close();
    $conn->close();
    exit;
}
$email_check_query->close();
// Prepare SQL statement
$stmt = $conn->prepare("INSERT INTO ca_users (NAME, EMAIL, PASSWORD,LEVEL, EMAIL_PERMISSION, WHATSAPP_NUMBER, CALL_PERMISSION, DOB, GENDER, GAMES, ADDRESS, CITY, COUNTRY, PROVINCE, CURRENCY, TIMEZONE_OFFSET, USERTYPE) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('sssssssssssssssss', $NAME, $EMAIL, $PASSWORD,$LEVEL ,$EMAIL_PERMISSION, $WHATSAPP_NUMBER, $CALL_PERMISSION, $DATE_OF_BIRTH, $GENDER, $GAMES, $ADDRESS, $CITY, $COUNTRY, $PROVINCE, $CURRENCY, $TIMEZONE_OFFSET, $USERTYPE);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Data inserted successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to insert data: ' . $stmt->error]);
}

$stmt->close();
$conn->close();

?>