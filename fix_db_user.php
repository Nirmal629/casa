<?php
$conn = new mysqli("127.0.0.1", "root", "", "");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user exists
$res = $conn->query("SELECT User FROM mysql.user WHERE User = 'casa_test'");
if ($res->num_rows == 0) {
    $conn->query("CREATE USER 'casa_test'@'localhost' IDENTIFIED BY 'casa_test123#'");
    echo "Created user 'casa_test'@'localhost'.\n";
    
    // Also create for % just in case
    $conn->query("CREATE USER 'casa_test'@'%' IDENTIFIED BY 'casa_test123#'");
    echo "Created user 'casa_test'@'%'.\n";
} else {
    // Just reset the password
    $conn->query("ALTER USER 'casa_test'@'localhost' IDENTIFIED BY 'casa_test123#'");
    echo "Reset password for 'casa_test'@'localhost'.\n";
}

$conn->query("GRANT ALL PRIVILEGES ON casa_test.* TO 'casa_test'@'localhost'");
$conn->query("GRANT ALL PRIVILEGES ON casa_test.* TO 'casa_test'@'%'");
$conn->query("FLUSH PRIVILEGES");
echo "Granted privileges and flushed.\n";

// Test the new connection
try {
    $test = @new mysqli("127.0.0.1", "casa_test", "casa_test123#", "casa_test");
    if ($test->connect_error) {
        echo "Still failed: " . $test->connect_error . "\n";
    } else {
        echo "Successfully connected with casa_test!\n";
    }
} catch (Exception $e) {
    echo "Still exception: " . $e->getMessage() . "\n";
}
?>
