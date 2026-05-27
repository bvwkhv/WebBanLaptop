<?php
// Bắt đầu session để kiểm tra quyền đăng nhập
session_start();

// --- KIỂM TRA QUYỀN TRUY CẬP ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Nhúng file cấu hình và kết nối cơ sở dữ liệu
require_once "database.php";
$db = new Database();

// --- XỬ LÝ XÓA TIN TỨC (Giữ nguyên cơ chế GET gốc của bạn) ---
if (isset($_GET['delete_id'])) {
    $db->execute("DELETE FROM news WHERE news_id = ?", "i", [$_GET['delete_id']]);
    header("Location: admin_news.php");
    exit(); // Thêm exit() sau header để dừng script ngay lập tức sau khi điều hướng
}

// Lấy toàn bộ danh sách tin tức từ database (Tin mới nhất lên đầu)
$all_news = $db->select("SELECT * FROM news ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tin tức - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        body { 
            background-color: #fdfae6; /* Giữ màu nền kem nguyên bản của bạn */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }

        /* Thùng chứa bảng - bo góc và đổ bóng nhẹ mềm mại */
        .table-container {
            background-color: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: none;
        }

        .table {
            margin-bottom: 0;
            vertical-align: middle;
        }

        /* Định dạng thanh tiêu đề bảng (Thead) màu vàng cam Pastel */
        .table thead th {
            background-color: #ffb74d !important;
            color: white !important;
            border: none;
            padding: 16px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        /* Bo góc mượt cho 2 đầu dòng tiêu đề bảng */
        .table thead tr th:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .table thead tr th:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        /* Định dạng các ô dữ liệu body */
        .table tbody td {
            padding: 16px;
            border-bottom: 1px solid #f1f1f1;
            background-color: white;
            font-size: 0.95rem;
        }

        /* Hiệu ứng đổi màu nền nhẹ nhàng khi hover qua từng hàng */
        .table tbody tr:hover td {
            background-color: #fdfdf7;
        }

        /* Nút bấm chủ đạo (Tạo mới, Sửa) */
        .btn-main { 
            background-color: #ffb74d; 
            border: none; 
            border-radius: 8px; 
            font-weight: 600; 
            padding: 8px 20px; 
            color: #4a321a !important; /* Đổi màu chữ tối lại để dễ đọc trên nền vàng */
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-main:hover {
            background-color: #f5a623;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 166, 35, 0.2);
        }

        /* Tối ưu kích thước riêng cho nút sửa/xóa nhỏ gọn nằm trong bảng */
        .btn-table-action {
            padding: 6px 14px;
            font-size: 0.85rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 5px; /* Tạo khoảng cách vừa vặn giữa icon và chữ */
            font-weight: 600;
            border: none;
            transition: all 0.2s ease;
        }

        /* Nút sửa kế thừa màu vàng pastel chủ đạo */
        .btn-edit {
            background-color: #ffb74d;
            color: #4a321a !important;
        }
        .btn-edit:hover {
            background-color: #f5a623;
        }

        /* Nút xóa tùy biến riêng dịu mắt */
        .btn-delete {
            background-color: #fff0ed;
            color: #d9534f !important;
            border: 1px solid #fccac7;
        }
        .btn-delete:hover {
            background-color: #d9534f;
            color: white !important;
        }

        /* Nút quay lại Dashboard tinh chỉnh tinh tế hơn */
        .btn-back {
            background: white;
            border: 1px solid #dee2e6;
            color: #6c757d;
            font-weight: 500;
            padding: 8px 16px;
            transition: all 0.2s ease;
        }
        .btn-back:hover {
            background: #f8f9fa;
            color: #333;
            border-color: #ced4da;
        }

        /* Nhãn trạng thái hiển thị / ẩn tin tức dạng viên thuốc */
        .badge-status {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-show { background-color: #e6f4ea; color: #137333; }
        .status-hide { background-color: #fce8e6; color: #c5221f; }
    </style>
</head>
<body>

    <div class="container mt-4">
        <a href="admin_dashboard.php" class="btn btn-back rounded-3 shadow-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Quay lại Dashboard
        </a>
    </div>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1" style="letter-spacing: -0.5px;">QUẢN LÝ TIN TỨC</h2>
                <p class="text-muted small mb-0">Xem, chỉnh sửa hoặc xóa các bài viết tin tức công nghệ trên hệ thống.</p>
            </div>
            <a href="admin_news_form.php" class="btn btn-main shadow-sm">
                <i class="fa-solid fa-plus"></i> Tạo mới
            </a>
        </div>

        <div class="table-container shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr class="text-center">
                            <th style="width: 80px;">ID</th>
                            <th class="text-start" style="min-width: 350px;">Tiêu đề bài viết</th>
                            <th style="width: 140px;">Ngày đăng</th>
                            <th style="width: 140px;">Trạng thái</th>
                            <th style="width: 200px;">Hành động</th> </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($all_news)): ?>
                            <?php foreach($all_news as $n): ?>
                            <tr class="text-center">
                                <td class="fw-bold text-secondary">#<?= $n['news_id'] ?></td>
                                
                                <td class="text-start fw-semibold ps-3">
                                    <?= htmlspecialchars(mb_strimwidth($n['title'], 0, 80, "...")) ?>
                                </td>
                                
                                <td class="text-muted"><?= date('d/m/Y', strtotime($n['created_at'])) ?></td>
                                
                                <td>
                                    <?php if ($n['status'] == 1): ?>
                                        <span class="badge-status status-show"><i class="fa-solid fa-circle-check me-1"></i> Hiển thị</span>
                                    <?php else: ?>
                                        <span class="badge-status status-hide"><i class="fa-solid fa-eye-slash me-1"></i> Ẩn</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="admin_news_form.php?id=<?= $n['news_id'] ?>" class="btn-table-action btn-edit">
                                            <i class="fa-regular fa-pen-to-square"></i> Sửa
                                        </a>
                                        <a href="?delete_id=<?= $n['news_id'] ?>" class="btn-table-action btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa bài tin tức này không?')">
                                            <i class="fa-regular fa-trash-can"></i> Xóa
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Hiện tại chưa có bài viết tin tức nào trong hệ thống.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>