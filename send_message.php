<?php
require_once "database.php";
session_start();
$db = new Database();

if (isset($_SESSION['user_id'])) {
    $sender_id = $_SESSION['user_id'];
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $receiver_id = 4; // ID Admin của Tuan
    $image_url = null;

    // 1. Xử lý Upload hình ảnh (nếu có)
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";
        
        // Tự động tạo thư mục uploads nếu chưa có
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        // Đặt tên file ngẫu nhiên để không bị trùng (ví dụ: 1715421234_65ef12.jpg)
        $file_name = time() . '_' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = $file_name;
        }
    }

    // 2. Lưu vào Database (Chỉ lưu nếu có chữ HOẶC có hình)
    if (!empty($message) || $image_url !== null) {
        $sql = "INSERT INTO messages (sender_id, receiver_id, message_text, image_url) VALUES (?, ?, ?, ?)";
        $db->insert($sql, "iiss", [$sender_id, $receiver_id, $message, $image_url]);
        
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No content']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
}