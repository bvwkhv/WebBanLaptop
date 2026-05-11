<?php
require_once "database.php";
session_start();
$db = new Database();

if (isset($_POST['receiver_id']) && isset($_SESSION['user_id'])) {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = (int)$_POST['receiver_id'];
    $message = trim($_POST['message']);
    $image_url = null;

    // Xử lý Upload ảnh cho Admin
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/"; // Dùng chung thư mục với user
        $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $file_name = "admin_" . time() . '_' . uniqid() . '.' . $file_extension;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $file_name)) {
            $image_url = $file_name;
        }
    }

    if (!empty($message) || $image_url !== null) {
        $sql = "INSERT INTO messages (sender_id, receiver_id, message_text, image_url) VALUES (?, ?, ?, ?)";
        $db->insert($sql, "iiss", [$sender_id, $receiver_id, $message, $image_url]);
        echo json_encode(['status' => 'success']);
    }
}