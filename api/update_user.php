<?php
require_once __DIR__ . '/_security.php';
$userId = requireApiUser();
requireApiPost();
require_once __DIR__ . '/dbConnection.php';
if (!empty($_POST['password'])) apiSecurityDeny(400, 'Use a dedicated password-change flow with current-password verification.');
$fields = ['NAME' => 'name', 'EMAIL' => 'email', 'EMAIL_PERMISSION' => 'EmailPermission',
    'WHATSAPP_NUMBER' => 'number', 'CALL_PERMISSION' => 'CallPermission', 'DOB' => 'dateofbirth',
    'GENDER' => 'GenderRadioOptions', 'CITY' => 'City', 'COUNTRY' => 'Country', 'PROVINCE' => 'Province',
    'CURRENCY' => 'currency', 'LEVEL' => 'level', 'TIMEZONE_OFFSET' => 'timezone_offset'];
$values = [];
foreach ($fields as $column => $input) {
    $value = $_POST[$input] ?? '';
    if (!is_string($value)) apiSecurityDeny(400, 'Invalid profile field.');
    $values[] = trim($value);
}
if (!filter_var($values[1], FILTER_VALIDATE_EMAIL) || $values[0] === '') apiSecurityDeny(400, 'Valid name and email required.');
$check = $conn->prepare('SELECT ID FROM ca_users WHERE EMAIL = ? AND ID != ? LIMIT 1');
$check->bind_param('si', $values[1], $userId);
$check->execute();
if ($check->get_result()->fetch_assoc()) apiSecurityDeny(409, 'This email is already in use.');
$assignments = implode(', ', array_map(static fn($column) => $column . ' = ?', array_keys($fields)));
$values[] = $userId;
$stmt = $conn->prepare('UPDATE ca_users SET ' . $assignments . ' WHERE ID = ?');
$stmt->bind_param(str_repeat('s', count($fields)) . 'i', ...$values);
if (!$stmt->execute()) apiSecurityDeny(500, 'Profile update failed.');
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'User updated successfully']);
