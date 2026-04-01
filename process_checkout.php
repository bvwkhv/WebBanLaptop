<?php
session_start();
require_once "database.php";
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_SESSION['cart'])) {
    $customer_name = $_POST['fullname']; 
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $note = $_POST['note'];
    
    $total_all = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_all += $item['price'] * $item['qty'];
    }

    // SỬA TÊN CỘT: total_amount và status
    // Mình thêm 'Chờ xử lý' vào cột status luôn
    $sql_order = "INSERT INTO orders (customer_name, email, phone, address, note, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    // 7 dấu chấm hỏi tương ứng với 7 tham số: sssssds (s: chuỗi, d: số thực)
    $order_id = $db->insert($sql_order, 'sssssds', [
        $customer_name, 
        $email, 
        $phone, 
        $address, 
        $note, 
        $total_all,
        'Chờ xử lý' 
    ]);

    if ($order_id) {
        // Lưu chi tiết đơn hàng
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $sql_detail = "INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $db->insert($sql_detail, 'iiid', [$order_id, $product_id, $item['qty'], $item['price']]);
        }

        unset($_SESSION['cart']);
        echo "<script>alert('Đặt hàng thành công! Đơn hàng số: " . $order_id . "'); window.location.href='index.php';</script>";
    } else {
        echo "Lỗi lưu DB: " . $db->conn->error;
    }
}