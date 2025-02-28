<?php
    date_default_timezone_set('Asia/Bangkok');

    //  ตั้งค่า Header สำหรับ JSON และ CORS
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    include 'db_connect.php';

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // ดึงค่า id_information จาก query string
        $id_information = isset($_GET['id_information']) ? $_GET['id_information'] : null;

        if ($id_information) {
            $sql = "SELECT id_information, description_th FROM information WHERE id_information = $id_information"; 
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                echo json_encode(["success" => true, "information" => $row]);
            } else {
                echo json_encode(["success" => false, "message" => "ไม่พบข้อมูล"]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "ไม่พบพารามิเตอร์ id_information"]);
        }

        exit;
    }


    //  กรณี POST -> รับข้อมูล JSON และอัปเดตฐานข้อมูล
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // รับข้อมูล JSON ที่ส่งเข้ามา
        $rawData = file_get_contents("php://input");
        $data = json_decode($rawData, true);

        // ตรวจสอบว่า JSON ถูกต้องและมีข้อมูลครบถ้วน
        if (is_null($data)) {
            echo json_encode(["success" => false, "message" => "รูปแบบ JSON ไม่ถูกต้อง"]);
            exit;
        }

        if (!isset($data['id_information'], $data['description_th'], $data['updated_at'], $data['updated_by'])) {
            echo json_encode(["success" => false, "message" => "ข้อมูลไม่ครบถ้วน"]);
            exit;
        }

        // ดึงค่าจาก JSON
        $id_information = (int) $data['id_information'];
        $description_th = $conn->real_escape_string($data['description_th']);
        $updated_at = $conn->real_escape_string($data['updated_at']);
        $updated_by = (int) $data['updated_by'];

        //  อัปเดตข้อมูลในฐานข้อมูล
        $sql = "UPDATE information 
                SET description_th = '$description_th', 
                    updated_at = '$updated_at', 
                    updated_by = '$updated_by' 
                WHERE id_information = $id_information";

        if ($conn->query($sql) === TRUE) {
            echo json_encode(["success" => true, "message" => "อัปเดตข้อมูลสำเร็จ"]);
        } else {
            echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาด: " . $conn->error]);
        }

        $conn->close();
        exit;
    }

    //  ถ้าคำขอไม่ใช่ GET หรือ POST
    echo json_encode(["success" => false, "message" => "Method ไม่ถูกต้อง"]);
?>
