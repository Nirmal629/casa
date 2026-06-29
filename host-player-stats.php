
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/dbConnection.php';

$sql = "SELECT * FROM ca_users LIMIT 5";
$result = $conn->query($sql);
if($result->num_rows > 0){
    echo "<table class='table'><tr><th>Name</th><th>Email</th></tr>";
    while($row = $result->fetch_assoc()){
        echo "<tr><td>".$row['NAME']."</td><td>".$row['EMAIL']."</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No players found.</p>";
}
?>