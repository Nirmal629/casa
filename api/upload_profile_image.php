<?php
require_once __DIR__ . '/_security.php';
$userId = requireApiUser();
requireApiPost();
require_once __DIR__ . '/dbConnection.php';
$image = $_FILES['profileImage'] ?? null;
if (!is_array($image) || ($image['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
    || !is_uploaded_file($image['tmp_name'] ?? '') || ($image['size'] ?? 0) > 5 * 1024 * 1024) {
    apiSecurityDeny(400, 'Upload a JPEG, PNG or WebP image up to 5 MB.');
}
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($image['tmp_name']);
$extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$dimensions = @getimagesize($image['tmp_name']);
if (!isset($extensions[$mime]) || !$dimensions || $dimensions[0] > 10000 || $dimensions[1] > 10000) apiSecurityDeny(400, 'Invalid image.');
$imageName = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
$target = __DIR__ . '/../profile_img/' . $imageName;
if (!move_uploaded_file($image['tmp_name'], $target)) apiSecurityDeny(500, 'Upload failed.');
try {
    $stmt = $conn->prepare('UPDATE ca_users SET PROFILE_IMAGE = ? WHERE ID = ?');
    $stmt->bind_param('si', $imageName, $userId);
    if (!$stmt->execute()) throw new RuntimeException('Update failed.');
} catch (Throwable $e) {
    @unlink($target);
    apiSecurityDeny(500, 'Profile update failed.');
}
header('Content-Type: application/json');
echo json_encode(['success' => true, 'image' => $imageName, 'imagePath' => '../profile_img/' . $imageName]);
