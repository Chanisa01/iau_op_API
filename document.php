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

function getFileNameById($id_documents) {
    $fileNames = [
        1 => "AuditStandards.pdf",
        2 => "InternalAuditRegulations.pdf",
        3 => "InternalAuditCharter.pdf"
    ];
    return $fileNames[$id_documents] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id_documents = isset($_GET['id_documents']) ? $_GET['id_documents'] : null;

    if ($id_documents) {
        // $sql = "SELECT * FROM documents WHERE id_documents = $id_documents"; 
        $sql = " SELECT documents.*, users.name, users.surname
                 FROM documents
                 JOIN users ON documents.updated_by = users.id
                 WHERE documents.id_documents = $id_documents"; 
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode(["success" => true, "information" => $row]);
        } else {
            echo json_encode(["success" => false, "message" => "ไม่พบข้อมูล"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "ไม่พบพารามิเตอร์ id_documents"]);
    }
    exit;
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
        //-------------------------------------------------------------------------------------
        //แก้ชื่อไฟล์ AuditStandards_{$date}.{$fileExtension}
        // $date = date("d-m-Y_H-i-s");
        // $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); 
        // $newFileName = "AuditStandards_{$date}.{$fileExtension}";
        // $targetFilePath = $targetDir . $newFileName;
        
        // array_map('unlink', glob($targetDir . "*")); // ลบไฟล์เก่า

        //--------------------------------SAVE ORIGIN NAME FILE & DELETE FILE IN FOLDER------- 
        // $originalFileName = basename($file['name']);
        // $targetFilePath = $targetDir . $originalFileName;

        // array_map('unlink', glob($targetDir . "*")); // ลบไฟล์เก่า
        
        // if (move_uploaded_file($file['tmp_name'], $targetFilePath)) { 
            
        //     $id_documents = $_POST['id_documents'] ?? null;
        //     $topic_th = $_POST['topic_th'] ?? '';
        //     $description_th = $_POST['description_th'] ?? '';
        //     $updated_at = $_POST['updated_at'] ?? '';
        //     $updated_by = $_POST['updated_by'] ?? '';

        //     if (!$id_documents) {
        //         http_response_code(400);
        //         echo json_encode(["success" => false, "message" => "ไม่พบค่า id_documents"]);
        //         exit;
        //     }
        //     $stmt = $conn->prepare("UPDATE documents SET topic_th = ?, description_th = ?, filename = ?, updated_at = ?, updated_by = ? WHERE id_documents = ?");
        //     if (!$stmt) {
        //         http_response_code(500);
        //         echo json_encode(["success" => false, "message" => "ข้อผิดพลาดในการเตรียมคำสั่ง SQL"]);
        //         exit;
        //     }
        //     //แก้ชื่อไฟล์
        //     // $stmt->bind_param("sssssi", $topic_th, $description_th, $newFileName, $updated_at, $updated_by, $id_documents);

        //     // if ($stmt->execute()) {
        //     //     echo json_encode([
        //     //         "success" => true,
        //     //         "message" => "อัปโหลดและบันทึกไฟล์สำเร็จ",
        //     //         "newFileName" => $newFileName,
        //     //         "filePath" => $targetFilePath
        //     //     ]);
        //     // } else {
        //     //     http_response_code(500);
        //     //     echo json_encode(["success" => false, "message" => "ไม่สามารถบันทึกข้อมูลลงในฐานข้อมูล"]);
        //     // }

        //     $stmt->bind_param("sssssi", $topic_th, $description_th, $originalFileName, $updated_at, $updated_by, $id_documents);

        //     if ($stmt->execute()) {
        //         echo json_encode([
        //             "success" => true,
        //             "message" => "อัปโหลดและบันทึกไฟล์สำเร็จ",
        //             "fileName" => $originalFileName,
        //             "filePath" => $targetFilePath
        //         ]);
        //     } else {
        //         http_response_code(500);
        //         echo json_encode(["success" => false, "message" => "ไม่สามารถบันทึกข้อมูลลงในฐานข้อมูล"]);
        //     }

        //     $stmt->close();
        // } else {
        //     http_response_code(500);
        //     echo json_encode(["success" => false, "message" => "ไม่สามารถบันทึกไฟล์ได้"]);
        // }
        //-------------------------------------------------------------------------------------
        $id_documents = $_POST['id_documents'] ?? null;
        $topic_th = $_POST['topic_th'] ?? '';
        $description_th = $_POST['description_th'] ?? '';
        $updated_at = $_POST['updated_at'] ?? '';
        $updated_by = $_POST['updated_by'] ?? '';

        if (!$id_documents) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "ไม่พบค่า id_documents"]);
            exit;
        }

        $newFileName = getFileNameById($id_documents);
        if (!$newFileName) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "id_documents ไม่ถูกต้อง"]);
            exit;
        }

        $targetFilePath = $targetDir . $newFileName;

        if (file_exists($targetFilePath)) {
            unlink($targetFilePath);
        }

        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) { 
            $stmt = $conn->prepare("UPDATE documents SET topic_th = ?, description_th = ?, filename = ?, updated_at = ?, updated_by = ? WHERE id_documents = ?");
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "ข้อผิดพลาดในการเตรียมคำสั่ง SQL"]);
                exit;
            }
            //แก้ชื่อไฟล์
            $stmt->bind_param("sssssi", $topic_th, $description_th, $newFileName, $updated_at, $updated_by, $id_documents);

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

            // $stmt->bind_param("sssssi", $topic_th, $description_th, $originalFileName, $updated_at, $updated_by, $id_documents);

            // if ($stmt->execute()) {
            //     echo json_encode([
            //         "success" => true,
            //         "message" => "อัปโหลดและบันทึกไฟล์สำเร็จ",
            //         "fileName" => $originalFileName,
            //         "filePath" => $targetFilePath
            //     ]);
            // } else {
            //     http_response_code(500);
            //     echo json_encode(["success" => false, "message" => "ไม่สามารถบันทึกข้อมูลลงในฐานข้อมูล"]);
            // }

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
