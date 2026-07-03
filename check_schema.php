<?php
include('dbConnection.php');

header('Content-Type: text/plain');

function printTableSchema($conn, $tableName) {
    echo "=== Schema for $tableName ===\n";
    $result = $conn->query("DESCRIBE `$tableName`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "Field: {$row['Field']} | Type: {$row['Type']} | Null: {$row['Null']} | Key: {$row['Key']} | Default: {$row['Default']}\n";
        }
    } else {
        echo "Error or table does not exist: " . $conn->error . "\n";
    }
    echo "\n";
}

printTableSchema($conn, 'ca_users');
printTableSchema($conn, 'ca_clubs');

// Let's also check if table ca_clubs_player_mapping or club_player_mapping exists
printTableSchema($conn, 'ca_clubs_player_mapping');
printTableSchema($conn, 'club_player_mapping');
printTableSchema($conn, 'ca_player_club_status');

$conn->close();
?>
