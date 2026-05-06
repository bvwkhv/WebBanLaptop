<?php
session_start();
require_once "database.php";
$db = new Database();

// Nhận dữ liệu JSON từ Fetch API
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data) {
    $event_type = $data['event_type'];
    $target_id = $data['target_id'];
    $user_id = $_SESSION['user_id'] ?? null; // Lấy ID nếu đã đăng nhập

    $sql = "INSERT INTO event_tracking (event_type, target_id, user_id, created_at) 
            VALUES (?, ?, ?, NOW())";
    
    // Giả sử hàm execute của bạn nhận (sql, kiểu dữ liệu, mảng tham số)
    $db->execute($sql, 'ssi', [$event_type, $target_id, $user_id]);

    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
}
?>