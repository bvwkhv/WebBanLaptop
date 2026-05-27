<?php
session_start();
require_once "database.php";
$db = new Database();

// Giả định hệ thống của bạn lưu vai trò người dùng trong $_SESSION['role'] khi đăng nhập
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Lấy ID đơn hàng từ URL, ép kiểu (int) để phòng chống SQL Injection
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. LẤY THÔNG TIN TỔNG QUAN ĐƠN HÀNG (Kèm tên chương trình ưu đãi nếu có)
$sql_order = "SELECT o.*, pr.name as promo_name 
              FROM orders o 
              LEFT JOIN promotions pr ON o.promotion_id = pr.promotion_id 
              WHERE o.order_id = ?";
$order_result = $db->select($sql_order, 'i', [$order_id]);

// Nếu không tìm thấy đơn hàng trong hệ thống, xuất thông báo lỗi và dừng script
if (empty($order_result)) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Không tìm thấy đơn hàng #$order_id!</div></div>";
    exit();
}
$order = $order_result[0];

// 2. LẤY DANH SÁCH CHI TIẾT CÁC SẢN PHẨM TRONG ĐƠN HÀNG
$sql_items = "SELECT od.*, p.product_name 
              FROM order_details od 
              JOIN products p ON od.product_id = p.product_id
              WHERE od.order_id = ?";
$items = $db->select($sql_items, 'i', [$order_id]);

// 3. LẤY LỊCH SỬ LOG TRẠNG THÁI VẬN CHUYỂN ĐỂ DỰNG TIMELINE
$sql_history = "SELECT * FROM order_history WHERE order_id = ? ORDER BY created_at DESC";
$history = $db->select($sql_history, 'i', [$order_id]);

// 4. TÍNH TOÁN LẠI TIỀN HÀNG GỐC VÀ TIỀN GIẢM GIÁ
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += (float)$item['price'] * (int)$item['quantity'];
}

// Nếu không tính toán được từ chi tiết, lấy tạm tổng amount từ bảng orders
if ($subtotal <= 0) {
    $subtotal = (float)$order['total_amount'];
    $discount = 0;
} else {
    // Tiền giảm giá = Tổng tiền gốc - Số tiền thực tế khách trả
    $discount = round($subtotal) - round((float)$order['total_amount']);
}

// Phân tách màu sắc động cho Badge tùy theo trạng thái đơn hàng (Dùng tone Pastel hiện đại)
$status_class = 'status-cho-xu-ly'; 
if ($order['status'] == 'Đã giao' || $order['status'] == 'Thành công') {
    $status_class = 'status-da-giao';
} elseif ($order['status'] == 'Đang giao' || $order['status'] == 'Đang vận chuyển') {
    $status_class = 'status-dang-giao';
} elseif ($order['status'] == 'Đã hủy') {
    $status_class = 'status-da-huy';
} elseif ($order['status'] == 'Yêu cầu trả hàng') {
    $status_class = 'status-yeu-cau-tra-hang';
}

