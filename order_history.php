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

// --- XỬ LÝ YÊU CẦU TRẢ HÀNG ---
if (isset($_POST['confirm_return'])) {
    $order_id = (int)$_POST['order_id'];
    $reason = htmlspecialchars($_POST['return_reason']);

    // 1. Cập nhật trạng thái đơn hàng
    $sql_update = "UPDATE orders SET status = 'Yêu cầu trả hàng' WHERE order_id = ? AND user_id = ?";
    $db->execute($sql_update, 'ii', [$order_id, $current_user_id]);

    // 2. Ghi vào lịch sử vận chuyển kèm LÝ DO
    $description = "Khách hàng yêu cầu trả hàng. Lý do: " . $reason;
    $sql_history = "INSERT INTO order_history (order_id, status_name, description, location, created_at) 
                    VALUES (?, 'Yêu cầu trả hàng', ?, 'Hệ thống khách hàng', NOW())";
    $db->execute($sql_history, 'is', [$order_id, $description]);

    echo "<script>alert('Đã gửi yêu cầu trả hàng với lý do: $reason'); window.location.href='order_history.php';</script>";
    exit();
}

// --- LOGIC PHÂN TRANG (giữ nguyên) ---
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;
$sql_count = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
$total_result = $db->select($sql_count, 'i', [$current_user_id]);
$total_orders = $total_result[0]['total'] ?? 0;
$total_pages = ceil($total_orders / $limit);
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
        body {
            background-color: #fff4c5;
        }

        .badge-return {
            background-color: #6f42c1;
        }

        .pagination .page-link {
            color: #198754;
        }

        .pagination .active .page-link {
            background-color: #198754;
            border-color: #198754;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container mt-5 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold"><i class="bi bi-clock-history me-2"></i>LỊCH SỬ ĐẶT HÀNG</h2>
            <a href="index.php" class="btn btn-outline-success rounded-pill px-4 shadow-sm">Tiếp tục mua sắm</a>
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
                                    <td><?= date('d/m/Y H:i', strtotime($row['order_date'])) ?></td>
                                    <td class="fw-bold text-danger"><?= number_format($row['total_amount'], 0, ',', '.') ?>đ</td>
                                    <td>
                                        <?php
                                        $class = "bg-warning text-dark";
                                        if ($row['status'] == 'Đã giao') $class = "bg-success";
                                        if ($row['status'] == 'Đã hủy') $class = "bg-danger";
                                        if ($row['status'] == 'Yêu cầu trả hàng') $class = "bg-info text-dark";
                                        if ($row['status'] == 'Đã hoàn tiền') $class = "badge-return text-white";
                                        ?>
                                        <span class="badge <?= $class ?>"><?= $row['status'] ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="order_details.php?id=<?= $row['order_id'] ?>" class="btn btn-sm btn-outline-primary">Chi tiết</a>

                                            <?php if ($row['status'] == 'Đã giao'): ?>
                                                <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="openReturnModal(<?= $row['order_id'] ?>)">
                                                    Trả hàng
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">Chưa có đơn hàng nào.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="returnModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Lý do trả hàng #<span id="display_order_id"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="input_order_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Vui lòng cho biết lý do bạn muốn trả hàng:</label>
                        <textarea name="return_reason" class="form-control" rows="3" placeholder="Ví dụ: Sản phẩm bị vỡ, không giống mô tả..." required></textarea>
                    </div>
                    <p class="text-muted small">* Yêu cầu của bạn sẽ được Admin xem xét và thực hiện hoàn tiền.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="confirm_return" class="btn btn-danger">Xác nhận gửi yêu cầu</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openReturnModal(orderId) {
            document.getElementById('display_order_id').innerText = orderId;
            document.getElementById('input_order_id').value = orderId;
            var myModal = new bootstrap.Modal(document.getElementById('returnModal'));
            myModal.show();
        }
    </script>
</body>

</html>