<?php
session_start();
require_once "database.php";
$db = new Database();

// --- 1. XỬ LÝ CẬP NHẬT TRẠNG THÁI ---
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $sql = "UPDATE orders SET status = ? WHERE order_id = ?";
    $db->insert($sql, 'si', [$new_status, $order_id]);
    echo "success";
    exit;
}

// --- 2. LẤY DANH SÁCH TẤT CẢ ĐƠN HÀNG ---
// Đảm bảo lấy thêm cột return_reason để hiển thị lý do trả hàng
$orders = $db->select("SELECT * FROM orders ORDER BY order_id DESC");
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
        .status-select { border-radius: 20px; font-size: 0.85rem; padding: 0.375rem 1rem; cursor: pointer; font-weight: bold; }
        
        /* Màu sắc trạng thái */
        .status-waiting { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .status-confirmed { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .status-shipped { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        /* Màu mới cho trả hàng/hoàn tiền */
        .status-return-pending { background-color: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
        .status-refunded { background-color: #d1cfeb; color: #512da8; border: 1px solid #b39ddb; }
        
        .reason-box { max-width: 200px; margin: 8px auto 0; font-size: 0.8rem; line-height: 1.2; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">
            <i class="bi bi-box-seam me-2 text-primary"></i>QUẢN LÝ ĐƠN HÀNG
        </h2>
        <div class="d-flex gap-2">
            <a href="admin_dashboard.php" class="btn btn-dark rounded-pill px-4 shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> Trang Admin
            </a>
            <a href="admin_statistics.php" class="btn btn-primary rounded-pill shadow-sm">
                <i class="bi bi-graph-up me-1"></i> Xem Thống kê
            </a>
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
                    <?php foreach($orders as $row): ?>
                    <tr id="row-<?= $row['order_id'] ?>">
                        <td class="text-center fw-bold">#<?= $row['order_id'] ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($row['customer_name'] ?? 'Khách lẻ') ?></div>
                            <div class="small text-muted"><?= $row['phone'] ?? '' ?></div>
                        </td>
                        <td class="text-center"><?= date('d/m/Y', strtotime($row['order_date'])) ?></td>
                        <td class="text-center fw-bold text-primary"><?= number_format($row['total_amount'], 0, ',', '.') ?>đ</td>
                        <td class="text-center">
                            <select class="form-select form-select-sm status-change status-select mx-auto
                                <?php 
                                    if($row['status'] == 'Chờ xử lý') echo 'status-waiting';
                                    elseif($row['status'] == 'Đã xác nhận') echo 'status-confirmed';
                                    elseif($row['status'] == 'Đã giao') echo 'status-shipped';
                                    elseif($row['status'] == 'Đã hủy') echo 'status-cancelled';
                                    elseif($row['status'] == 'Yêu cầu trả hàng') echo 'status-return-pending';
                                    elseif($row['status'] == 'Đã hoàn tiền') echo 'status-refunded';
                                ?>" 
                                data-id="<?= $row['order_id'] ?>" style="width: 170px;">
                                <option value="Chờ xử lý" <?= $row['status'] == 'Chờ xử lý' ? 'selected' : '' ?>>🕒 Chờ xử lý</option>
                                <option value="Đã xác nhận" <?= $row['status'] == 'Đã xác nhận' ? 'selected' : '' ?>>✅ Đã xác nhận</option>
                                <option value="Đã giao" <?= $row['status'] == 'Đã giao' ? 'selected' : '' ?>>🚚 Đã giao</option>
                                <option value="Đã hủy" <?= $row['status'] == 'Đã hủy' ? 'selected' : '' ?>>❌ Đã hủy</option>
                                <option value="Yêu cầu trả hàng" <?= $row['status'] == 'Yêu cầu trả hàng' ? 'selected' : '' ?>>📩 Yêu cầu trả</option>
                                <option value="Đã hoàn tiền" <?= $row['status'] == 'Đã hoàn tiền' ? 'selected' : '' ?>>💰 Đã hoàn tiền</option>
                            </select>

                            <?php if (!empty($row['return_reason'])): ?>
                                <div class="reason-box p-2 border border-danger rounded bg-white text-start">
                                    <strong class="text-danger small">Lý do:</strong>
                                    <div class="text-dark italic small"><?= htmlspecialchars($row['return_reason']) ?></div>
                                </div>
                            <?php endif; ?>
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
    </div>
</div>

<script>
document.querySelectorAll('.status-change').forEach(select => {
    select.addEventListener('change', function() {
        const orderId = this.getAttribute('data-id');
        const newStatus = this.value;
        const currentSelect = this;

        currentSelect.style.opacity = '0.5';

        const formData = new FormData();
        formData.append('update_status', 'true');
        formData.append('order_id', orderId);
        formData.append('status', newStatus);

        fetch('admin_orders.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(data => {
            if(data.trim() === "success") {
                // Cập nhật Class CSS dựa trên trạng thái mới
                currentSelect.classList.remove('status-waiting', 'status-confirmed', 'status-shipped', 'status-cancelled', 'status-return-pending', 'status-refunded');
                
                if(newStatus === 'Chờ xử lý') currentSelect.classList.add('status-waiting');
                else if(newStatus === 'Đã xác nhận') currentSelect.classList.add('status-confirmed');
                else if(newStatus === 'Đã giao') currentSelect.classList.add('status-shipped');
                else if(newStatus === 'Đã hủy') currentSelect.classList.add('status-cancelled');
                else if(newStatus === 'Yêu cầu trả hàng') currentSelect.classList.add('status-return-pending');
                else if(newStatus === 'Đã hoàn tiền') currentSelect.classList.add('status-refunded');

                currentSelect.style.opacity = '1';
                console.log("Updated order #" + orderId + " to " + newStatus);
            }
        });
    });
});
</script>

</body>
</html>