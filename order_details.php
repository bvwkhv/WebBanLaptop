<?php
session_start();
require_once "database.php";
$db = new Database();

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. Lấy thông tin đơn hàng
$sql_order = "SELECT o.*, pr.name as promo_name 
              FROM orders o 
              LEFT JOIN promotions pr ON o.promotion_id = pr.promotion_id 
              WHERE o.order_id = ?";
$order_result = $db->select($sql_order, 'i', [$order_id]);

if (empty($order_result)) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Không tìm thấy đơn hàng #$order_id!</div></div>";
    exit();
}
$order = $order_result[0];

// 2. Lấy danh sách sản phẩm
$sql_items = "SELECT od.*, p.product_name 
              FROM order_details od 
              JOIN products p ON od.product_id = p.product_id
              WHERE od.order_id = ?";
$items = $db->select($sql_items, 'i', [$order_id]);

// 3. Lấy lịch sử vận chuyển cho Timeline
$sql_history = "SELECT * FROM order_history WHERE order_id = ? ORDER BY created_at DESC";
$history = $db->select($sql_history, 'i', [$order_id]);

// 4. Tính toán tiền hàng
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += (float)$item['price'] * (int)$item['quantity'];
}

if ($subtotal <= 0) {
    $subtotal = (float)$order['total_amount'];
    $discount = 0;
} else {
    $discount = round($subtotal) - round((float)$order['total_amount']);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?= $order_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: none; margin-bottom: 2rem; }
        .card-header { background: linear-gradient(45deg, #007bff, #0056b3); border-radius: 15px 15px 0 0 !important; }
        .status-badge { padding: 5px 15px; border-radius: 20px; font-size: 0.85rem; }

        /* CSS CHO TIMELINE */
        .timeline { position: relative; padding-left: 30px; list-style: none; margin-top: 20px; }
        .timeline::before {
            content: ""; position: absolute; left: 7px; top: 5px; bottom: 5px;
            width: 2px; background: #e9ecef;
        }
        .timeline-item { position: relative; padding-bottom: 1.5rem; }
        .timeline-item::after {
            content: ""; position: absolute; left: -27px; top: 5px;
            width: 12px; height: 12px; border-radius: 50%; background: #adb5bd;
        }
        .timeline-item.active::after {
            background: #198754; box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.2);
        }
        .timeline-date { font-size: 0.8rem; color: #6c757d; font-weight: bold; }
        .timeline-content { font-size: 0.95rem; font-weight: 500; color: #333; }
        
        /* Ẩn các item lịch sử cũ */
        .extra-item { display: none; }
        #btn-toggle-timeline { cursor: pointer; color: #007bff; text-decoration: none; font-size: 0.85rem; font-weight: 600; display: inline-block; margin-top: 10px; }

        @media print { .btn, .timeline-section, #btn-toggle-timeline { display: none; } }
    </style>
</head>
<body>
    <div class="container mt-3">
        <a href="index.php" class="btn btn-outline-secondary rounded-pill shadow-sm">Home</a>
    </div>

    <div class="container mt-5 mb-5">
        <div class="card">
            <div class="card-header text-white p-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Mã đơn hàng: #<?= $order_id ?></h5>
                <span class="badge bg-light text-primary status-badge"><?= htmlspecialchars($order['status']) ?></span>
            </div>
            
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-md-6 border-end">
                        <h6 class="text-primary fw-bold mb-3">THÔNG TIN GIAO HÀNG</h6>
                        <p class="mb-1"><strong>Họ tên:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                        <p class="mb-1"><strong>Điện thoại:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                        <p class="mb-1 text-muted"><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['address']) ?></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h6 class="text-primary fw-bold mb-3">THANH TOÁN</h6>
                        <p class="mb-1">Phương thức: <span class="badge bg-secondary"><?= htmlspecialchars($order['payment_method']) ?></span></p>
                        <p class="mb-1 text-muted">Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></p>
                    </div>
                </div>

                <div class="timeline-section mt-4 mb-5 p-3 bg-light rounded shadow-sm">
                    <h6 class="text-dark fw-bold mb-3"><i class="bi bi-truck me-2"></i>TRẠNG THÁI VẬN CHUYỂN</h6>
                    <?php if (!empty($history)): ?>
                        <ul class="timeline mb-0" id="timeline-list">
                            <?php foreach ($history as $index => $step): ?>
                                <li class="timeline-item <?= $index === 0 ? 'active' : '' ?> <?= $index > 1 ? 'extra-item' : '' ?>">
                                    <div class="timeline-date">
                                        <?= date('H:i d/m/Y', strtotime($step['created_at'])) ?>
                                        <?php if(!empty($step['location'])) echo " — " . htmlspecialchars($step['location']); ?>
                                    </div>
                                    <div class="timeline-content">
                                        <?= htmlspecialchars($step['status_name']) ?>
                                    </div>
                                    <?php if(!empty($step['description'])): ?>
                                        <div class="text-muted small"><?= htmlspecialchars($step['description']) ?></div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <?php if (count($history) > 2): ?>
                            <a id="btn-toggle-timeline" onclick="toggleTimeline()">
                                <i class="bi bi-chevron-down"></i> Xem thêm trạng thái
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted small mb-0 ms-2 italic">Đang chờ cập nhật thông tin vận chuyển...</p>
                    <?php endif; ?>
                </div>

                <h6 class="text-primary fw-bold mb-3">DANH SÁCH SẢN PHẨM</h6>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sản phẩm</th>
                                <th class="text-center">Số lượng</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="fw-medium"><?= htmlspecialchars($item['product_name']) ?></td>
                                <td class="text-center">x<?= $item['quantity'] ?></td>
                                <td class="text-end"><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
                                <td class="text-end fw-bold"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="border-top">
                            <tr>
                                <td colspan="3" class="text-end text-muted pt-3">Tạm tính:</td>
                                <td class="text-end pt-3"><?= number_format($subtotal, 0, ',', '.') ?>đ</td>
                            </tr>
                            <?php if ($discount > 0): ?>
                            <tr>
                                <td colspan="3" class="text-end text-success">Giảm giá:</td>
                                <td class="text-end text-success fw-bold">-<?= number_format($discount, 0, ',', '.') ?>đ</td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th colspan="3" class="text-end fs-5 pt-3">TỔNG THANH TOÁN:</th>
                                <th class="text-end text-danger fs-3 pt-2"><?= number_format($order['total_amount'], 0, ',', '.') ?>đ</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <a href="order_history.php" class="btn btn-secondary px-4 shadow-sm">Lịch sử đơn hàng</a>
                    <button onclick="window.print()" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-printer me-1"></i> Xuất hóa đơn</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleTimeline() {
            const extraItems = document.querySelectorAll('.extra-item');
            const btn = document.getElementById('btn-toggle-timeline');
            
            // Kiểm tra xem đang ẩn hay hiện
            if (extraItems[0].style.display === 'block') {
                extraItems.forEach(item => item.style.display = 'none');
                btn.innerHTML = '<i class="bi bi-chevron-down"></i> Xem thêm trạng thái';
            } else {
                extraItems.forEach(item => item.style.display = 'block');
                btn.innerHTML = '<i class="bi bi-chevron-up"></i> Thu gọn';
            }
        }
    </script>
</body>
</html>