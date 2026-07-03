<?php
$host = '127.0.0.1';
$user = 'root';
$pass = ''; // default XAMPP root password

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    echo "Root connection failed: " . $conn->connect_error . "<br>";
} else {
    echo "Root connected successfully<br>";
    // Let's reset the password for casa_test
    $sql1 = "CREATE USER IF NOT EXISTS 'casa_test'@'localhost' IDENTIFIED BY 'casa_test123#'";
    $sql2 = "ALTER USER 'casa_test'@'localhost' IDENTIFIED BY 'casa_test123#'";
    $sql3 = "GRANT ALL PRIVILEGES ON casa_test.* TO 'casa_test'@'localhost'";
    $sql4 = "CREATE USER IF NOT EXISTS 'casa_test'@'127.0.0.1' IDENTIFIED BY 'casa_test123#'";
    $sql5 = "ALTER USER 'casa_test'@'127.0.0.1' IDENTIFIED BY 'casa_test123#'";
    $sql6 = "GRANT ALL PRIVILEGES ON casa_test.* TO 'casa_test'@'127.0.0.1'";
    $sql7 = "FLUSH PRIVILEGES";

    if ($conn->query($sql1) === TRUE) echo "SQL1 success<br>"; else echo "SQL1 error: " . $conn->error . "<br>";
    if ($conn->query($sql2) === TRUE) echo "SQL2 success<br>"; else echo "SQL2 error: " . $conn->error . "<br>";
    if ($conn->query($sql3) === TRUE) echo "SQL3 success<br>"; else echo "SQL3 error: " . $conn->error . "<br>";
    if ($conn->query($sql4) === TRUE) echo "SQL4 success<br>"; else echo "SQL4 error: " . $conn->error . "<br>";
    if ($conn->query($sql5) === TRUE) echo "SQL5 success<br>"; else echo "SQL5 error: " . $conn->error . "<br>";
    if ($conn->query($sql6) === TRUE) echo "SQL6 success<br>"; else echo "SQL6 error: " . $conn->error . "<br>";
    if ($conn->query($sql7) === TRUE) echo "SQL7 success<br>"; else echo "SQL7 error: " . $conn->error . "<br>";
}
?>
