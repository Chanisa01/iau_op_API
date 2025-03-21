<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

include 'db_connect.php';
// เปิดการแสดง Error (สำหรับ Debug)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // ดึงข้อมูลเอกสารของคณะกรรมการตรวจสอบ
    $sql = "SELECT d.*, u.prename, u.name, u.surname
            FROM document d
            JOIN users u ON d.updated_by = u.id
            JOIN document_categories dc ON d.category_id = dc.id
            WHERE dc.category_name = 'คณะกรรมการตรวจสอบ'";

    
    $result = $conn->query($sql);

    if ($result) {
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(["success" => true, "documentData" => $rows ?: []]); // ถ้าไม่มีข้อมูลให้ส่ง array ว่าง
    } else {
        echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการดึงข้อมูล", "error" => $conn->error]);
    }

    exit;
}
?>
