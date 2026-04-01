<?php
require_once "database.php";
$db = new Database();

// 1. Lấy ngày từ bộ lọc (Mặc định 7 ngày gần nhất để bảng có dữ liệu)
$from_date = $_GET['from_date'] ?? date('Y-m-d', strtotime('-7 days'));
$to_date = $_GET['to_date'] ?? date('Y-m-d');

// 2. SQL: Nhóm dữ liệu theo từng ngày (GROUP BY order_date)
$sql = "SELECT 
            order_date, 
            SUM(total_amount) as daily_revenue, 
            COUNT(order_id) as total_orders,
            (SELECT SUM(od.quantity) FROM order_details od 
             JOIN orders o2 ON od.order_id = o2.order_id 
             WHERE o2.order_date = o.order_date AND o2.status IN ('Đã xác nhận', 'Đã giao')) as total_products
        FROM orders o
        WHERE order_date BETWEEN ? AND ? 
        AND status IN ('Đã xác nhận', 'Đã giao')
        GROUP BY order_date
        ORDER BY order_date DESC";

$details = $db->select($sql, 'ss', [$from_date, $to_date]);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bảng chi tiết doanh thu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #fdfae7; font-family: 'Segoe UI', sans-serif; }
        .header-title { background-color: #6f42c1; color: white; padding: 10px 40px; border-radius: 5px; display: inline-block; }
        .table-custom { background-color: #4e73df; color: white; border-radius: 10px; overflow: hidden; }
        .table-custom thead { background-color: #4e73df; }
        .table-custom tbody { background-color: #7597f5; }
        .table-custom td, .table-custom th { border: 1px solid rgba(255,255,255,0.2); padding: 12px; text-align: center; }
        .btn-export { background-color: #36b9cc; color: white; font-weight: bold; padding: 10px 30px; border-radius: 5px; border: none; }
    </style>
</head>
<body>

<div class="container py-5 text-center">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <a href="index.php" class="text-dark fw-bold text-decoration-none">HOME</a>
        <div class="header-title fw-bold text-uppercase">Bảng chi tiết</div>
        <div class="text-end">
            <p class="mb-0">Ngày tạo: <?= date('d/m/Y') ?></p>
        </div>
    </div>

    <div class="table-responsive shadow-sm mb-5">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Doanh thu</th>
                    <th>Số đơn</th>
                    <th>Sản phẩm</th>
                    <th>TB/Đơn</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (!empty($details)): 
                    foreach ($details as $row): 
                        // Tính trung bình mỗi đơn
                        $avg = ($row['total_orders'] > 0) ? ($row['daily_revenue'] / $row['total_orders']) : 0;
                ?>
                <tr>
                    <td><?= date('d/m', strtotime($row['order_date'])) ?></td>
                    <td><?= number_format($row['daily_revenue'] / 1000000, 1) ?>tr</td>
                    <td><?= $row['total_orders'] ?></td>
                    <td><?= $row['total_products'] ?? 0 ?></td>
                    <td><?= number_format($avg / 1000000, 1) ?>tr</td>
                </tr>
                <?php 
                    endforeach; 
                else: 
                ?>
                <tr><td colspan="5">Không có dữ liệu trong khoảng thời gian này</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center gap-3">
        <button onclick="window.print()" class="btn-export shadow">Xuất báo cáo</button>
        <a href="admin_statistics.php" class="btn btn-secondary px-4 fw-bold shadow">Quay lại</a>
    </div>
</div>

</body>
</html>