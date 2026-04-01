<?php
require_once "database.php";
$db = new Database();

// 1. Lấy ngày từ bộ lọc (Mặc định từ đầu tháng đến nay)
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

// 2. SQL: Tổng doanh thu & Số đơn (CHỈ TÍNH đơn 'Đã xác nhận' hoặc 'Đã giao')
// Chúng ta loại bỏ 'Chờ xử lý' (chưa chắc chắn) và 'Đã hủy' khỏi doanh thu thực tế
$sql_summary = "SELECT 
                    SUM(total_amount) as total_revenue, 
                    COUNT(order_id) as total_orders 
                FROM orders 
                WHERE order_date BETWEEN ? AND ? 
                AND status IN ('Đã xác nhận', 'Đã giao')";
$summary_res = $db->select($sql_summary, 'ss', [$from_date, $to_date]);
$summary = $summary_res[0] ?? ['total_revenue' => 0, 'total_orders' => 0];

// 3. SQL: Tổng sản phẩm đã bán (Cũng chỉ tính đơn đã chốt)
$sql_prods = "SELECT SUM(od.quantity) as total_qty 
              FROM order_details od
              JOIN orders o ON od.order_id = o.order_id
              WHERE o.order_date BETWEEN ? AND ? 
              AND o.status IN ('Đã xác nhận', 'Đã giao')";
$prods_res = $db->select($sql_prods, 'ss', [$from_date, $to_date]);
$total_qty = $prods_res[0]['total_qty'] ?? 0;

// 4. SQL: Dữ liệu cho biểu đồ cột (Doanh thu theo từng ngày)
$sql_chart = "SELECT order_date, SUM(total_amount) as daily_revenue 
              FROM orders 
              WHERE order_date BETWEEN ? AND ? 
              AND status IN ('Đã xác nhận', 'Đã giao')
              GROUP BY order_date 
              ORDER BY order_date ASC";
$chart_data = $db->select($sql_chart, 'ss', [$from_date, $to_date]);

$labels = [];
$revenues = [];
foreach ($chart_data as $row) {
    $labels[] = date('d/m', strtotime($row['order_date']));
    $revenues[] = (float)$row['daily_revenue'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thống kê báo cáo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #fdfae7; }
        .stat-card { border: none; border-radius: 12px; color: white; padding: 20px; }
        .filter-box { background: white; border-radius: 12px; padding: 20px; margin-bottom: 25px; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-pie-chart-fill me-2 text-primary"></i>THỐNG KÊ DOANH THU
            </h3>
        </div>
        <div class="d-flex gap-2">
            <a href="admin_dashboard.php" class="btn btn-dark rounded-pill px-4 shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> Trang Admin
            </a>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">Trang chủ</a>
        </div>
    </div>

    <div class="filter-box shadow-sm">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">TỪ NGÀY</label>
                <input type="date" name="from_date" class="form-control rounded-pill" value="<?= $from_date ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">ĐẾN NGÀY</label>
                <input type="date" name="to_date" class="form-control rounded-pill" value="<?= $to_date ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-warning w-100 rounded-pill fw-bold">LỌC DỮ LIỆU</button>
            </div>
        </form>
    </div>

    <div class="row g-4 mb-4 text-center">
        <div class="col-md-4">
            <div class="stat-card bg-primary shadow-sm">
                <div class="opacity-75 small">DOANH THU THỰC TẾ</div>
                <h2 class="fw-bold mb-0"><?= number_format($summary['total_revenue'] ?? 0, 0, ',', '.') ?> đ</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-info shadow-sm">
                <div class="opacity-75 small">ĐƠN HÀNG THÀNH CÔNG</div>
                <h2 class="fw-bold mb-0"><?= $summary['total_orders'] ?> đơn</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-success shadow-sm">
                <div class="opacity-75 small">SẢN PHẨM ĐÃ GIAO</div>
                <h2 class="fw-bold mb-0"><?= $total_qty ?> máy</h2>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm p-4 rounded-4 mb-4">
        <h5 class="fw-bold text-secondary mb-4">BIỂU ĐỒ DOANH THU THEO NGÀY</h5>
        <canvas id="statChart" style="max-height: 350px;"></canvas>
    </div>

    <div class="d-flex justify-content-center gap-3 my-4">
    <a href="admin_statistics_detail.php?from_date=<?= $from_date ?>&to_date=<?= $to_date ?>" 
       class="btn btn-info fw-bold px-5 py-2 rounded-pill shadow-sm text-white text-uppercase">
       <i class="bi bi-table me-2"></i>Bảng chi tiết
    </a>
    
    <button onclick="window.print()" class="btn btn-secondary fw-bold px-5 py-2 rounded-pill shadow-sm text-uppercase">
       <i class="bi bi-printer me-2"></i>Xuất báo cáo
    </button>
</div>
</div>

<script>
const ctx = document.getElementById('statChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Doanh thu (VNĐ)',
            data: <?= json_encode($revenues) ?>,
            backgroundColor: 'rgba(13, 110, 253, 0.7)',
            borderColor: 'rgb(13, 110, 253)',
            borderWidth: 1,
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { 
                beginAtZero: true,
                ticks: { callback: (val) => val.toLocaleString() + ' đ' }
            }
        }
    }
});
</script>

</body>
</html>