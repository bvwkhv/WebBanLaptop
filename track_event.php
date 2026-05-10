<?php
session_start();
require_once "database.php";
$db = new Database();

header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Hỗ trợ cả trường hợp gửi qua POST thường hoặc JSON fetch
$event_type = $data['event_type'] ?? ($_POST['event_type'] ?? null);
$target_id = $data['target_id'] ?? ($_POST['target_id'] ?? null);
$user_id = $_SESSION['user_id'] ?? null;

if ($event_type && $target_id) {
    $sql = "INSERT INTO event_tracking (event_type, target_id, user_id, created_at) 
            VALUES (?, ?, ?, NOW())";
    
    // Đảm bảo hàm execute của bạn hoạt động đúng
    $db->execute($sql, 'ssi', [$event_type, $target_id, $user_id]);

    echo json_encode(['status' => 'success', 'received' => $event_type]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Missing data', 'debug' => $data]);
}
?>