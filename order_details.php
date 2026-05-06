<?php
session_start();
require_once "database.php";
$db = new Database();

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. Lấy thông tin đơn hàng
$sql_order = "SELECT o.*, pr.name as promo_name 
              FROM orders o 
              LEFT JOIN promotions pr ON o.promotion_id = pr.promotion_id 
              WHERE o.order_id = ?";
$order_result = $db->select($sql_order, 'i', [$order_id]);

if (empty($order_result)) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Không tìm thấy đơn hàng #$order_id!</div></div>";
    exit();
}
$order = $order_result[0];

// 2. Lấy danh sách sản phẩm
$sql_items = "SELECT od.*, p.product_name 
              FROM order_details od 
              JOIN products p ON od.product_id = p.product_id
              WHERE od.order_id = ?";
$items = $db->select($sql_items, 'i', [$order_id]);

// 3. Tính toán tiền hàng gốc
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += (float)$item['price'] * (int)$item['quantity'];
}

// 4. FIX LOGIC GIẢM GIÁ:
// Nếu có sản phẩm, dùng subtotal trừ đi total_amount trong DB
// Nếu danh sách sản phẩm trống (do lỗi lưu dữ liệu), mặc định coi subtotal = total_amount để không hiện 0đ
if ($subtotal <= 0) {
    $subtotal = (float)$order['total_amount'];
    $discount = 0;
} else {
    $discount = round($subtotal) - round((float)$order['total_amount']);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?= $order_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: none; }
        .card-header { background: linear-gradient(45deg, #007bff, #0056b3); border-radius: 15px 15px 0 0 !important; }
        .table tfoot td { border: none; padding: 5px 10px; }
        .status-badge { padding: 5px 15px; border-radius: 20px; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="container mt-3">
    <a href="index.php" class="btn btn-outline-secondary rounded-pill shadow-sm">
        <i class="fa-solid fa-arrow-left me-2"></i>Home
    </a>
    </div>
<div class="container mt-5 mb-5">
    <div class="card">
        <div class="card-header text-white p-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Mã đơn hàng: #<?= $order_id ?></h5>
            <span class="badge bg-light text-primary status-badge"><?= htmlspecialchars($order['status']) ?></span>
        </div>
        <div class="card-body p-4">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-primary fw-bold mb-3 uppercase">Thông tin giao hàng</h6>
                    <p class="mb-1"><strong>Họ tên:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                    <p class="mb-1"><strong>Điện thoại:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                    <p class="mb-1 text-muted"><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['address']) ?></p>
                    <?php if(!empty($order['note'])): ?>
                        <p class="mb-1 small"><strong>Ghi chú:</strong> <em><?= htmlspecialchars($order['note']) ?></em></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <h6 class="text-primary fw-bold mb-3">Chi tiết thanh toán</h6>
                    <p class="mb-1">Phương thức: <span class="badge bg-secondary"><?= htmlspecialchars($order['payment_method']) ?></span></p>
                    <p class="mb-1 text-muted">Thời gian: <?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Sản phẩm</th>
                            <th class="text-center">Số lượng</th>
                            <th class="text-end">Đơn giá</th>
                            <th class="text-end">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($items)): ?>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="fw-medium"><?= htmlspecialchars($item['product_name']) ?></td>
                                <td class="text-center">x<?= $item['quantity'] ?></td>
                                <td class="text-end"><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
                                <td class="text-end fw-bold"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-muted">Dữ liệu sản phẩm đang được cập nhật...</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="border-top">
                        <tr>
                            <td colspan="3" class="text-end text-muted pt-3">Tạm tính:</td>
                            <td class="text-end pt-3"><?= number_format($subtotal, 0, ',', '.') ?>đ</td>
                        </tr>
                        
                        <?php if ($discount > 0): ?>
                        <tr>
                            <td colspan="3" class="text-end text-success">
                                Giảm giá <?= !empty($order['promo_name']) ? "(".htmlspecialchars($order['promo_name']).")" : "" ?>:
                            </td>
                            <td class="text-end text-success fw-bold">-<?= number_format($discount, 0, ',', '.') ?>đ</td>
                        </tr>
                        <?php endif; ?>

                        <tr>
                            <th colspan="3" class="text-end fs-5 pt-3">TỔNG THANH TOÁN:</th>
                            <th class="text-end text-danger fs-3 pt-2"><?= number_format($order['total_amount'], 0, ',', '.') ?>đ</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-4 d-flex gap-2">
                <a href="order_history.php" class="btn btn-secondary px-4 py-2">Lịch sử</a>
                <button onclick="window.print()" class="btn btn-primary px-4 py-2">Xuất hóa đơn (PDF)</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>