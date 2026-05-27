<?php
// Bắt đầu phiên làm việc để quản lý trạng thái đăng nhập của Admin
session_start();

// Kiểm tra quyền hạn: Nếu không phải Admin, lập tức chặn quyền truy cập và điều hướng về trang chủ
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Nhúng file cấu hình kết nối cơ sở dữ liệu
require_once "database.php";
$db = new Database();

// --- XỬ LÝ XÓA KHUYẾN MÃI ---
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id']; // Ép kiểu số nguyên để bảo mật SQL Injection
    $db->execute("DELETE FROM promotions WHERE promotion_id = ?", "i", [$delete_id]);
    
    // Xóa xong điều hướng lại về trang để xóa sạch tham số trên URL, tránh lặp lại hành động khi F5
    header("Location: admin_promotions.php");
    exit();
}

// --- CẤU HÌNH PHÂN TRANG ---
$limit = 5; // Số lượng bản ghi hiển thị trên một trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// --- XỬ LÝ TÌM KIẾM ĐA NĂNG (MÃ KM HOẶC TÊN KM) ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = "";
$params = [];
$types = "";

if ($search !== '') {
    // Thử lọc lấy phần số nếu người dùng gõ định dạng "KM03" hoặc "km3"
    $searchNumber = preg_replace('/[^0-9]/', '', $search);
    
    if (!empty($searchNumber)) {
        // Nếu chuỗi tìm kiếm có chứa số, tìm theo cả Tên hoặc Mã trùng khớp với số đó
        $where = " WHERE name LIKE ? OR promotion_id = ?";
        $params = ["%$search%", (int)$searchNumber];
        $types = "si";
    } else {
        // Nếu chỉ gõ chữ thuần túy, tìm kiếm theo Tên khuyến mãi
        $where = " WHERE name LIKE ?";
        $params = ["%$search%"];
        $types = "s";
    }
}

// --- TÍNH TOÁN SỐ TRANG DỰA TRÊN KẾT QUẢ TÌM KIẾM ---
$count_sql = "SELECT COUNT(*) as total FROM promotions" . $where;
$total_res = $db->select($count_sql, $types ?: null, $params);
$total_records = !empty($total_res) ? $total_res[0]['total'] : 0;
$total_pages = ceil($total_records / $limit);
if ($total_pages < 1) $total_pages = 1;

// Giới hạn trang hiện tại không vượt quá số trang thực tế (tránh lỗi rỗng dữ liệu khi phân trang)
if ($page > $total_pages) $page = $total_pages; 
$offset = ($page - 1) * $limit;

