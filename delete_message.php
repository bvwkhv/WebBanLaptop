<?php
require_once "database.php";
session_start();
$db = new database();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'])) {
    $msg_id = (int)$_POST['message_id'];
    $current_id = (int)$_SESSION['user_id'];

    // Chỉ cho phép xóa tin nhắn do chính mình gửi
    $sql = "DELETE FROM messages WHERE message_id = ? AND sender_id = ?";
    $result = $db->execute($sql, 'ii', [$msg_id, $current_id]);

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}