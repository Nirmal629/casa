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

printTableSchema($conn, 'ca_events');
printTableSchema($conn, 'ca_gamejoin');
printTableSchema($conn, 'to_matches');
printTableSchema($conn, 'to_teams');
printTableSchema($conn, 'to_tournaments');

$conn->close();
?>
