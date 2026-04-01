<?php
session_start();
require_once "database.php";
$db = new Database();

// 1. Lấy ID sản phẩm từ URL
$id = isset($_GET['id']) ? $_GET['id'] : null;

if ($id) {
    // 2. Định nghĩa câu lệnh SQL TRƯỚC khi gọi hàm select
    $sql = "SELECT product_name, price, image_url FROM products WHERE product_id = ?";
    
    // 3. Thực hiện truy vấn
    $result = $db->select($sql, 'i', [$id]);

    // Debug thử (Xóa dòng này sau khi chạy được)
    // var_dump($result); die(); 

    if (count($result) > 0) {
        $p = $result[0];

        // 4. Khởi tạo giỏ hàng nếu chưa có
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // 5. Kiểm tra và thêm sản phẩm
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['qty'] += 1;
        } else {
            $_SESSION['cart'][$id] = [
                'name' => $p['product_name'],
                'price' => $p['price'],
                'image' => $p['image_url'],
                'qty' => 1
            ];
        }
    }
}

// 6. Chuyển hướng
header("Location: view_cart.php");
exit();