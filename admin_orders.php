<?php
session_start();

// --- 1. KIỂM TRA QUYỀN TRUY CẬP
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once "database.php";
$db = new Database();

// --- 2. XỬ LÝ CẬP NHẬT TRẠNG THÁI (AJAX) ---
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];

    $sql = "UPDATE orders SET status = ? WHERE order_id = ?";
    $db->insert($sql, 'si', [$new_status, $order_id]);
    echo "success";
    exit;
}

// --- 3. CẤU HÌNH PHÂN TRANG ---
$limit = 5; // Số đơn hàng mỗi trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Tính tổng số trang
$total_result = $db->select("SELECT COUNT(*) as total FROM orders");
$total_orders = $total_result[0]['total'];
$total_pages = ceil($total_orders / $limit);

// Lấy danh sách đơn hàng theo phân trang
$orders = $db->select("SELECT * FROM orders ORDER BY order_id DESC LIMIT $limit OFFSET $offset");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý đơn hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #fdfae7; }
        .table-card { background: white; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .status-select { border-radius: 20px; font-size: 0.85rem; padding: 0.375rem 1rem; cursor: pointer; font-weight: bold; width: 170px; }
        
        /* Trạng thái màu sắc */
        .status-waiting { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .status-confirmed { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .status-shipped { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status-return-pending { background-color: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
        .status-refunded { background-color: #d1cfeb; color: #512da8; border: 1px solid #b39ddb; }

        /* Phân trang CSS */
        .pagination .page-link { color: #333; border: none; margin: 0 3px; border-radius: 8px !important; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .pagination .page-item.active .page-link { background-color: #0d6efd; color: white; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-primary"></i>QUẢN LÝ ĐƠN HÀNG</h2>
        <div class="d-flex gap-2">
            <a href="admin_dashboard.php" class="btn btn-dark rounded-pill px-4 shadow-sm"><i class="bi bi-speedometer2 me-1"></i> Trang Admin</a>
            <a href="admin_statistics.php" class="btn btn-primary rounded-pill shadow-sm"><i class="bi bi-graph-up me-1"></i> Thống kê</a>
        </div>
    </div>

    <div class="table-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light text-center">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái duyệt</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $row): ?>
                    <tr id="row-<?= $row['order_id'] ?>">
                        <td class="text-center fw-bold">#<?= $row['order_id'] ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars(mb_strimwidth($row['customer_name'] ?? 'Khách lẻ', 0, 20, "...")) ?></div>
                            <div class="small text-muted"><?= $row['phone'] ?? '' ?></div>
                        </td>
                        <td class="text-center"><?= date('d/m/Y', strtotime($row['order_date'])) ?></td>
                        <td class="text-center fw-bold text-primary"><?= number_format($row['total_amount'], 0, ',', '.') ?>đ</td>
                        <td class="text-center">
                            <select class="form-select form-select-sm status-change status-select mx-auto 
                                <?= ($row['status'] == 'Chờ xử lý') ? 'status-waiting' : '' ?>
                                <?= ($row['status'] == 'Đã xác nhận') ? 'status-confirmed' : '' ?>
                                <?= ($row['status'] == 'Đã giao') ? 'status-shipped' : '' ?>
                                <?= ($row['status'] == 'Đã hủy') ? 'status-cancelled' : '' ?>
                                <?= ($row['status'] == 'Yêu cầu trả hàng') ? 'status-return-pending' : '' ?>
                                <?= ($row['status'] == 'Đã hoàn tiền') ? 'status-refunded' : '' ?>"
                                data-id="<?= $row['order_id'] ?>">
                                <option value="Chờ xử lý" <?= $row['status'] == 'Chờ xử lý' ? 'selected' : '' ?>>🕒 Chờ xử lý</option>
                                <option value="Đã xác nhận" <?= $row['status'] == 'Đã xác nhận' ? 'selected' : '' ?>>✅ Đã xác nhận</option>
                                <option value="Đã giao" <?= $row['status'] == 'Đã giao' ? 'selected' : '' ?>>🚚 Đã giao</option>
                                <option value="Đã hủy" <?= $row['status'] == 'Đã hủy' ? 'selected' : '' ?>>❌ Đã hủy</option>
                                <option value="Yêu cầu trả hàng" <?= $row['status'] == 'Yêu cầu trả hàng' ? 'selected' : '' ?>>📩 Yêu cầu trả</option>
                                <option value="Đã hoàn tiền" <?= $row['status'] == 'Đã hoàn tiền' ? 'selected' : '' ?>>💰 Đã hoàn tiền</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <a href="order_details.php?id=<?= $row['order_id'] ?>" class="btn btn-sm btn-outline-info rounded-pill">
                                <i class="bi bi-eye"></i> Chi tiết
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- HIỂN THỊ PHÂN TRANG -->
        <?php if ($total_pages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>">Trước</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>">Sau</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('.status-change').forEach(select => {
    select.addEventListener('change', function() {
        const orderId = this.getAttribute('data-id');
        const newStatus = this.value;
        const el = this;

        el.style.opacity = '0.5';
        const formData = new FormData();
        formData.append('update_status', 'true');
        formData.append('order_id', orderId);
        formData.append('status', newStatus);

        fetch('admin_orders.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(data => {
            if (data.trim() === "success") {
                el.classList.remove('status-waiting', 'status-confirmed', 'status-shipped', 'status-cancelled', 'status-return-pending', 'status-refunded');
                if (newStatus === 'Chờ xử lý') el.classList.add('status-waiting');
                else if (newStatus === 'Đã xác nhận') el.classList.add('status-confirmed');
                else if (newStatus === 'Đã giao') el.classList.add('status-shipped');
                else if (newStatus === 'Đã hủy') el.classList.add('status-cancelled');
                else if (newStatus === 'Yêu cầu trả hàng') el.classList.add('status-return-pending');
                else if (newStatus === 'Đã hoàn tiền') el.classList.add('status-refunded');
                el.style.opacity = '1';
            }
        });
    });
});
</script>
</body>
</html>