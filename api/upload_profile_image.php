<?php
include('dbConnection.php');

    $userId = $_POST['user_id'];
    $image = $_FILES['profileImage'];
    $targetDir = "./../profile_img/";
    $imageName = uniqid() . "_" . basename($image["name"]);
    $targetFile = $targetDir . $imageName;

    if (move_uploaded_file($image["tmp_name"], $targetFile)) {
        $stmt = $conn->prepare("UPDATE ca_users SET PROFILE_IMAGE = ? WHERE id = ?");
        $stmt->bind_param("si", $imageName, $userId);
        $stmt->execute();
        echo "Profile image uploaded successfully.";
    } else {
        echo "Failed to upload image.";
    }

?>