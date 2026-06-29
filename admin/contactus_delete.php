<?php
include('dbConnection.php');
$id = intval($_POST['id']);
$response = ['success'=>false];

if($conn->query("DELETE FROM ca_contact_messages WHERE id=$id")) {
    $response['success'] = true;
}
echo json_encode($response);