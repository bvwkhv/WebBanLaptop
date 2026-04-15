<?php
session_start();
require_once "database.php";
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $order_id = $_POST['order_id'];
    $reason = $_POST['return_reason'];

    $sql = "UPDATE orders SET status = 'Yêu cầu trả hàng', return_reason = ? 
            WHERE order_id = ? AND user_id = ?";
    $db->insert($sql, 'sii', [$reason, $order_id, $_SESSION['user_id']]);

    header("Location: order_history.php?msg=Đã gửi yêu cầu trả hàng");
}