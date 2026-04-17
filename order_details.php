<?php
session_start();
require_once "database.php";
$db = new Database();

$order_id = $_GET['id'] ?? 0;

// 1. Lấy thông tin chung của đơn hàng
$sql_order = "SELECT * FROM orders WHERE order_id = ?";
$order = $db->select($sql_order, 'i', [$order_id]);

if (empty($order)) {
    echo "Không tìm thấy đơn hàng!";
    exit();
}
$order = $order[0]; // Lấy dòng đầu tiên

// Sửa p.name thành tên cột đúng trong bảng của bạn (ví dụ p.title)
$sql_items = "SELECT od.*, p.product_name as prod_name 
              FROM order_details od 
              JOIN products p ON od.product_id = p.product_id
              WHERE od.order_id = ?";
$items = $db->select($sql_items, 'i', [$order_id]);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chi tiết đơn hàng #<?= $order_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{ background-color: #fff4c5; }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex justify-content-between">
            <h5 class="mb-0">Chi tiết đơn hàng #<?= $order_id ?></h5>
            <span>Trạng thái: <strong><?= $order['status'] ?></strong></span>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6><strong>Thông tin khách hàng</strong></h6>
                    <p class="mb-1">Khách hàng: <?= htmlspecialchars($order['customer_name']) ?></p>
                    <p class="mb-1">Số điện thoại: <?= htmlspecialchars($order['phone']) ?></p>
                    <p class="mb-1">Địa chỉ: <?= htmlspecialchars($order['address']) ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6><strong>Phương thức & Ngày đặt</strong></h6>
                    <p class="mb-1">Thanh toán: <?= $order['payment_method'] ?></p>
                    <p class="mb-1">Ngày đặt: <?= $order['order_date'] ?></p>
                </div>
            </div>

            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Sản phẩm</th>
                        <th class="text-center">Số lượng</th>
                        <th class="text-end">Đơn giá</th>
                        <th class="text-end">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $row): ?>
                    <tr>
                        <td><?= $row['prod_name'] ?></td>
                        <td class="text-center"><?= $row['quantity'] ?></td>
                        <td class="text-end"><?= number_format($row['price'], 0, ',', '.') ?>đ</td>
                        <td class="text-end"><?= number_format($row['price'] * $row['quantity'], 0, ',', '.') ?>đ</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end text-danger">TỔNG CỘNG:</th>
                        <th class="text-end text-danger fs-5"><?= number_format($order['total_amount'], 0, ',', '.') ?>đ</th>
                    </tr>
                </tfoot>
            </table>
            
            <div class="mt-4">
                <a href="order_history.php" class="btn btn-outline-secondary">Quay lại</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>