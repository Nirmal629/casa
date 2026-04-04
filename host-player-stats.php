
<?php
session_start();
date_default_timezone_set('America/Toronto');

const DATABASE_NAME = 'casa_test';
const USERNAME = 'casa_test';
const PASSWORD = 'casa_test123#';
$host = 'localhost';

$conn = new mysqli($host, USERNAME, PASSWORD, DATABASE_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<?php
$sql = "SELECT * FROM ca_user LIMIT 5"; 
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