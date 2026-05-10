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
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];

    $current_order = $db->select("SELECT status FROM orders WHERE order_id = ?", "i", [$order_id]);
    $old_status = $current_order[0]['status'];

    $locked_statuses = ['Đã giao', 'Đã hủy', 'Đã hoàn tiền', 'Yêu cầu trả hàng'];
    if (in_array($old_status, $locked_statuses) && !in_array($new_status, ['Đã giao', 'Đã hoàn tiền', 'Đã hủy'])) {
        echo "error_logic";
        exit;
    }

    $sql = "UPDATE orders SET status = ? WHERE order_id = ?";
    $db->execute($sql, 'si', [$new_status, $order_id]);

    $description = "Trạng thái đơn hàng đã được thay đổi thành: " . $new_status;
    $location = "Hệ thống quản lý";

    if ($new_status === 'Đã xác nhận') {
        $description = "Người bán đã xác nhận đơn hàng và đang chuẩn bị sản phẩm.";
    } elseif ($new_status === 'Đang trên đường giao') {
        $description = "Shipper đang mang kiện hàng đến địa chỉ của bạn. Vui lòng chú ý điện thoại.";
        $location = "Bưu cục giao hàng";
    } elseif ($new_status === 'Đã giao') {
        $description = "Đơn hàng đã được giao thành công đến tay khách hàng.";
    } elseif ($new_status === 'Đã hủy') {
        $description = "Đơn hàng đã bị hủy bởi quản trị viên.";
    } elseif ($new_status === 'Đã hoàn tiền') {
        $description = "Tiền đã được hoàn lại cho khách hàng thành công.";
    }

    $sql_history = "INSERT INTO order_history (order_id, status_name, description, location, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
    $db->execute($sql_history, 'isss', [$order_id, $new_status, $description, $location]);

    echo "success";
    exit;
}

// --- 3. CẤU HÌNH PHÂN TRANG ---
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$total_result = $db->select("SELECT COUNT(*) as total FROM orders");
$total_orders = $total_result[0]['total'];
$total_pages = ceil($total_orders / $limit);

$orders = $db->select("SELECT * FROM orders ORDER BY order_id DESC LIMIT $limit OFFSET $offset");

