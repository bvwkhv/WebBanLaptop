<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once "database.php";
$db = new Database();

// Xử lý xóa khuyến mãi
if (isset($_GET['delete_id'])) {
    $db->execute("DELETE FROM promotions WHERE promotion_id = ?", "i", [$_GET['delete_id']]);
    header("Location: admin_promotions.php");
    exit();
}

// Cấu hình phân trang
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Tìm kiếm
$search = $_GET['search'] ?? '';
$where = $search ? " WHERE name LIKE ?" : "";
$params = $search ? ["%$search%"] : [];
$types = $search ? "s" : "";

// Lấy danh sách
$sql = "SELECT * FROM promotions" . $where . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params_list = array_merge($params, [$limit, $offset]);
$promotions = $db->select($sql, $types . "ii", $params_list);

// Đếm tổng để phân trang
$count_sql = "SELECT COUNT(*) as total FROM promotions" . $where;
$total_res = $db->select($count_sql, $types, $params);
$total_pages = ceil($total_res[0]['total'] / $limit);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Khuyến mãi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #fdfae6; font-family: Arial, sans-serif; }
        .header-title { letter-spacing: 2px; font-weight: 800; margin-bottom: 30px; }
        .promo-table { background: #ffb74d; border-radius: 10px; overflow: hidden; }
        .promo-table th { background: #fb8c00; color: white; border: none; }
        .promo-table td { background: #ffe0b2; vertical-align: middle; border-bottom: 1px solid #ffb74d; }
        .btn-action { border-radius: 20px; padding: 2px 15px; font-size: 14px; border: 1px solid #999; background: #eee; }
        .btn-create { background: #ffb74d; border-radius: 20px; font-weight: bold; border: 1px solid #e69138; padding: 5px 25px; }
        .pagination .page-link { background: none; border: none; color: black; font-weight: bold; }
        .status-active { color: #2e7d32; font-weight: bold; }
        .status-expired { color: #c62828; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container mt-3">
    <a href="admin_dashboard.php" class="btn btn-outline-secondary rounded-pill shadow-sm">
        <i class="fa-solid fa-arrow-left me-2"></i>Quay lại Dashboard
    </a>
    </div>

<div class="container py-5 text-center">
    <h2 class="header-title text-uppercase">Quản lý khuyến mãi</h2>

    <!-- Thanh công cụ -->
    <div class="d-flex justify-content-between align-items-center mb-4 px-3">
        <form class="d-flex w-50">
            <label class="me-2 mt-1 fw-bold">Tìm kiếm</label>
            <input type="text" name="search" class="form-control me-2" placeholder="Tìm kiếm mã KN..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-primary rounded-circle">🔍</button>
        </form>
        <a href="admin_edit_promotion.php" class="btn btn-create shadow-sm">Tạo mới</a>
    </div>

    <!-- Bảng dữ liệu -->
    <div class="table-responsive">
        <table class="table promo-table">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Tên KM</th>
                    <th>% giảm</th>
                    <th>Thời gian</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($promotions as $p): 
                    $now = date('Y-m-d H:i:s');
                    $status_text = "Đang áp dụng";
                    $status_class = "status-active";

                    if ($p['status'] == 0) {
                        $status_text = "Đã tạm dừng";
                        $status_class = "text-muted";
                    } elseif ($now < $p['start_date']) {
                        $status_text = "Chưa diễn ra";
                        $status_class = "text-primary";
                    } elseif ($now > $p['end_date']) {
                        $status_text = "Hết hạn";
                        $status_class = "status-expired";
                    }
                ?>
                <tr>
                    <td>KM<?= str_pad($p['promotion_id'], 2, '0', STR_PAD_LEFT) ?></td>
                    <td class="fw-bold"><?= $p['name'] ?></td>
                    <td><?= $p['discount_percent'] ?>%</td>
                    <td><?= date('d/m', strtotime($p['start_date'])) ?> - <?= date('d/m', strtotime($p['end_date'])) ?></td>
                    <td class="<?= $status_class ?>"><?= $status_text ?></td>
                    <td>
                        <a href="admin_edit_promotion.php?id=<?= $p['promotion_id'] ?>" class="btn btn-action me-2">Sửa</a>
                        <a href="?delete_id=<?= $p['promotion_id'] ?>" class="btn btn-action" onclick="return confirm('Xác nhận xóa?')">Xóa</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Phân trang -->
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item"><a class="page-link" href="?page=1&search=<?= $search ?>"> << </a></li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= $search ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $total_pages ?>&search=<?= $search ?>"> >> </a></li>
        </ul>
    </nav>
</div>

</body>
</html>