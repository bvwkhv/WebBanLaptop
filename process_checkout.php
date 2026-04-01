<?php
session_start();
require_once "database.php";
$db = new Database();

// Kiểm tra nếu có dữ liệu POST và giỏ hàng không trống
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_SESSION['cart'])) {
    
    // 1. Lấy dữ liệu từ các input hidden của trang confirmation.php
    $customer_name = $_POST['fullname']; 
    $email         = $_POST['email'];
    $phone         = $_POST['phone'];
    $address       = $_POST['address'];
    $note          = $_POST['note'];
    $payment_method = $_POST['payment_method']; // Lấy từ bước chọn phương thức
    
    // Tính tổng tiền từ giỏ hàng
    $total_amount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += $item['price'] * $item['qty'];
    }

    $user_id = $_SESSION['user_id']; // Lấy số 1 từ Session
$customer_name = $_POST['fullname']; // Vẫn lấy "Nguyễn Văn Tuấn" bình thường

// 1. Câu lệnh SQL (Phải có đủ 9 dấu hỏi tương ứng 9 cột)
$sql_order = "INSERT INTO orders (user_id, customer_name, email, phone, address, note, total_amount, payment_method, status, order_date) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; 

// Thêm tham số ngày tháng vào mảng (tổng cộng 10 tham số)
$order_id = $db->insert($sql_order, 'isssssdsss', [
    $_SESSION['user_id'], 
    $customer_name, 
    $email, 
    $phone, 
    $address, 
    $note, 
    $total_amount, 
    $payment_method, 
    'Chờ xử lý',
    date('Y-m-d') // QUAN TRỌNG: Lưu ngày hiện tại để biểu đồ lấy được dữ liệu
]);

    if ($order_id) {
        // 3. Lưu chi tiết sản phẩm vào bảng order_details
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $sql_detail = "INSERT INTO order_details (order_id, product_id, quantity, price) 
                           VALUES (?, ?, ?, ?)";
            $db->insert($sql_detail, 'iiid', [
                $order_id, 
                $product_id, 
                $item['qty'], 
                $item['price']
            ]);
        }

        // 4. Đặt hàng xong thì dọn dẹp giỏ hàng
        unset($_SESSION['cart']);
        
        // Chuyển hướng sang trang thankyou.php và gửi kèm ID đơn hàng vừa tạo
header("Location: thankyou.php?order_id=" . $order_id);
exit();
    } else {
        echo "Lỗi hệ thống khi lưu đơn hàng: " . $db->conn->error;
    }
} else {
    // Nếu truy cập trái phép hoặc giỏ trống, đẩy về trang chủ
    header("Location: index.php");
    exit();
}