// 5. KIỂM TRA ĐIỀU KIỆN ĐƯỢC PHÉP HỦY ĐƠN HÀNG
$can_cancel = false;
if ($is_admin) {
    // Admin được hủy nếu đơn hàng chưa hoàn thành, chưa giao thành công và chưa hủy trước đó
    if (!in_array($order['status'], ['Đã giao', 'Thành công', 'Đã hủy'])) {
        $can_cancel = true;
    }
} else {
    // Khách hàng thông thường chỉ được hủy khi đơn ở trạng thái chờ
    if ($order['status'] == 'Chờ xử lý' || $order['status'] == 'Chờ xác nhận') {
        $can_cancel = true;
    }
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            background-color: #fdfae6; /* Giữ màu nền vàng kem sữa yêu thích */
            font-family: 'Inter', sans-serif; 
            color: #212529;
        }
        .card { 
            border-radius: 16px; 
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03); 
            border: none; 
            margin-bottom: 2rem; 
            overflow: hidden;
            background: white;
        }
        .card-header { 
            background: white; 
            border-bottom: 1px solid #f1f1f1;
            padding: 20px 24px;
        }

        /* --- NÚT ĐIỀU HƯỚNG --- */
        .btn-clean-nav {
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            color: #495057;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .btn-clean-nav:hover {
            background-color: #f8f9fa;
            color: #212529;
            border-color: #cde2cd;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }

        /* --- BADGE TRẠNG THÁI TONE PASTEL --- */
        .badge-custom {
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-cho-xu-ly { background-color: #fff3cd; color: #664d03; }
        .status-da-giao { background-color: #d1e7dd; color: #0f5132; }
        .status-dang-giao { background-color: #cff4fc; color: #055160; }
        .status-da-huy { background-color: #f8d7da; color: #842029; }
        .status-yeu-cau-tra-hang { background-color: #e2d9f3; color: #4b2c85; }

        /* TIMELINE VẬN CHUYỂN */
        .timeline { position: relative; padding-left: 30px; list-style: none; margin-top: 20px; }
        .timeline::before {
            content: ""; position: absolute; left: 7px; top: 5px; bottom: 5px;
            width: 2px; background: #dee2e6;
        }
        .timeline-item { position: relative; padding-bottom: 1.5rem; }
        .timeline-item::after {
            content: ""; position: absolute; left: -27px; top: 5px;
            width: 12px; height: 12px; border-radius: 50%; background: #ced4da;
        }
        .timeline-item.active::after {
            background: #198754; box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.2);
        }
        .timeline-item.canceled_status::after {
            background: #dc3545; box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.2);
        }
        .timeline-date { font-size: 0.8rem; color: #6c757d; font-weight: 600; }
        .timeline-content { font-size: 0.95rem; font-weight: 600; color: #212529; margin-top: 2px; }
        
        .extra-item { display: none; }
        #btn-toggle-timeline { cursor: pointer; color: #198754; text-decoration: none; font-size: 0.85rem; font-weight: 600; display: inline-block; margin-top: 10px; }
        #btn-toggle-timeline:hover { color: #0f5132; }

        @media print { 
            <?php if ($is_admin): ?>
                body { background-color: #fff; }
                .btn, .timeline-section, #btn-toggle-timeline, .nav-action-buttons, form { display: none !important; } 
                .card { box-shadow: none; border: none; }
                .card-header { background: none; color: #000; border-bottom: 2px solid #000; padding: 10px 0; }
            <?php else: ?>
                body { display: none !important; }
            <?php endif; ?>
        }
    </style>
</head>
<body>

    <div class="container mt-4 nav-action-buttons">
        <a href="index.php" class="btn btn-clean-nav rounded-3 px-4 shadow-sm">
            <i class="bi bi-house-door me-1"></i> Trang chủ
        </a>
    </div>

    <div class="container mt-4 mb-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Chi tiết mã đơn</span>
                    <h4 class="mb-0 fw-bold text-dark">#<?= $order_id ?></h4>
                </div>
                <span class="badge-custom <?= $status_class ?>"><?= htmlspecialchars($order['status']) ?></span>
            </div>
            
            <div class="card-body p-4">
                <div class="row g-4 mb-4 pb-4 border-bottom">
                    <div class="col-md-6 border-end">
                        <h6 class="text-secondary fw-bold small text-uppercase mb-3"><i class="bi bi-geo-alt-fill me-1"></i>Thông tin nhận hàng</h6>
                        <p class="mb-1 fw-bold text-dark"><?= htmlspecialchars($order['customer_name']) ?></p>
                        <p class="mb-1 text-secondary">Điện thoại: <span class="text-dark fw-medium"><?= htmlspecialchars($order['phone']) ?></span></p>
                        <p class="mb-0 text-muted small">Địa chỉ: <?= htmlspecialchars($order['address']) ?></p>
                    </div>
                    <div class="col-md-6 ps-md-4">
                        <h6 class="text-secondary fw-bold small text-uppercase mb-3"><i class="bi bi-credit-card-fill me-1"></i>Giao dịch & Thanh toán</h6>
                        <p class="mb-1">Phương thức: <span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($order['payment_method']) ?></span></p>
                        <p class="mb-0 text-muted small">Thời gian đặt: <?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></p>
                    </div>
                </div>

                <div class="timeline-section mb-5 p-4 bg-light rounded-4 border">
                    <h6 class="text-dark fw-bold mb-3"><i class="bi bi-box-seam-fill text-primary me-2"></i>Hành trình đơn hàng</h6>
                    <?php if (!empty($history)): ?>
                        <ul class="timeline mb-0" id="timeline-list">
                            <?php foreach ($history as $index => $step): 
                                // Nếu log ghi nhận đơn hủy thì cho dấu mốc timeline màu đỏ rực
                                $is_cancelled_log = ($step['status_name'] == 'Đã hủy');
                                $timeline_active_class = '';
                                if ($index === 0) {
                                    $timeline_active_class = $is_cancelled_log ? 'canceled_status' : 'active';
                                }
                            ?>
                                <li class="timeline-item <?= $timeline_active_class ?> <?= $index > 1 ? 'extra-item' : '' ?>">
                                    <div class="timeline-date">
                                        <?= date('H:i - d/m/Y', strtotime($step['created_at'])) ?>
                                        <?php if(!empty($step['location'])) echo " — <span class='text-dark'>" . htmlspecialchars($step['location']) . "</span>"; ?>
                                    </div>
                                    <div class="timeline-content <?= $is_cancelled_log ? 'text-danger' : '' ?>">
                                        <?= htmlspecialchars($step['status_name']) ?>
                                    </div>
                                    <?php if(!empty($step['description'])): ?>
                                        <div class="text-muted small mt-1"><?= htmlspecialchars($step['description']) ?></div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <?php if (count($history) > 2): ?>
                            <a id="btn-toggle-timeline" onclick="toggleTimeline()">
                                <i class="bi bi-chevron-down"></i> Xem đầy đủ lịch sử hành trình
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted small mb-0 ms-2" style="font-style: italic;"><i class="bi bi-hourglass-split me-1"></i>Hệ thống đang chuẩn bị hàng và cập nhật hành trình...</p>
                    <?php endif; ?>
                </div>

                <h6 class="text-secondary fw-bold small text-uppercase mb-3"><i class="bi bi-list-stars me-1"></i>Danh sách sản phẩm</h6>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3 px-3 text-secondary">Sản phẩm</th>
                                <th class="text-center py-3 text-secondary">Số lượng</th>
                                <th class="text-end py-3 text-secondary">Đơn giá</th>
                                <th class="text-end py-3 px-3 text-secondary">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="fw-semibold text-dark py-3 px-3"><?= htmlspecialchars($item['product_name']) ?></td>
                                <td class="text-center text-secondary py-3">x<?= $item['quantity'] ?></td>
                                <td class="text-end text-secondary py-3"><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
                                <td class="text-end fw-bold text-dark py-3 px-3"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="border-top">
                            <tr>
                                <td colspan="3" class="text-end text-muted pt-3">Tạm tính:</td>
                                <td class="text-end text-dark fw-medium pt-3 px-3"><?= number_format($subtotal, 0, ',', '.') ?>đ</td>
                            </tr>
                            <?php if ($discount > 0): ?>
                            <tr>
                                <td colspan="3" class="text-end text-success align-middle">Mã giảm giá (<?= htmlspecialchars($order['promo_name'] ?? 'Ưu đãi') ?>):</td>
                                <td class="text-end text-success fw-bold pt-2 px-3">-<?= number_format($discount, 0, ',', '.') ?>đ</td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th colspan="3" class="text-end text-dark fs-5 pt-3">TỔNG THANH TOÁN:</th>
                                <th class="text-end text-danger fs-3 pt-2 px-3"><?= number_format($order['total_amount'], 0, ',', '.') ?>đ</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-4 d-flex gap-2 nav-action-buttons flex-wrap">
                    <a href="<?= $is_admin ? 'admin_orders.php' : 'order_history.php' ?>" class="btn btn-clean-nav rounded-3 px-4 shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i> <?= $is_admin ? 'Quản lý đơn hàng' : 'Lịch sử đơn hàng' ?>
                    </a>
                    
                    <?php if ($can_cancel): ?>
                        <form action="cancel_order.php" method="POST" onsubmit="return confirm('Bạn có thực sự chắc chắn muốn hủy đơn hàng này?');" class="d-inline">
                            <input type="hidden" name="order_id" value="<?= $order_id ?>">
                            <button type="submit" class="btn btn-outline-danger rounded-3 px-4 shadow-sm fw-medium">
                                <i class="bi bi-x-circle me-1"></i> Hủy đơn hàng
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($is_admin): ?>
                        <button onclick="window.print()" class="btn btn-outline-success rounded-3 px-4 shadow-sm fw-medium">
                            <i class="bi bi-printer me-1"></i> Xuất hóa đơn (Admin)
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleTimeline() {
            const extraItems = document.querySelectorAll('.extra-item');
            const btn = document.getElementById('btn-toggle-timeline');
            
            if (extraItems[0].style.display === 'block') {
                extraItems.forEach(item => item.style.display = 'none');
                btn.innerHTML = '<i class="bi bi-chevron-down"></i> Xem đầy đủ lịch sử hành trình';
            } else {
                extraItems.forEach(item => item.style.display = 'block');
                btn.innerHTML = '<i class="bi bi-chevron-up"></i> Thu gọn bớt';
            }
        }
    </script>
</body>
</html>