<?php
session_start();
require_once "database.php";
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $product_id = $_POST['product_id'];
    $user_id = $_SESSION['user_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];

    $sql = "INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)";
    $db->insert($sql, 'iiis', [$product_id, $user_id, $rating, $comment]);

    // Quay lại trang sản phẩm vừa xem
    header("Location: product_details.php?id=" . $product_id);
    exit();
}