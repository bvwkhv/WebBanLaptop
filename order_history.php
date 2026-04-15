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

$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$orders = $db->select($sql, 'i', [$current_user_id]);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lịch sử đơn hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body{ background-color: #fff4c5; }
        .badge-return { background-color: #6f42c1; } /* Màu tím cho trạng thái trả hàng */
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">
            <i class="bi bi-clock-history me-2"></i>LỊCH SỬ ĐẶT HÀNG
        </h2>
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
                            <td><?= $row['order_date'] ?></td>
                            <td class="fw-bold"><?= number_format($row['total_amount'], 0, ',', '.') ?>đ</td>
                            <td>
                                <?php 
                                    // Logic hiển thị màu sắc badge theo trạng thái
                                    $class = "bg-warning text-dark"; // Mặc định Chờ xử lý
                                    if($row['status'] == 'Đã giao') $class = "bg-success";
                                    if($row['status'] == 'Đã hủy') $class = "bg-danger";
                                    if($row['status'] == 'Yêu cầu trả hàng') $class = "bg-info text-dark";
                                    if($row['status'] == 'Đã hoàn tiền') $class = "badge-return text-white";
                                ?>
                                <span class="badge <?= $class ?>">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="order_details.php?id=<?= $row['order_id'] ?>" class="btn btn-sm btn-outline-primary">
                                        Chi tiết
                                    </a>

                                    <?php if ($row['status'] == 'Đã giao'): ?>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#returnModal<?= $row['order_id'] ?>">
                                            Trả hàng
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <div class="modal fade" id="returnModal<?= $row['order_id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form action="process_return.php" method="POST" class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Yêu cầu trả hàng đơn #<?= $row['order_id'] ?></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Lý do bạn muốn trả hàng/hoàn tiền:</label>
                                                    <textarea name="return_reason" class="form-control" rows="3" required 
                                                              placeholder="Ví dụ: Laptop bị móp cạnh, giao sai màu..."></textarea>
                                                </div>
                                                <p class="small text-muted italic">* Lưu ý: Yêu cầu của bạn sẽ được Admin xem xét và phản hồi sớm nhất.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                <button type="submit" class="btn btn-danger">Gửi yêu cầu</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4">Bạn chưa có đơn hàng nào.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>