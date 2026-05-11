<?php
require_once "database.php";
session_start();
$db = new database();

if (isset($_POST['message']) && isset($_POST['receiver_id'])) {
    $sender_id = $_SESSION['user_id']; // ID Admin
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);

    if (!empty($message)) {
        $sql = "INSERT INTO messages (sender_id, receiver_id, message_text, is_read) VALUES (?, ?, ?, 1)";
        $db->insert($sql, "iis", [$sender_id, $receiver_id, $message]);
        echo json_encode(['status' => 'success']);
    }
}