<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    header("Content-Type: application/json; charset=UTF-8");

    include 'db_connect.php';
    // เปิดการแสดง Error (สำหรับ Debug)
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $targetDir = "document/committees/";

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // ดึงข้อมูลเอกสารของคณะกรรมการตรวจสอบ
        $sql = "SELECT d.*, u.prename, u.name, u.surname
                FROM document d
                JOIN users u ON d.updated_by = u.id
                WHERE d.category_id = 2";
        $result = $conn->query($sql);

        if ($result) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(["success" => true, "documentData" => $rows ?: []]);
        } else {
            echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการดึงข้อมูล", "error" => $conn->error]);
        }
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'];
        // $category_id = $_POST['category_id'];
        $uploaded_at = $_POST['uploaded_at'];
        $updated_by = $_POST['updated_by'];
        $newFileName = null;

        // ดึงชื่อไฟล์รูปเก่าจากฐานข้อมูล
        $query = "SELECT file_name FROM `document` WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $old_image = $row['file_name'];

        if (isset($_FILES['file_name']) && $_FILES['file_name']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file_name'];
            // var_dump('dddd', $file);
            $allowedTypes = ['application/pdf'];
            $maxSize = 2097152 ;

            if (!in_array($file['type'], $allowedTypes)) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "อนุญาตเฉพาะไฟล์ PDF เท่านั้น"]);
                exit;
            }

            if ($file['size'] > $maxSize) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 2MB)"]);
                exit;
            }

            if (!empty($old_image)) {
                $oldImagePath = $targetDir . $old_image;
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $originalFileName = basename($file['name']);
            $newFileName = $originalFileName;
            $targetFilePath = $targetDir . $newFileName;

            if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "ไม่สามารถบันทึกไฟล์ได้"]);
                exit;
            }
        }

        if ($newFileName) {
            $stmt = $conn->prepare("UPDATE document SET 
                                    file_name = ?, uploaded_at = ?, updated_by = ?
                                    WHERE id = ? AND category_id = 2");
            $stmt->bind_param("ssi", $newFileName, $uploaded_at, $updated_by, $id);
        } else {
            $stmt = $conn->prepare("UPDATE document SET 
                                    uploaded_at = ?, updated_by = ?
                                    WHERE id = ? AND category_id = 2");
            $stmt->bind_param("ssi", $uploaded_at, $updated_by, $id);
        }

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "อัปเดตข้อมูลสำเร็จ"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "ไม่สามารถบันทึกข้อมูลลงในฐานข้อมูล"]);
        }

        $stmt->close();
    }
?>
