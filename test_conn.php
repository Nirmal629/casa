<?php
$credentials = [
    ['127.0.0.1', 'casa_test', 'casa_test123#'],
    ['localhost', 'casa_test', 'casa_test123#'],
    ['127.0.0.1', 'root', ''],
    ['localhost', 'root', ''],
    ['127.0.0.1', 'root', 'root'],
    ['localhost', 'root', 'root']
];

foreach ($credentials as $cred) {
    echo "Testing {$cred[1]}@{$cred[0]} with pass {$cred[2]}: ";
    try {
        $conn = @new mysqli($cred[0], $cred[1], $cred[2], 'casa_test');
        if ($conn->connect_error) {
            echo "Failed (" . $conn->connect_error . ")\n";
        } else {
            echo "SUCCESS\n";
        }
    } catch (Exception $e) {
        echo "Exception (" . $e->getMessage() . ")\n";
    }
}
?>
