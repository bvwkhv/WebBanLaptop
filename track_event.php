<?php
require_once "database.php";
session_start();
$db = new Database();

// Nhận dữ liệu từ AJAX
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $user_id = $_SESSION['user_id'] ?? null;
    $event_type = $data['event_type'];
    $target_id = $data['target_id'];
    $page_url = $_SERVER['HTTP_REFERER'] ?? '';

    $sql = "INSERT INTO event_tracking (user_id, event_type, target_id, page_url) VALUES (?, ?, ?, ?)";
    $db->execute($sql, 'isss', [$user_id, $event_type, $target_id, $page_url]);
    
    echo json_encode(['status' => 'success']);
}
?>