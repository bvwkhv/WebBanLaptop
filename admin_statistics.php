<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once "database.php";
$db = new Database();

// --- 2. LẤY NGÀY BỘ LỌC (MẶC ĐỊNH TỪ ĐẦU THÁNG ĐẾN NAY) ---
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

// --- 3. SQL: TỔNG DOANH THU & ĐƠN HÀNG (SỬA LỖI DATE ĐỂ KHỚP DATETIME TRONG DB) ---
$sql_summary = "SELECT 
                    SUM(total_amount) as total_revenue, 
                    COUNT(order_id) as total_orders 
                FROM orders 
                WHERE DATE(order_date) BETWEEN ? AND ? 
                AND status IN ('Đã xác nhận', 'Đã giao')";
$summary_res = $db->select($sql_summary, 'ss', [$from_date, $to_date]);
$summary = $summary_res[0] ?? ['total_revenue' => 0, 'total_orders' => 0];

// --- 4. SQL: TỔNG SẢN PHẨM ĐÃ BÁN ---
$sql_prods = "SELECT SUM(od.quantity) as total_qty 
              FROM order_details od
              JOIN orders o ON od.order_id = o.order_id
              WHERE DATE(o.order_date) BETWEEN ? AND ? 
              AND o.status IN ('Đã xác nhận', 'Đã giao')";
$prods_res = $db->select($sql_prods, 'ss', [$from_date, $to_date]);
$total_qty = $prods_res[0]['total_qty'] ?? 0;

// --- 5. SQL: DỮ LIỆU BIỂU ĐỒ (GROUP BY DATE ĐỂ GOM NHÓM CHÍNH XÁC) ---
$sql_chart = "SELECT DATE(order_date) as order_day, SUM(total_amount) as daily_revenue 
              FROM orders 
              WHERE DATE(order_date) BETWEEN ? AND ? 
              AND status IN ('Đã xác nhận', 'Đã giao')
              GROUP BY DATE(order_date) 
              ORDER BY order_day ASC";
$chart_data = $db->select($sql_chart, 'ss', [$from_date, $to_date]);

$labels = [];
$revenues = [];
foreach ($chart_data as $row) {
    $labels[] = date('d/m', strtotime($row['order_day']));
    $revenues[] = (float)$row['daily_revenue'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thống kê báo cáo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #fdfae7; }
        .stat-card { border: none; border-radius: 12px; color: white; padding: 20px; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .filter-box { background: white; border-radius: 12px; padding: 20px; margin-bottom: 25px; }
        @media print { .btn, .filter-box { display: none; } }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">
            <i class="bi bi-pie-chart-fill me-2 text-primary"></i>THỐNG KÊ DOANH THU
        </h3>
        <div class="d-flex gap-2">
            <a href="admin_dashboard.php" class="btn btn-dark rounded-pill px-4 shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> Trang Admin
            </a>
            <a href="admin_orders.php" class="btn btn-outline-primary rounded-pill px-4 shadow-sm">Quản lý đơn</a>
        </div>
    </div>

    <!-- BỘ LỌC -->
    <div class="filter-box shadow-sm">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">TỪ NGÀY</label>
                <input type="date" name="from_date" class="form-control rounded-pill" value="<?= $from_date ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">ĐẾN NGÀY</label>
                <input type="date" name="to_date" class="form-control rounded-pill" value="<?= $to_date ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-warning w-100 rounded-pill fw-bold text-white shadow-sm">
                    <i class="bi bi-funnel-fill me-1"></i> LỌC DỮ LIỆU
                </button>
            </div>
        </form>
    </div>

    <!-- CARD THỐNG KÊ -->
    <div class="row g-4 mb-4 text-center">
        <div class="col-md-4">
            <div class="stat-card bg-primary shadow-sm">
                <div class="opacity-75 small">DOANH THU THỰC TẾ</div>
                <h2 class="fw-bold mb-0"><?= number_format($summary['total_revenue'] ?? 0, 0, ',', '.') ?>đ</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-info shadow-sm">
                <div class="opacity-75 small text-white">ĐƠN HÀNG THÀNH CÔNG</div>
                <h2 class="fw-bold mb-0 text-white"><?= $summary['total_orders'] ?> đơn</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-success shadow-sm">
                <div class="opacity-75 small">SẢN PHẨM ĐÃ GIAO</div>
                <h2 class="fw-bold mb-0"><?= number_format($total_qty, 0, ',', '.') ?> máy</h2>
            </div>
        </div>
    </div>

    <!-- BIỂU ĐỒ -->
    <div class="card border-0 shadow-sm p-4 rounded-4 mb-4">
        <h5 class="fw-bold text-secondary mb-4"><i class="bi bi-bar-chart-line me-2"></i>BIỂU ĐỒ DOANH THU THEO NGÀY</h5>
        <div style="height: 350px;">
            <canvas id="statChart"></canvas>
        </div>
    </div>

    <!-- NÚT THAO TÁC -->
    <div class="d-flex justify-content-center gap-3 my-4">
        <a href="admin_statistics_detail.php?from_date=<?= $from_date ?>&to_date=<?= $to_date ?>" 
           class="btn btn-info fw-bold px-5 py-2 rounded-pill shadow-sm text-white">
           <i class="bi bi-table me-2"></i>XEM CHI TIẾT
        </a>
        <button onclick="window.print()" class="btn btn-secondary fw-bold px-5 py-2 rounded-pill shadow-sm">
           <i class="bi bi-printer me-2"></i>XUẤT BÁO CÁO
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
            borderRadius: 8
        }]
    },
    options: {
        maintainAspectRatio: false,
        responsive: true,
        plugins: { 
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Doanh thu: ' + context.raw.toLocaleString() + ' đ';
                    }
                }
            }
        },
        scales: {
            y: { 
                beginAtZero: true,
                ticks: { 
                    callback: (val) => val.toLocaleString() + ' đ' 
                }
            }
        }
    }
});
</script>

</body>
</html>