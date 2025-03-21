<?php
include 'kmutnbsso.php';
include "connect.php";
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$auth = new kmutnbsso();

if (!isset($_GET['code'])) { 
    header("Location: http://localhost:5173/login?action=notallow");
    exit();
}

$userDetails = $auth->handleCallback();

if ($userDetails === false) {
    header("Location: http://localhost:5173/login?action=error");
    exit();
}

$pid = $userDetails["profile"]["pid"];
$username = $userDetails["profile"]["username"];

$sql = "SELECT * FROM users WHERE pid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $pid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $_SESSION['sess_iauop_id'] = session_id();
    $_SESSION['sess_iauop_username'] = $username;

    // ตั้งค่า cookie เพื่อให้ React อ่าน session ได้
    setcookie("sess_iauop_id", $_SESSION['sess_iauop_id'], 0, "/");
    setcookie("sess_iauop_username", $_SESSION['sess_iauop_username'], 0, "/");

    header("Location: http://localhost:3000");
    exit();
} else {
    header("Location: http://localhost:5173/login?action=noauthorize");
    exit();
}
?>
