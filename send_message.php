<?php
require_once "database.php";
session_start();
$db = new Database();

if (isset($_POST['message']) && isset($_SESSION['user_id'])) {
    $sender_id = $_SESSION['user_id'];
    $message = trim($_POST['message']);
    $receiver_id = 4; // Mặc định gửi cho Admin (ID = 1)

    if (!empty($message)) {
        $sql = "INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)";
        $db->insert($sql, "iis", [$sender_id, $receiver_id, $message]);
        echo json_encode(['status' => 'success']);
    }
}