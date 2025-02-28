<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

include 'db_connect.php';

$targetDir = "document/information/";
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['filename'])) {
        $file = $_FILES['filename'];
        $allowedTypes = ['application/pdf'];
        $maxSize = 1000000;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการอัปโหลดไฟล์"]);
            exit;
        }

        if (!in_array($file['type'], $allowedTypes)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "อนุญาตเฉพาะไฟล์ PDF เท่านั้น"]);
            exit;
        }

        if ($file['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 1000KB)"]);
            exit;
        }

        $date = date("d-m-Y_H-i-s");
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); // ✅ แก้ตรงนี้
        $newFileName = "AuditStandards_{$date}.{$fileExtension}";
        $targetFilePath = $targetDir . $newFileName;

        array_map('unlink', glob($targetDir . "*")); // ลบไฟล์เก่า

        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) { // ✅ ใช้ 'tmp_name'
            $id = $_POST['id'] ?? null;
            $topic_th = $_POST['topic_th'] ?? '';
            $description_th = $_POST['description_th'] ?? '';
            $updated_at = $_POST['updated_at'] ?? '';
            $updated_by = $_POST['updated_by'] ?? '';

            if (!$id) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "ไม่พบค่า id"]);
                exit;
            }

            $stmt = $conn->prepare("UPDATE documents SET topic_th = ?, description_th = ?, filename = ?, updated_at = ?, updated_by = ? WHERE id = ?");
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "ข้อผิดพลาดในการเตรียมคำสั่ง SQL"]);
                exit;
            }

            $stmt->bind_param("sssssi", $topic_th, $description_th, $newFileName, $updated_at, $updated_by, $id);

            if ($stmt->execute()) {
                echo json_encode([
                    "success" => true,
                    "message" => "อัปโหลดและบันทึกไฟล์สำเร็จ",
                    "newFileName" => $newFileName,
                    "filePath" => $targetFilePath
                ]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "ไม่สามารถบันทึกข้อมูลลงในฐานข้อมูล"]);
            }

            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "ไม่สามารถบันทึกไฟล์ได้"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ไม่พบไฟล์ที่ถูกส่งมา"]);
    }
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
}
?>
