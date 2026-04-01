<?php
session_start();

// Lấy ID cần xóa từ URL
$id = isset($_GET['id']) ? $_GET['id'] : null;

if ($id && isset($_SESSION['cart'][$id])) {
    // Xóa sản phẩm khỏi mảng session
    unset($_SESSION['cart'][$id]);
}

// Quay lại trang giỏ hàng
header("Location: view_cart.php");
exit();