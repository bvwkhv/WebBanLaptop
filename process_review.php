<?php
session_start();
require_once "database.php";
$db = new Database();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $product_id = $_POST['product_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    // 1. Lưu số sao vào bảng ratings
    $sql_rate = "INSERT INTO ratings (product_id, user_id, star_count, created_at) VALUES (?, ?, ?, NOW())";
    $db->execute($sql_rate, "iii", [$product_id, $user_id, $rating]);

    // 2. Lưu nội dung bình luận vào bảng comments
    if (!empty($comment)) {
        $sql_comm = "INSERT INTO comments (product_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())";
        $db->execute($sql_comm, "iis", [$product_id, $user_id, $comment]);
    }

    // 3. Quay lại trang chi tiết sản phẩm kèm thông báo thành công (nếu muốn)
    header("Location: product_details.php?id=" . $product_id . "&status=success");
    exit();
} else {
    header("Location: index.php");
    exit();
}