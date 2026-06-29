<?php
include('api/dbConnection.php');
$output = "Club Status:\n";
$res = $conn->query("SELECT * FROM ca_player_club_status");
while($row = $res->fetch_assoc()) {
    $output .= print_r($row, true);
}
$output .= "Users:\n";
$res2 = $conn->query("SELECT ID, NAME, CITY, GAMES FROM ca_users WHERE USERTYPE='Player'");
while($row2 = $res2->fetch_assoc()) {
    $output .= print_r($row2, true);
}
$output .= "Clubs:\n";
$res3 = $conn->query("SELECT id, host_id, game_type, city, join_type FROM ca_clubs");
while($row3 = $res3->fetch_assoc()) {
    $output .= print_r($row3, true);
}
file_put_contents('db_dump.txt', $output);
echo "Dumped to db_dump.txt";
?>
