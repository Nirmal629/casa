<?php
include('../dbConnection.php');
// Check if the form data is received via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input data
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $email_permission = mysqli_real_escape_string($conn, $_POST['EmailPermission']);
    $whatsapp_number = mysqli_real_escape_string($conn, $_POST['number']);
    $call_permission = mysqli_real_escape_string($conn, $_POST['CallPermission']);
    $dob = mysqli_real_escape_string($conn, $_POST['dateofbirth']);
    $gender = mysqli_real_escape_string($conn, $_POST['GenderRadioOptions']);
    $city = mysqli_real_escape_string($conn, $_POST['City']);
    $country = mysqli_real_escape_string($conn, $_POST['Country']);
    $province = mysqli_real_escape_string($conn, $_POST['Province']);
    $currency = mysqli_real_escape_string($conn, $_POST['currency']);
    $level = mysqli_real_escape_string($conn, $_POST['level']);
    $vlevel = mysqli_real_escape_string($conn, $_POST['vlevel']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $timezone_offset = mysqli_real_escape_string($conn, $_POST['timezone_offset']);
    $usertype = mysqli_real_escape_string($conn, $_POST['usertype']);
    $Premium = mysqli_real_escape_string($conn, $_POST['Premium']);
    


    // Check if the email exists for another user (exclude the current user's ID)
    $emailCheckQuery = "SELECT COUNT(*) FROM ca_users WHERE EMAIL = '$email' AND ID != $user_id";
    $emailCheckResult = mysqli_query($conn, $emailCheckQuery);
    
    if ($emailCheckResult) {
        $emailCount = mysqli_fetch_row($emailCheckResult)[0];

        // If email already exists for another user, return error
        if ($emailCount > 0) {
            echo json_encode(['success' => false, 'message' => 'This email is already in use by another user.']);
            mysqli_close($conn);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to check email availability.']);
        mysqli_close($conn);
        exit();
    }

    // Update query
    if($password!='')
    {
        // $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $hashed_password = $password;

        $sql = "UPDATE ca_users 
            SET NAME = '$name', EMAIL = '$email', PASSWORD='$hashed_password',  EMAIL_PERMISSION = '$email_permission', WHATSAPP_NUMBER = '$whatsapp_number', CALL_PERMISSION = '$call_permission', DOB = '$dob', GENDER = '$gender', CITY = '$city', COUNTRY = '$country', PROVINCE = '$province', CURRENCY = '$currency', LEVEL='$level', VERIFIED_LEVEL='$vlevel', TIMEZONE_OFFSET = '$timezone_offset', USERTYPE = '$usertype',PREMIUM='$Premium' 
            WHERE ID = $user_id";
    }
    else
    {
        $sql = "UPDATE ca_users 
            SET NAME = '$name', EMAIL = '$email',  EMAIL_PERMISSION = '$email_permission', WHATSAPP_NUMBER = '$whatsapp_number', CALL_PERMISSION = '$call_permission', DOB = '$dob', GENDER = '$gender', CITY = '$city', COUNTRY = '$country', PROVINCE = '$province', CURRENCY = '$currency', LEVEL='$level', VERIFIED_LEVEL='$vlevel', TIMEZONE_OFFSET = '$timezone_offset', USERTYPE = '$usertype',PREMIUM='$Premium'
            WHERE ID = $user_id";
    }

    $updateResult = mysqli_query($conn, $sql);

    if ($updateResult) {
        // Respond with success
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        // Respond with error
        echo json_encode(['success' => false, 'message' => 'Failed to update user. Please try again.']);
    }

    mysqli_close($conn);
} else {
    // Respond with error if not a POST request
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
