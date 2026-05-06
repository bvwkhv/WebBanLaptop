<?php
session_start();
require_once "database.php";
$db = new Database();

$current_user = $_SESSION['username'] ?? ''; 
if (!$current_user) {
    echo "<script>alert('Vui lòng đăng nhập!'); window.location.href='login.php';</script>";
    exit();
}
$current_user_id = $_SESSION['user_id'];

// --- PHẦN LOGIC PHÂN TRANG ---
$limit = 5; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// 1. Đếm tổng số đơn hàng của user này
$sql_count = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
$total_result = $db->select($sql_count, 'i', [$current_user_id]);
$total_orders = $total_result[0]['total'] ?? 0;
$total_pages = ceil($total_orders / $limit);

// 2. Lấy dữ liệu (Sắp xếp đơn mới nhất lên đầu)
$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT ? OFFSET ?";
$orders = $db->select($sql, 'iii', [$current_user_id, $limit, $offset]);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lịch sử đơn hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #fff4c5; }
        .badge-return { background-color: #6f42c1; } 
        .pagination .page-link { color: #198754; }
        .pagination .active .page-link { background-color: #198754; border-color: #198754; color: white; }
    </style>
</head>
<body>
<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="bi bi-clock-history me-2"></i>LỊCH SỬ ĐẶT HÀNG</h2>
        <a href="index.php" class="btn btn-outline-success rounded-pill px-4 shadow-sm">
            <i class="bi bi-house-door me-1"></i> <b>Tiếp tục mua sắm</b>
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $row): ?>
                        <tr>
                            <td>#<?= $row['order_id'] ?></td>
                            <!-- FIX: Hiển thị đầy đủ ngày/tháng/năm Giờ:Phút -->
                            <td><?= date('d/m/Y H:i', strtotime($row['order_date'])) ?></td>
                            <td class="fw-bold text-danger"><?= number_format($row['total_amount'], 0, ',', '.') ?>đ</td>
                            <td>
                                <?php 
                                    $class = "bg-warning text-dark"; 
                                    if($row['status'] == 'Đã giao') $class = "bg-success";
                                    if($row['status'] == 'Đã hủy') $class = "bg-danger";
                                    if($row['status'] == 'Yêu cầu trả hàng') $class = "bg-info text-dark";
                                    if($row['status'] == 'Đã hoàn tiền') $class = "badge-return text-white";
                                ?>
                                <span class="badge <?= $class ?>"><?= $row['status'] ?></span>
                            </td>
                            <td>
                                <a href="order_details.php?id=<?= $row['order_id'] ?>" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4">Chưa có đơn hàng nào khớp với tài khoản của bạn.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Phân trang -->
            <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>