// --- LẤY DANH SÁCH BẢN GHI THEO PHÂN TRANG ---
$sql = "SELECT * FROM promotions" . $where . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params_list = array_merge($params, [$limit, $offset]);
$promotions = $db->select($sql, $types . "ii", $params_list) ?? []; // Đảm bảo trả về mảng nếu rỗng
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Khuyến mãi - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        /* Thiết lập giao diện nền vàng nhạt đồng bộ hệ thống */
        body { 
            background-color: #fdfae6; 
            font-family: 'Inter', sans-serif; 
            color: #333;
        }
        .header-title { 
            letter-spacing: 1px; 
            font-weight: 800; 
            color: #1e293b;
            margin-bottom: 30px; 
        }
        
        /* --- ĐỊNH DẠNG BẢNG DỮ LIỆU --- */
        .promo-table { 
            background: #ffb74d; 
            border-radius: 12px; 
            overflow: hidden; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: none;
        }
        .promo-table th { 
            background: #fb8c00; 
            color: white; 
            font-weight: 600;
            padding: 14px;
            border: none; 
        }
        .promo-table td { 
            background: #ffffff; 
            vertical-align: middle; 
            padding: 14px;
            color: #4a321a;
            border-bottom: 1px solid #ffb74d; 
        }
        .promo-table tr:last-child td {
            border-bottom: none; /* Xóa viền hàng cuối cùng giúp bo góc bảng mượt mà */
        }

        /* --- HÀNH ĐỘNG & NÚT BẤM --- */
        .btn-action { 
            border-radius: 20px; 
            padding: 4px 16px; 
            font-size: 13px; 
            font-weight: 600;
            border: 1px solid #e2e8f0; 
            background: white; 
            color: #475569;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }
        .btn-action.btn-edit:hover {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }
        .btn-action.btn-delete:hover {
            background: #dc2626;
            color: white;
            border-color: #dc2626;
        }
        .btn-create { 
            background: #ffb74d; 
            color: #4a321a;
            border-radius: 25px; 
            font-weight: 700; 
            border: 2px solid #ffb74d; 
            padding: 8px 24px; 
            transition: all 0.2s;
        }
        .btn-create:hover { 
            background: transparent;
            color: #ffb74d;
        }

        /* --- ĐỊNH DẠNG THANH TÌM KIẾM --- */
        .search-box {
            border-radius: 25px 0 0 25px;
            border: 2px solid #ffb74d;
            padding-left: 20px;
        }
        .search-box:focus {
            box-shadow: none;
            border-color: #fb8c00;
        }
        .btn-search {
            background-color: #ffb74d;
            border: 2px solid #ffb74d;
            border-radius: 0 25px 25px 0;
            color: white;
            padding: 0 20px;
        }
        .btn-search:hover {
            background-color: #fb8c00;
            border-color: #fb8c00;
            color: white;
        }

        /* --- PHÂN TRANG CỦA BOOTSTRAP --- */
        .pagination .page-link { 
            background: white; 
            border: 1px solid #ffb74d; 
            color: #fb8c00; 
            font-weight: bold; 
            margin: 0 2px;
            border-radius: 6px;
        }
        .pagination .page-item.active .page-link {
            background: #fb8c00;
            border-color: #fb8c00;
            color: white;
        }
        .pagination .page-link:hover {
            background: #ffe0b2;
            color: #fb8c00;
        }

        /* --- ĐỊNH DẠNG TRẠNG THÁI --- */
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-active { background-color: #c8e6c9; color: #2e7d32; }
        .status-expired { background-color: #ffcdd2; color: #c62828; }
        .status-paused { background-color: #e2e8f0; color: #64748b; }
        .status-upcoming { background-color: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>

<div class="container mt-4">
    <a href="admin_dashboard.php" class="btn btn-light rounded-pill shadow-sm px-4 fw-semibold border text-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Quay lại Dashboard
    </a>
</div>

<div class="container py-4">
    <h2 class="header-title text-uppercase text-center">Quản lý chương trình khuyến mãi</h2>

    <div class="row g-3 justify-content-between align-items-center mb-4 px-2">
        <div class="col-md-6 col-lg-5">
            <form class="d-flex" method="GET" action="admin_promotions.php">
                <input type="text" name="search" class="form-control search-box" placeholder="Nhập mã (VD: KM02) hoặc tên khuyến mãi..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-search"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>
        <div class="col-md-auto text-end">
            <a href="admin_edit_promotion.php" class="btn btn-create shadow-sm"><i class="fa-solid fa-plus me-2"></i>Tạo mới</a>
        </div>
    </div>

    <div class="table-responsive shadow-sm rounded-3">
        <table class="table promo-table mb-0 text-center">
            <thead>
                <tr>
                    <th style="width: 10%;">Mã</th>
                    <th style="width: 25%;" class="text-start">Tên KM</th>
                    <th style="width: 15%;">Mức giảm</th>
                    <th style="width: 20%;">Thời gian áp dụng</th>
                    <th style="width: 15%;">Trạng thái</th>
                    <th style="width: 15%;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($promotions)): ?>
                    <?php foreach ($promotions as $p): 
                        // Logic kiểm tra thời gian thực để phân loại trạng thái trực quan
                        $now = date('Y-m-d H:i:s');
                        
                        if ($p['status'] == 0) {
                            $status_text = "Đã tạm dừng";
                            $status_class = "status-paused";
                        } elseif ($now < $p['start_date']) {
                            $status_text = "Chưa diễn ra";
                            $status_class = "status-upcoming";
                        } elseif ($now > $p['end_date']) {
                            $status_text = "Hết hạn";
                            $status_class = "status-expired";
                        } else {
                            $status_text = "Đang áp dụng";
                            $status_class = "status-active";
                        }
                    ?>
                    <tr>
                        <td class="fw-semibold">KM<?= str_pad($p['promotion_id'], 2, '0', STR_PAD_LEFT) ?></td>
                        <td class="fw-bold text-start text-truncate" style="max-width: 240px;"><?= htmlspecialchars($p['name']) ?></td>
                        
                        <td class="fw-bold text-danger">
                            <?php if (isset($p['discount_type']) && $p['discount_type'] === 'amount'): ?>
                                <?= number_format($p['discount_percent'], 0, ',', '.') ?> đ
                            <?php else: ?>
                                <?= (int)$p['discount_percent'] ?>%
                            <?php endif; ?>
                        </td>

                        <td class="small">
                            <i class="fa-regular fa-clock me-1 text-muted"></i><?= date('d/m/Y', strtotime($p['start_date'])) ?> - <?= date('d/m/Y', strtotime($p['end_date'])) ?>
                        </td>
                        <td>
                            <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                        </td>
                        <td>
                            <a href="admin_edit_promotion.php?id=<?= $p['promotion_id'] ?>" class="btn-action btn-edit me-1"><i class="fa-regular fa-pen-to-square"></i> Sửa</a>
                            <a href="?delete_id=<?= $p['promotion_id'] ?>" class="btn-action btn-delete" onclick="return confirm('Bạn chắc chắn có muốn xóa chương trình khuyến mãi này không?')"><i class="fa-regular fa-trash-can"></i> Xóa</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 fw-semibold text-muted bg-light">Không tìm thấy mã hoặc chương trình khuyến mãi nào phù hợp.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>"><i class="fa-solid fa-angles-left"></i></a>
            </li>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>"><i class="fa-solid fa-angles-right"></i></a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>