<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    header("Content-Type: application/json; charset=UTF-8");

    include 'db_connect.php';
    // เปิดการแสดง Error (สำหรับ Debug)
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;
        // var_dump('category_id:' .$category_id);
        if ($category_id) {
            // var_dump('category_id:' .$category_id);
            $sql = "SELECT d.*, u.prename, u.name, u.surname
                    FROM document d
                    JOIN users u ON d.updated_by = u.id
                    WHERE d.category_id = $category_id";
            $result = $conn->query($sql);

            if ($result) {
                $rows = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode(["success" => true, "documentData" => $rows ?: []]);
            } else {
                echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการดึงข้อมูล", "error" => $conn->error]);
            }
        }
    }
    // error_log("POST Data: " . json_encode($_POST));
    // error_log("FILES Data: " . json_encode($_FILES));
    // var_dump($_POST);
    // var_dump($_FILES);
    // exit;


    if ($method === 'POST') {
        $category_id = $_POST['category_id'] ?? null;
        $id = $_POST['id'] ?? null;
        // var_dump('ID: '.$id);
        $uploaded_at = $_POST['uploaded_at'] ?? null;
        $updated_by = $_POST['updated_by'] ?? null;

        if (!$category_id || !$id || !$uploaded_at || !$updated_by) {
            echo json_encode(["success" => false, "message" => "Missing required fields"]);
            exit;
        }
        
        // ค้นหา folder_path
        $stmt = $conn->prepare("SELECT folder_path FROM document_categories WHERE id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $folder = $result->fetch_assoc();

        if (!$folder) {
            echo json_encode(["success" => false, "message" => "Invalid category_id"]);
            exit;
        } 
        
        $targetDir = "document/" . $folder['folder_path'] . "/";

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // ตรวจสอบการอัปโหลดไฟล์
        if (!empty($_FILES['file_name']['name'])) {
            $file = $_FILES['file_name'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if ($fileExt !== 'pdf' || $file['size'] > 2 * 1024 * 1024) {
                echo json_encode(["success" => false, "message" => "Invalid file type or size"]);
                exit;
            }

            // ค้นหาชื่อไฟล์เก่า
            $stmt = $conn->prepare("SELECT file_name FROM document WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $oldFile = $result->fetch_assoc();

            if ($oldFile && file_exists($targetDir . $oldFile['file_name'])) {
                unlink($targetDir . $oldFile['file_name']);
            }

            // กำหนดชื่อไฟล์ใหม่
            $newFileName = basename($_FILES['file_name']['name']);
            $filePath = $targetDir . $newFileName;

            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                echo json_encode(["success" => false, "message" => "File upload failed"]);
                exit;
            }

            // อัปเดตฐานข้อมูล
            $stmt = $conn->prepare("UPDATE document SET file_name = ?, uploaded_at = ?, updated_by = ? WHERE id = ? AND category_id = ?");
            $stmt->bind_param("sssii", $newFileName, $uploaded_at, $updated_by, $id, $category_id);
            $success = $stmt->execute();
            if (!$success) {
                error_log("SQL Error: " . $stmt->error);
                echo json_encode(["success" => false, "message" => "Update failed", "error" => $stmt->error]);
                exit;
            }
        } else {
            $stmt = $conn->prepare("UPDATE document SET uploaded_at = ?, updated_by = ? WHERE id = ? AND category_id = ?");
            $stmt->bind_param("ssii", $uploaded_at, $updated_by, $id, $category_id);
            $success = $stmt->execute();
            if (!$success) {
                error_log("SQL Error: " . $stmt->error);
                echo json_encode(["success" => false, "message" => "Update failed", "error" => $stmt->error]);
                exit;
            }
            
        }
        
        if ($success) {
            echo json_encode(["success" => true, "message" => "Update successful"]);
        } else {
            echo json_encode(["success" => false, "message" => "Update failed"]);
        }
    }

    // if ($method === 'POST') {
//     $category_id = $_POST['category_id'] ?? null;
//     $id = $_POST['id'] ?? null;
//     $uploaded_at = $_POST['uploaded_at'] ?? null;
//     $updated_by = $_POST['updated_by'] ?? null;

//     if (!$category_id || !$id || !$uploaded_at || !$updated_by) {
//         echo json_encode(["error" => "Missing required fields"]);
//         exit;
//     }
    
//     // ค้นหา folder_path
//     $stmt = $conn->prepare("SELECT folder_path FROM document_categories WHERE id = ?");
//     $stmt->bind_param("i", $category_id);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     $folder = $result->fetch_assoc();

//     if (!$folder) {
//         echo json_encode(["error" => "Invalid category_id"]);
//         exit;
//     }
    
//     $targetDir = "document/" . $folder['folder_path'] . "/";
    
//     if (!is_dir($targetDir)) {
//         mkdir($targetDir, 0777, true);
//     }

//     if (!empty($_FILES['file_name']['name'])) {
//         $file = $_FILES['file_name'];
//         $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
//         if ($fileExt !== 'pdf' || $file['size'] > 2 * 1024 * 1024) {
//             echo json_encode(["error" => "Invalid file type or size"]);
//             exit;
//         }

//         // ค้นหาชื่อไฟล์เก่า
//         $stmt = $pdo->prepare("SELECT file_name FROM document WHERE id = ?");
//         $stmt->execute([$id]);
//         $oldFile = $stmt->fetch(PDO::FETCH_ASSOC);

//         if ($oldFile && file_exists($targetDir . $oldFile['file_name'])) {
//             unlink($targetDir . $oldFile['file_name']);
//         }

//         // กำหนดชื่อไฟล์ใหม่
//         $newFileName = $_FILES['file']['name'];
//         $filePath = $targetDir . $newFileName;
        
//         if (!move_uploaded_file($file['tmp_name'], $filePath)) {
//             echo json_encode(["error" => "File upload failed"]);
//             exit;
//         }

//         // อัปเดตฐานข้อมูล
//         $stmt = $pdo->prepare("UPDATE document SET file_name = ?, uploaded_at = ?, updated_by = ? WHERE id = ? AND category_id = ?");
//         $success = $stmt->execute([$newFileName, $uploaded_at, $updated_by, $id, $category_id]);
//     } else {
//         $stmt = $pdo->prepare("UPDATE document SET uploaded_at = ?, updated_by = ? WHERE id = ? AND category_id = ?");
//         $success = $stmt->execute([$uploaded_at, $updated_by, $id, $category_id]);
//     }
    
//     if ($success) {
//         echo json_encode(["success" => true, "message" => "Update successful"]);
//     } else {
//         echo json_encode(["success" => false, "message" => "Update failed"]);
//     }
// }

?>
