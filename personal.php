<?php 
    date_default_timezone_set('Asia/Bangkok');

    // ตั้งค่า Header สำหรับ JSON และ CORS
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    include 'db_connect.php';

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT personal.*, users.name AS user_name, users.surname AS user_surname 
                FROM personal 
                JOIN users ON personal.updated_by = users.id"; 
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            // ใช้ fetch_all() เพื่อดึงข้อมูลทั้งหมด
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(["success" => true, "personal" => $rows]);
        } else {
            echo json_encode(["success" => false, "message" => "ไม่พบข้อมูล"]);
        }

        // ปิดการเชื่อมต่อฐานข้อมูล
        $conn->close();
        exit;
    }
?>
