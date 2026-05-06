<?php
session_start();
require_once "database.php";
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_SESSION['cart'])) {
        header("Location: index.php");
        exit();
    }

    // 1. Kiểm tra đăng nhập
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        echo "<script>alert('Vui lòng đăng nhập để thanh toán!'); window.location.href='login.php';</script>";
        exit();
    }

    // 2. Lấy dữ liệu an toàn từ POST
    $fullname = $_POST['fullname'] ?? '';
    $phone    = $_POST['phone'] ?? '';
    $address  = $_POST['address'] ?? '';
    $note     = $_POST['note'] ?? '';
    $payment  = $_POST['payment_method'] ?? 'COD';
    $total    = (float)($_POST['total_amount'] ?? 0); 
    $promo_id = !empty($_POST['applied_promo_id']) ? (int)$_POST['applied_promo_id'] : null;

    // 3. INSERT vào bảng orders
    // Lưu ý: Trạng thái ban đầu nên để là 'Chờ xác nhận' hoặc 'Chờ xử lý'
    $sql_order = "INSERT INTO orders (user_id, customer_name, phone, address, note, total_amount, payment_method, promotion_id, status, order_date) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Chờ xử lý', NOW())";

    $order_id = $db->insert_get_id($sql_order, 'issssdsi', [
        $user_id,
        $fullname, 
        $phone, 
        $address, 
        $note, 
        $total, 
        $payment, 
        $promo_id
    ]);

    if ($order_id) {
        // 4. LƯU CHI TIẾT ĐƠN HÀNG (Phần quan trọng nhất)
        foreach ($_SESSION['cart'] as $id_sp_trong_cart => $item) {
            $sql_detail = "INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            
            // FIX: Ưu tiên lấy ID sản phẩm từ key của mảng session hoặc biến nội bộ
            // Tùy vào cách bạn lưu giỏ hàng, thường là $_SESSION['cart'][$id] = [...]
            $p_id  = (int)($item['product_id'] ?? ($item['id'] ?? $id_sp_trong_cart));
            $qty   = (int)($item['qty'] ?? ($item['quantity'] ?? 1));
            $price = (float)($item['price'] ?? 0);

            // Kiểm tra ID sản phẩm phải lớn hơn 0 mới lưu
            if ($p_id > 0) {
                // Sử dụng hàm insert hoặc execute tùy theo class database.php của bạn
                $db->execute($sql_detail, 'iiid', [$order_id, $p_id, $qty, $price]);
            }
        }

        // 5. Hoàn tất
        unset($_SESSION['cart']);
        // Chuyển hướng sang trang xem hóa đơn vừa đặt
        header("Location: order_details.php?id=" . $order_id);
        exit();
    } else {
        echo "Lỗi: Không thể tạo đơn hàng!";
    }
}
?>