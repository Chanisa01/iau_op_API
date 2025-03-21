<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

session_start();

$response = [];

if (isset($_SESSION['sess_iauop_id']) && isset($_SESSION['sess_iauop_username'])) {
    $response['loggedIn'] = true;
    $response['username'] = $_SESSION['sess_iauop_username'];
} else {
    $response['loggedIn'] = false;
}

header('Content-Type: application/json');
echo json_encode($response);
?>
