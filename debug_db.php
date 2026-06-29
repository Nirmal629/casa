<?php
include('api/dbConnection.php');

echo "<h2>Debug DB Info</h2>";

echo "<h3>Your Clubs:</h3>";
$res3 = $conn->query("SELECT id, host_id, game_type, join_type, country, province, city FROM ca_clubs");
echo "<table border='1' cellpadding='5'><tr><th>Club ID</th><th>Host ID</th><th>Game Type</th><th>Join Type</th><th>Country</th><th>Province</th><th>City</th></tr>";
while($row3 = $res3->fetch_assoc()) {
    echo "<tr><td>{$row3['id']}</td><td>{$row3['host_id']}</td><td>{$row3['game_type']}</td><td>{$row3['join_type']}</td><td>{$row3['country']}</td><td>{$row3['province']}</td><td>{$row3['city']}</td></tr>";
}
echo "</table>";

echo "<h3>All Players (ca_users):</h3>";
$res2 = $conn->query("SELECT ID, NAME, GENDER, USERTYPE, COUNTRY, PROVINCE, CITY, GAMES, VERIFIED_LEVEL FROM ca_users WHERE USERTYPE='Player'");
echo "<table border='1' cellpadding='5'><tr><th>User ID</th><th>Name</th><th>Gender</th><th>Level</th><th>Games</th><th>Country</th><th>Province</th><th>City</th></tr>";
while($row2 = $res2->fetch_assoc()) {
    echo "<tr><td>{$row2['ID']}</td><td>{$row2['NAME']}</td><td>{$row2['GENDER']}</td><td>{$row2['VERIFIED_LEVEL']}</td><td>{$row2['GAMES']}</td><td>{$row2['COUNTRY']}</td><td>{$row2['PROVINCE']}</td><td>{$row2['CITY']}</td></tr>";
}
echo "</table>";

echo "<h3>Club Memberships (ca_player_club_status):</h3>";
$res = $conn->query("SELECT * FROM ca_player_club_status");
echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Club ID</th><th>Host ID</th><th>Player ID</th><th>Status</th></tr>";
while($row = $res->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['club_id']}</td><td>{$row['host_id']}</td><td>{$row['player_id']}</td><td>{$row['status']}</td></tr>";
}
echo "</table>";
?>
