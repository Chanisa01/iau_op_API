<?php 
    date_default_timezone_set('Asia/Bangkok');

    // ตั้งค่า Header สำหรับ JSON และ CORS
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    // print error on postman
    error_reporting(E_ALL); 
    ini_set('display_errors', 1);

    include 'db_connect.php';

    // ถ้ามีการร้องขอ OPTIONS (Preflight request), ให้ตอบกลับด้วย status 200
    // if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //     http_response_code(200);
    //     exit;
    // }

    $targetDir = "img/Personal/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    // DataTable
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT personal.*, users.name AS user_name, users.surname AS user_surname 
                FROM personal 
                JOIN users ON personal.updated_by = users.id"; 
        $result = $conn->query($sql);

        // if ($result && $result->num_rows > 0) {
        //     // ใช้ fetch_all() เพื่อดึงข้อมูลทั้งหมด
        //     $rows = $result->fetch_all(MYSQLI_ASSOC);
        //     echo json_encode(["success" => true, "personal" => $rows]);
        // } else {
        //     echo json_encode(["success" => false, "message" => "ไม่พบข้อมูล"]);
        // }

        if ($result) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            
            // ส่ง `success: true` เสมอ แต่ถ้าไม่มีข้อมูลให้ personal เป็น `[]`
            echo json_encode(["success" => true, "personal" => $rows ?: []]);
        } else {
            echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการดึงข้อมูล", "error" => $conn->error]);
        }

        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            // var_dump("ffff",$action);
            if ($action === 'delete' && isset($_POST['id'])) {
                $id_personal = $_POST['id'];
                // var_dump('ddddd test alert');
                $stmt = $conn->prepare("DELETE FROM personal WHERE id_personal = ?");
                $stmt->bind_param("i", $id_personal);

                if ($stmt->execute()) {
                    $oldFiles = glob($targetDir . "Personel_{$id_personal}.*"); // ค้นหาทุกนามสกุล
                    foreach ($oldFiles as $oldFile) {
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }
                    echo json_encode(["success" => true, "message" => "ลบข้อมูลสำเร็จ"]);
                } else{
                    http_response_code(500);
                    echo json_encode(["success" => false, "message" => "ไม่สามารถลบข้อมูลได้"]);
                }
                $stmt->close();
                exit;
            }elseif ($action === 'insert') {
                $prename = $_POST['prename'];
                $name = $_POST['name'];
                $surname = $_POST['surname'];
                $position = $_POST['position'];
                $department = $_POST['department'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $extension = $_POST['extension'];
                $updated_by = $_POST['updated_by'];

                $stmt = $conn->prepare("INSERT INTO personal (prename, name, surname, position, department, email, phone, extension, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssi", $prename, $name, $surname, $position, $department, $email, $phone, $extension, $updated_by);
                if ($stmt->execute()) {
                    $id_personal = $conn->insert_id; // รับค่า id ล่าสุดที่เพิ่มเข้าไป
                    $newFileName = null;
                    
                    if (isset($_FILES['image_personal_name']) && $_FILES['image_personal_name']['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES['image_personal_name'];
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                        $maxSize = 1000000;

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

                        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $newFileName = "Personel_{$id_personal}.{$fileExtension}";
                        $targetFilePath = $targetDir . $newFileName;

                        if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                            http_response_code(500);
                            echo json_encode(["success" => false, "message" => "ไม่สามารถบันทึกไฟล์ได้"]);
                            exit;
                        }

                        $stmt = $conn->prepare("UPDATE personal SET image_personal_name = ? WHERE id_personal = ?");
                        $stmt->bind_param("si", $newFileName, $id_personal);
                        $stmt->execute();
                        $stmt->close();
                    }
                    echo json_encode(["success" => true, "message" => "เพิ่มข้อมูลสำเร็จ", "id_personal" => $id_personal]);
                }else {
                    http_response_code(500);
                    echo json_encode(["success" => false, "message" => "ไม่สามารถเพิ่มข้อมูลได้"]);
                }

            }elseif ($action === 'update' && isset($_POST['id_personal'])) {
                $id_personal = $_POST['id_personal'];
                $prename = $_POST['prename'];
                $name = $_POST['name'];
                $surname = $_POST['surname'];
                $position = $_POST['position'];
                $department = $_POST['department'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $extension = $_POST['extension'];
                $updated_by = $_POST['updated_by'];
            
                $newFileName = null;
            
                if (isset($_FILES['image_personal_name']) && $_FILES['image_personal_name']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['image_personal_name'];
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                    $maxSize = 1000000;
            
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
            
                    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $newFileName = "Personel_{$id_personal}.{$fileExtension}";
                    $targetFilePath = $targetDir . $newFileName;
        
                    $oldFiles = glob($targetDir . "Personel_{$id_personal}.*"); // ค้นหาทุกนามสกุล
                    foreach ($oldFiles as $oldFile) {
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }
            
                    if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                        http_response_code(500);
                        echo json_encode(["success" => false, "message" => "ไม่สามารถบันทึกไฟล์ได้"]);
                        exit;
                    }
                }
                //var_dump('dddd test alret');
                if ($newFileName) {
                    $stmt = $conn->prepare("UPDATE personal SET 
                                            prename = ?, name = ?, surname = ?, image_personal_name = ?, 
                                            position = ?, department = ?, email = ?, 
                                            phone = ?, extension = ?, updated_by = ?
                                            WHERE id_personal = ?");
                    $stmt->bind_param("sssssssssii", $prename, $name, $surname, $newFileName, $position, $department, $email, $phone, $extension, $updated_by, $id_personal);
                } else {
                    $stmt = $conn->prepare("UPDATE personal SET prename = ?,
                                            name = ?, surname = ?, position = ?, 
                                            department = ?, email = ?, phone = ?, 
                                            extension = ?, updated_by = ?
                                            WHERE id_personal = ?");
                    $stmt->bind_param("ssssssssii", $prename, $name, $surname, $position, $department, $email, $phone, $extension, $updated_by, $id_personal);
                }
            
                if ($stmt->execute()) {
                    echo json_encode(["success" => true, "message" => "อัปเดตข้อมูลสำเร็จ"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["success" => false, "message" => "ไม่สามารถบันทึกข้อมูลลงในฐานข้อมูล"]);
                }
            
                $stmt->close();
            }
        }else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Action ไม่ถูกต้อง"]);
        }
    }else {
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    }

    // if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //     if(isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])){
    //         $id_personal = $_POST['id'];
    //         // var_dump('ddddd test alert');
    //         $stmt = $conn->prepare("DELETE FROM personal WHERE id_personal = ?");
    //         $stmt->bind_param("i", $id_personal);

    //         if ($stmt->execute()) {
    //             $oldFiles = glob($targetDir . "Personel_{$id_personal}.*"); // ค้นหาทุกนามสกุล
    //             foreach ($oldFiles as $oldFile) {
    //                 if (file_exists($oldFile)) {
    //                     unlink($oldFile);
    //                 }
    //             }
    //             echo json_encode(["success" => true, "message" => "ลบข้อมูลสำเร็จ"]);
    //         } else{
    //             http_response_code(500);
    //             echo json_encode(["success" => false, "message" => "ไม่สามารถลบข้อมูลได้"]);
    //         }
    //         $stmt->close();
    //         exit;
    //     }else{
    //         $id_personal = $_POST['id_personal'];
    //         $prename = $_POST['prename'];
    //         $name = $_POST['name'];
    //         $surname = $_POST['surname'];
    //         $position = $_POST['position'];
    //         $department = $_POST['department'];
    //         $email = $_POST['email'];
    //         $phone = $_POST['phone'];
    //         $extension = $_POST['extension'];
    //         $updated_by = $_POST['updated_by'];
           
    //         $newFileName = null;
        
    //         if (isset($_FILES['image_personal_name']) && $_FILES['image_personal_name']['error'] === UPLOAD_ERR_OK) {
    //             $file = $_FILES['image_personal_name'];
    //             $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    //             $maxSize = 1000000;
        
    //             if (!in_array($file['type'], $allowedTypes)) {
    //                 http_response_code(400);
    //                 echo json_encode(["status" => "error", "message" => "อนุญาตเฉพาะไฟล์ JPG, JPEG และ PNG เท่านั้น"]);
    //                 exit;
    //             }
        
    //             if ($file['size'] > $maxSize) {
    //                 http_response_code(400);
    //                 echo json_encode(["status" => "error", "message" => "ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 1000KB)"]);
    //                 exit;
    //             }
        
    //             $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    //             $newFileName = "Personel_{$id_personal}.{$fileExtension}";
    //             $targetFilePath = $targetDir . $newFileName;
    
    //             $oldFiles = glob($targetDir . "Personel_{$id_personal}.*"); // ค้นหาทุกนามสกุล
    //             foreach ($oldFiles as $oldFile) {
    //                 if (file_exists($oldFile)) {
    //                     unlink($oldFile);
    //                 }
    //             }
        
    //             if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
    //                 http_response_code(500);
    //                 echo json_encode(["success" => false, "message" => "ไม่สามารถบันทึกไฟล์ได้"]);
    //                 exit;
    //             }
    //         }
    //         //var_dump('dddd test alret');
    //         if ($newFileName) {
    //             $stmt = $conn->prepare("UPDATE personal SET 
    //                                     prename = ?, name = ?, surname = ?, image_personal_name = ?, 
    //                                     position = ?, department = ?, email = ?, 
    //                                     phone = ?, extension = ?, updated_by = ?
    //                                     WHERE id_personal = ?");
    //             $stmt->bind_param("sssssssssii", $prename, $name, $surname, $newFileName, $position, $department, $email, $phone, $extension, $updated_by, $id_personal);
    //         } else {
    //             $stmt = $conn->prepare("UPDATE personal SET prename = ?,
    //                                     name = ?, surname = ?, position = ?, 
    //                                     department = ?, email = ?, phone = ?, 
    //                                     extension = ?, updated_by = ?
    //                                     WHERE id_personal = ?");
    //             $stmt->bind_param("ssssssssii", $prename, $name, $surname, $position, $department, $email, $phone, $extension, $updated_by, $id_personal);
    //         }
        
    //         if ($stmt->execute()) {
    //             echo json_encode(["success" => true, "message" => "อัปเดตข้อมูลสำเร็จ"]);
    //         } else {
    //             http_response_code(500);
    //             echo json_encode(["success" => false, "message" => "ไม่สามารถบันทึกข้อมูลลงในฐานข้อมูล"]);
    //         }
        
    //         $stmt->close();
    //     }
    // } else {
    //     http_response_code(405);
    //     echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    // }

    // if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //     if (isset($_FILES['image_personal_name'])) {
    //         $file = $_FILES['image_personal_name'];
    //         $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    //         $maxSize = 1000000;
    
    //         if ($file['error'] !== UPLOAD_ERR_OK) {
    //             http_response_code(400);
    //             echo json_encode(["status" => "error", "message" => "เกิดข้อผิดพลาดในการอัปโหลดไฟล์"]);
    //             exit;
    //         }
    
    //         if (!in_array($file['type'], $allowedTypes)) {
    //             http_response_code(400);
    //             echo json_encode(["status" => "error", "message" => "อนุญาตเฉพาะไฟล์ JPG, JPEG และ PNG เท่านั้น"]);
    //             exit;
    //         }
    
    //         if ($file['size'] > $maxSize) {
    //             http_response_code(400);
    //             echo json_encode(["status" => "error", "message" => "ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 1000KB)"]);
    //             exit;
    //         }
           
            
    //         $id_personal = $_POST['id_personal'];
    //         $name = $_POST['name'];
    //         $surname = $_POST['surname'];
    //         $position = $_POST['position'];
    //         $department = $_POST['department'];
    //         $email = $_POST['email'];
    //         $phone = $_POST['phone'];
    //         $extension = $_POST['extension'];
    //         $updated_by = $_POST['updated_by'];
    
    //         $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); 
    //         $newFileName = "Personel_{$id_personal}.{$fileExtension}";
    //         $targetFilePath = $targetDir . $newFileName;
    //         // var_dump('sss',$targetFilePath);

    //         $oldFiles = glob($targetDir . "Personel_{$id_personal}.*"); // ค้นหาทุกนามสกุล
    //         foreach ($oldFiles as $oldFile) {
    //             if (file_exists($oldFile)) {
    //                 unlink($oldFile);
    //             }
    //         }   

    //         if (move_uploaded_file($file['tmp_name'], $targetFilePath)) { 
    //             $stmt = $conn->prepare("UPDATE personal 
    //                                            SET  name = ?,
    //                                                 surname = ?, 
    //                                                 image_personal_name =?,
    //                                                 position = ?, 
    //                                                 department = ?, 
    //                                                 email = ?, 
    //                                                 phone = ?, 
    //                                                 extension = ?, 
    //                                                 updated_by = ?
                                                     
    //                                             WHERE id_personal = ?");
    //             if (!$stmt) {
    //                 http_response_code(500);
    //                 echo json_encode(["success" => false, "message" => "ข้อผิดพลาดในการเตรียมคำสั่ง SQL"]);
    //                 exit;
    //             }
    //             $stmt->bind_param("sssssssssi", $name, $surname, $newFileName, $position, $department, $email, $phone, $extension, $updated_by, $id_personal);
    
    //             if ($stmt->execute()) {
    //                 echo json_encode([
    //                     "success" => true,
    //                     "message" => "อัปโหลดและบันทึกไฟล์สำเร็จ",
    //                 ]);
    //             } else {
    //                 http_response_code(500);
    //                 echo json_encode(["success" => false, "message" => "ไม่สามารถบันทึกข้อมูลลงในฐานข้อมูล"]);
    //             }
    
    //             $stmt->close();
    //         } else {
    //             http_response_code(500);
    //             echo json_encode(["success" => false, "message" => "ไม่สามารถบันทึกไฟล์ได้"]);
    //         }
    //     } else {
    //         http_response_code(400);
    //         echo json_encode(["success" => false, "message" => "ไม่พบไฟล์ที่ถูกส่งมา"]);
    //     }
    // } else {
    //     http_response_code(405);
    //     echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    // }

    // if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    //     // อ่านข้อมูล JSON ที่ถูกส่งมาจาก client
    //     parse_str(file_get_contents("php://input"), $_DELETE);
    
    //     // ตรวจสอบว่ามีค่า id_personal หรือไม่
    //     if (isset($_DELETE['id_personal'])) {
    //         $id_personal = $_DELETE['id_personal'];
    
    //         // สร้าง query เพื่อทำการลบข้อมูล
    //         $sql = "DELETE FROM personal WHERE id_personal = ?";
    
    //         // เตรียม statement
    //         $stmt = $conn->prepare($sql);
    
    //         // ผูกพารามิเตอร์
    //         $stmt->bind_param("i", $id_personal);
    
    //         // ประมวลผลคำสั่ง SQL
    //         if ($stmt->execute()) {
    //             // ส่งผลลัพธ์เมื่อการลบสำเร็จ
    //             echo json_encode(['success' => true, 'message' => 'Data deleted successfully']);
    //         } else {
    //             // ส่งผลลัพธ์เมื่อเกิดข้อผิดพลาด
    //             echo json_encode(['success' => false, 'message' => 'Error deleting data']);
    //         }
    
    //         // ปิด statement
    //         $stmt->close();
    //     } else {
    //         echo json_encode(['success' => false, 'message' => 'id_personal not provided']);
    //     }
    // } else {
    //     echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    // }
?>
