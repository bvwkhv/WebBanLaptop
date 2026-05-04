<?php
require_once "database.php";
$db = new Database();

/**
 * Hàm hỗ trợ chuyển đổi tên hành động sang tiếng Việt để hiển thị trên bảng
 */
function translateEvent($event) {
    $mapping = [
        'click_add_to_cart' => '🛒 Thêm vào giỏ hàng',
        'view_spec'         => '🔍 Xem chi tiết cấu hình',
        'scroll_to_reviews' => '📜 Xem đánh giá khách hàng',
        // Nếu bạn đã đổi bên JavaScript sang tiếng Việt thì mapping này sẽ giữ nguyên
        'Nhấn nút Thêm giỏ hàng' => '🛒 Thêm vào giỏ hàng',
        'Xem cấu hình'      => '🔍 Xem chi tiết cấu hình',
        'Cuộn xem đánh giá' => '📜 Xem đánh giá khách hàng'
    ];
    return $mapping[$event] ?? $event;
}

// 1. Thống kê tổng số lượng từng loại sự kiện
$sql_summary = "SELECT event_type, COUNT(*) as total 
                FROM event_tracking 
                GROUP BY event_type 
                ORDER BY total DESC";
$summary_data = $db->select($sql_summary);

// 2. Thống kê Top 10 sản phẩm được quan tâm nhiều nhất (Click thêm giỏ)
// Lưu ý: WHERE khớp với cả mã cũ và mã mới bạn đặt
$sql_top_cart = "SELECT target_id, COUNT(*) as total 
                 FROM event_tracking 
                 WHERE event_type = 'click_add_to_cart' OR event_type = 'Nhấn nút Thêm giỏ hàng'
                 GROUP BY target_id 
                 ORDER BY total DESC LIMIT 10";
$top_cart = $db->select($sql_top_cart);

// 3. Lấy 20 hành động mới nhất
$sql_recent = "SELECT e.*, u.username 
               FROM event_tracking e 
               LEFT JOIN users u ON e.user_id = u.user_id 
               ORDER BY e.created_at DESC LIMIT 20";
$recent_events = $db->select($sql_recent);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Quản lý hành vi người dùng</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border-radius: 15px; overflow: hidden; }
        .table thead { background-color: #f8f9fa; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <h2 class="mb-4 fw-bold text-dark text-center">📊 PHÂN TÍCH HÀNH VI NGƯỜI DÙNG</h2>

        <div class="row mb-4">
            <!-- Bảng tổng quan sự kiện -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-primary text-white fw-bold">Tổng quan hành động</div>
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead><tr><th>Loại hành động</th><th class="text-end">Số lần</th></tr></thead>
                            <tbody>
                                <?php foreach($summary_data as $row): ?>
                                <tr>
                                    <td><?= translateEvent($row['event_type']) ?></td>
                                    <td class="text-end"><span class="badge bg-info text-dark"><?= $row['total'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top sản phẩm được thêm vào giỏ -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-success text-white fw-bold">Sản phẩm hot (Click thêm giỏ)</div>
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead><tr><th>ID Sản phẩm</th><th class="text-end">Số lần</th></tr></thead>
                            <tbody>
                                <?php if(!empty($top_cart)): foreach($top_cart as $row): ?>
                                <tr>
                                    <td>📦 Mã sản phẩm: <strong><?= $row['target_id'] ?></strong></td>
                                    <td class="text-end fw-bold text-success"><?= $row['total'] ?></td>
                                </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="2" class="text-center text-muted">Chưa có dữ liệu thêm giỏ hàng</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nhật ký hành động thời gian thực -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
                <span>Nhật ký hành động mới nhất</span>
                <span class="badge bg-danger">Live</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Thời gian</th>
                                <th>Người dùng</th>
                                <th>Hành động</th>
                                <th>Chi tiết đối tượng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_events as $event): ?>
                            <tr>
                                <td class="ps-3 small text-muted"><?= date('H:i:s - d/m/Y', strtotime($event['created_at'])) ?></td>
                                <td><span class="fw-bold text-primary"><?= $event['username'] ?? 'Khách vãng lai' ?></span></td>
                                <td><span class="badge rounded-pill bg-light text-dark border"><?= translateEvent($event['event_type']) ?></span></td>
                                <td class="text-truncate" style="max-width: 300px;"><?= htmlspecialchars($event['target_id']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>