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
                $query = "SELECT image_personal_name FROM personal WHERE id_personal = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id_personal);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $old_image = $row['image_personal_name'] ?? null; // กันกรณีที่ไม่มีข้อมูล
                $stmt->close();

                $stmt = $conn->prepare("DELETE FROM personal WHERE id_personal = ?");
                $stmt->bind_param("i", $id_personal);

                if ($stmt->execute()) {
                    if (!empty($old_image)) {
                        $oldFilePath = $targetDir . $old_image;
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath); // ลบไฟล์เก่า
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
                        $newFileName = "Personel_{$id_personal}_". time().".{$fileExtension}";
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

                // ดึงชื่อไฟล์รูปเก่าจากฐานข้อมูล
                $query = "SELECT image_personal_name FROM personal WHERE id_personal = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id_personal);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $old_image = $row['image_personal_name'];

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

                    $oldImagePath = $targetDir . $old_image;
                    if (file_exists($oldImagePath) && !empty($old_image)) {
                        unlink($oldImagePath); // ลบไฟล์เก่า
                    }

                    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $newFileName = "Personel_{$id_personal}_". time().".{$fileExtension}";
                    $targetFilePath = $targetDir . $newFileName;
        
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
?>
