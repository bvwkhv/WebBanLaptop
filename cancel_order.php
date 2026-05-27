<?php
session_start();
require_once "database.php";
$db = new Database();

// Ngăn chặn truy cập bất hợp pháp không qua phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id'])) {
    header("Location: index.php");
    exit();
}

$order_id = (int)$_POST['order_id'];
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// 1. Kiểm tra sự tồn tại và trạng thái thực tế của đơn hàng trong Database
$sql_check = "SELECT status FROM orders WHERE order_id = ?";
$order_res = $db->select($sql_check, 'i', [$order_id]);

if (empty($order_res)) {
    echo "<script>alert('Lỗi: Đơn hàng không tồn tại trong hệ thống!'); window.history.back();</script>";
    exit();
}

$current_status = $order_res[0]['status'];

// 2. Kiểm duyệt bảo mật trạng thái (Double-Check) tránh trường hợp người dùng cố ý bypass giao diện
$allowed = false;
if ($is_admin) {
    if (!in_array($current_status, ['Đã giao', 'Thành công', 'Đã hủy'])) {
        $allowed = true;
    }
} else {
    if ($current_status == 'Chờ xử lý' || $current_status == 'Chờ xác nhận') {
        $allowed = true;
    }
}

if (!$allowed) {
    echo "<script>alert('Thao tác không hợp lệ! Trạng thái đơn hàng hiện tại không cho phép hủy.'); window.history.back();</script>";
    exit();
}

// 3. Tiến hành cập nhật trạng thái đơn hàng sang 'Đã hủy'
// Lưu ý: Hãy đổi tên hàm 'execute' bên dưới thành tên hàm thực thi lệnh UPDATE/INSERT trong class Database của bạn (ví dụ: query, update, v.v...)
$sql_update = "UPDATE orders SET status = 'Đã hủy' WHERE order_id = ?";
$db->execute($sql_update, 'i', [$order_id]); 

// 4. Đồng thời chèn thêm bản ghi vào lịch sử hành trình 'order_history' để Timeline cập nhật thời gian hủy thực tế
$sql_log = "INSERT INTO order_history (order_id, status_name, description, location, created_at) 
            VALUES (?, 'Đã hủy', ?, '', NOW())";

$actor = $is_admin ? "Quản trị viên" : "Khách hàng";
$description = "Đơn hàng đã được hủy thành công bởi $actor.";
$db->execute($sql_log, 'is', [$order_id, $description]);

// 5. Thông báo thành công và chuyển hướng người dùng quay lại trang chi tiết trước đó
echo "<script>
    alert('Hủy đơn hàng #$order_id thành công!'); 
    window.location.href = 'order_details.php?id=$order_id'; 
</script>";
exit();