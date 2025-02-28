<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

include 'db_connect.php';

$targetDir = "img/Structure/";

// สร้างโฟลเดอร์หากยังไม่มี
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['image_name'])) {
        $file = $_FILES['image_name'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 1000000; // 1000KB

        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "เกิดข้อผิดพลาดในการอัปโหลดไฟล์"]);
            exit;
        }

        if (!in_array($file['type'], $allowedTypes)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "อนุญาตเฉพาะไฟล์ JPG, JPEG และ PNG เท่านั้น"]);
            exit;
        }

        if ($file['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 1000KB)"]);
            exit;
        }

        $date = date("d-m-Y");
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newFileName = "structure_{$date}.{$fileExtension}";
        $targetFilePath = $targetDir . $newFileName;

        // ลบไฟล์ในโฟลเดอร์ทั้งหมดก่อนบันทึกใหม่
        $files = glob($targetDir . "*");
        foreach ($files as $existingFile) {
            if (is_file($existingFile)) {
                unlink($existingFile);
            }
        }

        // บันทึกไฟล์ใหม่หลังจากลบไฟล์เก่า
        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            // รับข้อมูลจาก POST
            $id_structure = $_POST['id_structure'];  // ค่าจาก React (ข้อมูลเพิ่มเติมที่ส่งมาจากฟอร์ม)
            $updated_by = $_POST['updated_by'];
            $updated_at = $_POST['updated_at'];

            // SQL Query สำหรับอัปเดตข้อมูล
            $query = "UPDATE structure 
                      SET image_name = ?, 
                          updated_at = ?, 
                          updated_by = ? 
                      WHERE id_structure = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssss", $newFileName, $updated_at, $updated_by, $id_structure);

            if ($stmt->execute()) {
                echo json_encode([
                    "success" => true,
                    "message" => "อัปโหลดและบันทึกไฟล์สำเร็จ",
                    "newFileName" => $newFileName,
                    "filePath" => $targetFilePath
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => "ไม่สามารถบันทึกข้อมูลลงในฐานข้อมูล"
                ]);
            }            
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "ไม่สามารถบันทึกไฟล์ได้"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "ไม่พบไฟล์ที่ถูกส่งมา"]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
}
?>
