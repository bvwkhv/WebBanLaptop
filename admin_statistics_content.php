<?php
require_once "database.php";
$db = new Database();

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

// SQL Summary
$sql_summary = "SELECT SUM(total_amount) as total_revenue, COUNT(order_id) as total_orders 
                FROM orders WHERE order_date BETWEEN ? AND ? AND status IN ('Đã xác nhận', 'Đã giao')";
$summary_res = $db->select($sql_summary, 'ss', [$from_date, $to_date]);
$summary = $summary_res[0] ?? ['total_revenue' => 0, 'total_orders' => 0];

// SQL Sản phẩm
$sql_prods = "SELECT SUM(od.quantity) as total_qty FROM order_details od
              JOIN orders o ON od.order_id = o.order_id
              WHERE o.order_date BETWEEN ? AND ? AND o.status IN ('Đã xác nhận', 'Đã giao')";
$prods_res = $db->select($sql_prods, 'ss', [$from_date, $to_date]);
$total_qty = $prods_res[0]['total_qty'] ?? 0;

// SQL Chart
$sql_chart = "SELECT order_date, SUM(total_amount) as daily_revenue FROM orders 
              WHERE order_date BETWEEN ? AND ? AND status IN ('Đã xác nhận', 'Đã giao')
              GROUP BY order_date ORDER BY order_date ASC";
$chart_data = $db->select($sql_chart, 'ss', [$from_date, $to_date]);

$labels = []; $revenues = [];
foreach ($chart_data as $row) {
    $labels[] = date('d/m', strtotime($row['order_date']));
    $revenues[] = (float)$row['daily_revenue'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-line me-2 text-primary"></i>Thống kê doanh thu</h4>
    <div>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary rounded-pill me-2">
            <i class="fa-solid fa-print me-1"></i> In
        </button>
        <!-- Nút Bảng chi tiết của bạn đây -->
        <a href="admin_statistics_detail.php?from_date=<?= $from_date ?>&to_date=<?= $to_date ?>" class="btn btn-sm btn-info text-white rounded-pill px-3 shadow-sm">
            <i class="fa-solid fa-table me-1"></i> Bảng chi tiết
        </a>
    </div>
</div>

<!-- Bộ lọc -->
<div class="card border-0 shadow-sm p-3 rounded-4 mb-4">
    <form class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="small fw-bold text-muted">TỪ NGÀY</label>
            <input type="date" id="from_date" class="form-control form-control-sm" value="<?= $from_date ?>">
        </div>
        <div class="col-md-4">
            <label class="small fw-bold text-muted">ĐẾN NGÀY</label>
            <input type="date" id="to_date" class="form-control form-control-sm" value="<?= $to_date ?>">
        </div>
        <div class="col-md-4">
            <button type="button" onclick="filterStats()" class="btn btn-primary btn-sm w-100 fw-bold">LỌC DỮ LIỆU</button>
        </div>
    </form>
</div>

<!-- Cards -->
<div class="row g-3 mb-4 text-center">
    <div class="col-md-4">
        <div class="p-3 bg-primary text-white rounded-4 shadow-sm">
            <div class="opacity-75 small">Doanh thu</div>
            <h4 class="fw-bold mb-0"><?= number_format($summary['total_revenue'] ?? 0, 0, ',', '.') ?>đ</h4>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 bg-info text-white rounded-4 shadow-sm">
            <div class="opacity-75 small">Đơn hàng thành công</div>
            <h4 class="fw-bold mb-0"><?= $summary['total_orders'] ?> đơn</h4>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 bg-success text-white rounded-4 shadow-sm">
            <div class="opacity-75 small">Đã bán</div>
            <h4 class="fw-bold mb-0"><?= $total_qty ?> máy</h4>
        </div>
    </div>
</div>

<!-- Biểu đồ -->
<div class="card border-0 shadow-sm p-4 rounded-4">
    <h6 class="fw-bold text-secondary mb-3 text-uppercase small">Biểu đồ doanh thu ngày</h6>
    <div style="height: 300px;"><canvas id="statChart"></canvas></div>
</div>

<script>
(function() {
    // Chờ một chút để đảm bảo HTML đã vào DOM
    setTimeout(() => {
        const event = new CustomEvent('renderChart', {
            detail: {
                labels: <?= json_encode($labels) ?>,
                data: <?= json_encode($revenues) ?>
            }
        });
        document.dispatchEvent(event);
    }, 150);
})();

function filterStats() {
    const from = document.getElementById('from_date').value;
    const to = document.getElementById('to_date').value;
    loadContent(`admin_statistics_content.php?from_date=${from}&to_date=${to}`, 
                document.querySelector('.sidebar-link.active'));
}
</script>