// Hàm lấy lý do trả hàng
function getReturnReason($db, $order_id) {
    $sql = "SELECT description FROM order_history WHERE order_id = ? AND status_name = 'Yêu cầu trả hàng' ORDER BY created_at DESC LIMIT 1";
    $result = $db->select($sql, "i", [$order_id]);
    return !empty($result) ? $result[0]['description'] : '';
}
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
        .table-card { background: white; border-radius: 15px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); }
        .status-select { border-radius: 20px; font-size: 0.85rem; padding: 0.375rem 1rem; cursor: pointer; font-weight: bold; width: 185px; }
        .status-waiting { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .status-confirmed { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .status-shipping { background-color: #e3f2fd; color: #0d6efd; border: 1px solid #bbdefb; }
        .status-shipped { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status-return-pending { background-color: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
        .status-refunded { background-color: #d1cfeb; color: #512da8; border: 1px solid #b39ddb; }
        
        /* CSS xử lý lý do trả hàng (Bấm vào được) */
        .reason-col { max-width: 180px; }
        .reason-text { 
            display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; 
            font-size: 0.85rem; color: #dc3545; background: #fff5f5; 
            padding: 5px 10px; border-radius: 6px; border-left: 4px solid #dc3545;
            cursor: pointer; transition: all 0.2s;
        }
        .reason-text:hover {
            background: #fee2e2;
            transform: scale(1.02);
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-primary"></i>QUẢN LÝ ĐƠN HÀNG</h2>
        <div class="d-flex gap-2">
            <a href="admin_dashboard.php" class="btn btn-dark rounded-pill px-4 shadow-sm">Trang Admin</a>
            <a href="admin_statistics.php" class="btn btn-primary rounded-pill shadow-sm">Thống kê</a>
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
                        <th>Lý do trả hàng</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $row): ?>
                        <tr>
                            <td class="text-center fw-bold">#<?= $row['order_id'] ?></td>
                            <td>
                                <div class="fw-bold text-nowrap"><?= htmlspecialchars(mb_strimwidth($row['customer_name'] ?? 'Khách lẻ', 0, 20, "...")) ?></div>
                                <div class="small text-muted"><?= $row['phone'] ?? '' ?></div>
                            </td>
                            <td class="text-center"><?= date('d/m/Y', strtotime($row['order_date'])) ?></td>
                            <td class="text-center fw-bold text-primary"><?= number_format($row['total_amount'], 0, ',', '.') ?>đ</td>
                            <td class="text-center">
                                <select class="form-select form-select-sm status-change status-select mx-auto 
                                    <?= ($row['status'] == 'Chờ xử lý') ? 'status-waiting' : '' ?>
                                    <?= ($row['status'] == 'Đã xác nhận') ? 'status-confirmed' : '' ?>
                                    <?= ($row['status'] == 'Đang trên đường giao') ? 'status-shipping' : '' ?>
                                    <?= ($row['status'] == 'Đã giao') ? 'status-shipped' : '' ?>
                                    <?= ($row['status'] == 'Đã hủy') ? 'status-cancelled' : '' ?>
                                    <?= ($row['status'] == 'Yêu cầu trả hàng') ? 'status-return-pending' : '' ?>
                                    <?= ($row['status'] == 'Đã hoàn tiền') ? 'status-refunded' : '' ?>"
                                    data-id="<?= $row['order_id'] ?>"
                                    <?= ($row['status'] == 'Đã hoàn tiền' || $row['status'] == 'Đã hủy') ? 'disabled' : '' ?>>
                                    
                                    <option value="Chờ xử lý" <?= $row['status'] == 'Chờ xử lý' ? 'selected' : '' ?>>🕒 Chờ xử lý</option>
                                    <option value="Đã xác nhận" <?= $row['status'] == 'Đã xác nhận' ? 'selected' : '' ?>>✅ Đã xác nhận</option>
                                    <option value="Đang trên đường giao" <?= $row['status'] == 'Đang trên đường giao' ? 'selected' : '' ?>>🚚 Đang giao hàng</option>
                                    <option value="Đã giao" <?= $row['status'] == 'Đã giao' ? 'selected' : '' ?>>📦 Đã giao</option>
                                    <option value="Đã hủy" <?= $row['status'] == 'Đã hủy' ? 'selected' : '' ?>>❌ Đã hủy</option>
                                    <option value="Yêu cầu trả hàng" <?= $row['status'] == 'Yêu cầu trả hàng' ? 'selected' : '' ?> disabled>📩 Yêu cầu trả</option>
                                    <option value="Đã hoàn tiền" <?= $row['status'] == 'Đã hoàn tiền' ? 'selected' : '' ?>>💰 Đã hoàn tiền</option>
                                </select>
                            </td>
                            <td class="reason-col">
                                <?php if ($row['status'] == 'Yêu cầu trả hàng'): 
                                    $reason = getReturnReason($db, $row['order_id']); ?>
                                    <div class="reason-text" 
                                         onclick="showReasonModal('<?= $row['order_id'] ?>', `<?= addslashes(htmlspecialchars($reason)) ?>`)">
                                        <i class="bi bi-search me-1"></i> <?= htmlspecialchars($reason) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center text-muted small">---</div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="order_details.php?id=<?= $row['order_id'] ?>" class="btn btn-sm btn-outline-info rounded-pill"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $page - 1 ?>">Trước</a></li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $page + 1 ?>">Sau</a></li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="reasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fs-6"><i class="bi bi-exclamation-triangle me-2"></i>Chi tiết lý do trả hàng</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="fw-bold mb-2">Đơn hàng: <span id="modalOrderId" class="text-danger"></span></p>
                <div class="p-3 bg-light rounded border border-danger-subtle">
                    <p id="modalReasonContent" class="mb-0 text-dark" style="white-space: pre-wrap; line-height: 1.6;"></p>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Hàm bật Modal hiện lý do
function showReasonModal(orderId, reason) {
    document.getElementById('modalOrderId').innerText = '#' + orderId;
    document.getElementById('modalReasonContent').innerText = reason;
    var myModal = new bootstrap.Modal(document.getElementById('reasonModal'));
    myModal.show();
}

// Xử lý AJAX đổi trạng thái
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
            const result = data.trim();
            if (result === "success") {
                location.reload(); 
            } else if (result === "error_logic") {
                alert("Thao tác không hợp lệ!");
                location.reload();
            }
        });
    });
});
</script>
</body>
</html>