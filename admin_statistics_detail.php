<?php
session_start();

// Kiểm tra quyền Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once "database.php";
$db = new Database();

// 1. Lấy ngày từ bộ lọc (Sửa lỗi định dạng ngày)
$from_date = $_GET['from_date'] ?? date('Y-m-d', strtotime('-7 days'));
$to_date = $_GET['to_date'] ?? date('Y-m-d');

// 2. SQL: Đã thêm DATE() vào tất cả các chỗ so sánh ngày tháng
$sql = "SELECT 
            DATE(order_date) as order_day, 
            SUM(total_amount) as daily_revenue, 
            COUNT(order_id) as total_orders,
            (SELECT SUM(od.quantity) 
             FROM order_details od 
             JOIN orders o2 ON od.order_id = o2.order_id 
             WHERE DATE(o2.order_date) = DATE(o.order_date) 
             AND o2.status IN ('Đã xác nhận', 'Đã giao')) as total_products
        FROM orders o
        WHERE DATE(order_date) BETWEEN ? AND ? 
        AND status IN ('Đã xác nhận', 'Đã giao')
        GROUP BY DATE(order_date)
        ORDER BY order_day DESC";

$details = $db->select($sql, 'ss', [$from_date, $to_date]);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bảng chi tiết doanh thu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #fdfae7; font-family: 'Segoe UI', sans-serif; }
        .header-title { background-color: #6f42c1; color: white; padding: 10px 40px; border-radius: 5px; display: inline-block; }
        .table-custom { background-color: #4e73df; color: white; border-radius: 10px; overflow: hidden; }
        .table-custom thead { background-color: #2e59d9; }
        .table-custom tbody { background-color: #4e73df; }
        .table-custom td, .table-custom th { border: 1px solid rgba(255,255,255,0.1); padding: 15px; text-align: center; vertical-align: middle; }
        .btn-export { background-color: #36b9cc; color: white; font-weight: bold; padding: 10px 30px; border-radius: 5px; border: none; transition: 0.3s; }
        .btn-export:hover { background-color: #2c9faf; }
    </style>
</head>
<body>

<div class="container py-5 text-center">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <a href="admin_dashboard.php" class="btn btn-sm btn-outline-dark fw-bold"><i class="bi bi-house-door"></i> ADMIN</a>
        <div class="header-title fw-bold text-uppercase shadow-sm">BẢNG CHI TIẾT DOANH THU</div>
        <div class="text-end small">
            <p class="mb-0 fw-bold">Từ: <?= date('d/m/Y', strtotime($from_date)) ?></p>
            <p class="mb-0 fw-bold">Đến: <?= date('d/m/Y', strtotime($to_date)) ?></p>
        </div>
    </div>

    <div class="table-responsive shadow-lg mb-5" style="border-radius: 15px;">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th><i class="bi bi-calendar3 me-2"></i>Thời gian</th>
                    <th><i class="bi bi-currency-dollar me-2"></i>Doanh thu</th>
                    <th><i class="bi bi-cart-check me-2"></i>Số đơn</th>
                    <th><i class="bi bi-laptop me-2"></i>Sản phẩm</th>
                    <th><i class="bi bi-calculator me-2"></i>TB/Đơn</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (!empty($details)): 
                    foreach ($details as $row): 
                        $avg = ($row['total_orders'] > 0) ? ($row['daily_revenue'] / $row['total_orders']) : 0;
                ?>
                <tr>
                    <td class="fw-bold"><?= date('d/m/Y', strtotime($row['order_day'])) ?></td>
                    <td class="fw-bold text-warning"><?= number_format($row['daily_revenue'], 0, ',', '.') ?> đ</td>
                    <td><?= $row['total_orders'] ?> đơn</td>
                    <td><?= $row['total_products'] ?? 0 ?> máy</td>
                    <td class="small"><?= number_format($avg, 0, ',', '.') ?> đ</td>
                </tr>
                <?php 
                    endforeach; 
                else: 
                ?>
                <tr><td colspan="5" class="py-5 bg-light text-dark">Dữ liệu hiện đang trống trong khoảng thời gian này.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center gap-3">
        <button onclick="window.print()" class="btn-export shadow"><i class="bi bi-printer me-2"></i>In báo cáo</button>
        <a href="admin_statistics.php" class="btn btn-secondary px-5 py-2 fw-bold shadow rounded-pill">Quay lại</a>
    </div>
</div>

</body>
</html>