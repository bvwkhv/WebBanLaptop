<?php
// Bắt đầu phiên làm việc để quản lý trạng thái đăng nhập hệ thống
session_start();

// --- 1. KIỂM TRA QUYỀN TRUY CẬP (BẢO MẬT) ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Nhúng file kết nối cơ sở dữ liệu và khởi tạo đối tượng DB
require_once "database.php";
$db = new Database();

// Lấy ID tin tức từ URL (nếu có hành động Sửa)
$id = $_GET['id'] ?? null;
$news = null;
$errors = []; // Mảng chứa các thông báo lỗi nếu dữ liệu không hợp lệ khi kiểm tra phía Server

// Nếu có ID, tiến hành lấy dữ liệu cũ từ Database để đổ vào các ô nhập liệu
if ($id) {
    $res = $db->select("SELECT * FROM news WHERE news_id = ?", "i", [$id]);
    $news = $res[0] ?? null;
    
    // Nếu truyền ID bậy bạ không tồn tại bài viết, đá ngược về trang quản lý bài viết
    if (!$news) {
        header("Location: admin_news.php");
        exit();
    }
}

// --- 2. XỬ LÝ KHI NGƯỜI DÙNG NHẤN LƯU FORM (POST METHOD) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Thu thập dữ liệu và dùng hàm trim() để cắt bỏ các khoảng trắng thừa ở đầu/cuối chuỗi
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $category = $_POST['category'] ?? '';
    $content = trim($_POST['content'] ?? '');
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    $image = $news['image_url'] ?? ''; // Mặc định giữ lại ảnh cũ nếu không cập nhật ảnh mới

    // --- RÀNG BUỘC KIỂM TRA DỮ LIỆU PHÍA SERVER (BACK-END VALIDATION) ---
    // Kiểm tra tiêu đề: Không trống, độ dài từ 1 đến 255 ký tự
    $title_len = mb_strlen($title);
    if ($title_len < 1 || $title_len > 255) {
        $errors[] = "Tiêu đề không được để trống và không vượt quá 255 ký tự.";
    }

    // Kiểm tra mô tả ngắn: Tối đa 500 ký tự (Có thể để trống)
    $summary_len = mb_strlen($summary);
    if ($summary_len > 500) {
        $errors[] = "Mô tả ngắn không được vượt quá 500 ký tự.";
    }

    // Kiểm tra nội dung chi tiết: Không trống, độ dài từ 1 đến 500 ký tự theo yêu cầu
    $content_len = mb_strlen($content);
    if ($content_len < 1 || $content_len > 500) {
        $errors[] = "Nội dung chi tiết không được để trống và không vượt quá 500 ký tự.";
    }

    // Kiểm tra danh mục: Phải nằm trong danh sách lựa chọn hợp lệ
    $valid_categories = ['Khuyến mãi', 'Công nghệ', 'Hướng dẫn'];
    if (!in_array($category, $valid_categories)) {
        $errors[] = "Danh mục bài viết được chọn không hợp lệ.";
    }

    // --- XỬ LÝ UPLOAD HÌNH ẢNH ---
    // Nếu không có lỗi ràng buộc nào và admin có chọn file ảnh mới
    if (empty($errors) && isset($_FILES['image']) && $_FILES['image']['name']) {
        $target_dir = "image/news/"; // Đồng bộ thư mục chứa ảnh tin tức như code hiển thị gốc của bạn
        
        // Kiểm tra và tự động tạo thư mục nếu chưa tồn tại trên host
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        // Tạo tên file duy nhất bằng hàm time() để tránh bị ghi đè trùng tên file
        $image_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $target_dir . $image_name;

        // Tiến hành di chuyển file từ thư mục tạm của hệ thống sang thư mục dự án
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image = $image_name; // Cập nhật tên file mới vào biến lưu trữ cơ sở dữ liệu
        } else {
            $errors[] = "Gặp lỗi trong quá trình upload tập tin hình ảnh.";
        }
    }

    // --- HÀNH ĐỘNG LƯU VÀO DATABASE (NẾU DỮ LIỆU HOÀN TOÀN HỢP LỆ) ---
    if (empty($errors)) {
        if ($id) {
            // Trường hợp: CẬP NHẬT (SỬA) TIN TỨC ĐÃ CÓ
            $sql = "UPDATE news SET title=?, summary=?, content=?, category=?, image_url=?, status=? WHERE news_id=?";
            $db->execute($sql, "ssssssi", [$title, $summary, $content, $category, $image, $status, $id]);
        } else {
            // Trường hợp: THÊM MỚI TIN TỨC VÀO HỆ THỐNG
            $sql = "INSERT INTO news (title, summary, content, category, image_url, status) VALUES (?, ?, ?, ?, ?, ?)";
            $db->execute($sql, "sssssi", [$title, $summary, $content, $category, $image, $status]);
        }
        // Lưu thành công, chuyển hướng ngay về trang danh sách quản lý
        header("Location: admin_news.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Sửa tin tức' : 'Thêm tin tức' ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        body { 
            background-color: #fdfae6; /* Giữ tông màu kem Pastel đồng bộ toàn hệ thống admin */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }

        /* Khối bo góc chứa toàn bộ form nhập liệu */
        .form-card {
            background-color: white;
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
            border: none;
        }

        /* Đồng bộ định dạng tiêu chuẩn cho nhãn trường nhập liệu */
        .form-label { 
            font-weight: 600; 
            color: #4a5568;
            margin-bottom: 8px;
        }

        /* Tùy chỉnh các ô input, textarea, select mềm mại hơn */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 10px 14px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #ffb74d;
            box-shadow: 0 0 0 3px rgba(255, 183, 77, 0.15);
        }

        /* Thiết kế hệ thống nút bấm hành động dạng dẹt phẳng hiện đại thay vì đổ bóng dày kiểu cũ */
        .btn-action { 
            font-weight: 600; 
            padding: 10px 30px; 
            border-radius: 8px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-save {
            background-color: #ffb74d; 
            border: none; 
            color: #4a321a;
        }
        .btn-save:hover {
            background-color: #f5a623;
            transform: translateY(-1px);
        }
        .btn-cancel {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #475569;
        }
        .btn-cancel:hover {
            background-color: #e2e8f0;
            color: #1e293b;
        }

        /* Khung hiển thị ảnh xem trước nhỏ gọn */
        .preview-img-box {
            border: 1px dashed #cbd5e1;
            padding: 6px;
            border-radius: 8px;
            background-color: #f8fafc;
            display: inline-block;
        }
    </style>
</head>
<body>

<div class="container py-5" style="max-width: 750px;">
    <h3 class="text-center fw-bold mb-4" style="letter-spacing: -0.5px;">
        <i class="fa-regular fa-pen-to-square me-2"></i><?= $id ? 'CHỈNH SỬA TIN TỨC' : 'THÊM TIN TỨC MỚI' ?>
    </h3>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Lỗi nhập liệu:</strong>
            <ul class="mb-0 mt-1 small">
                <?php foreach($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="form-card">
        
        <div class="mb-3">
            <label class="form-label">Tiêu đề bài viết <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" 
                   value="<?= htmlspecialchars($news['title'] ?? '') ?>" 
                   required minlength="1" maxlength="255" 
                   placeholder="Nhập tiêu đề tin tức ngắn gọn...">
            <div class="form-text text-muted small">Độ dài tối đa cho phép là 255 ký tự.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Mô tả ngắn (Tóm tắt)</label>
            <textarea name="summary" class="form-control" rows="2" 
                      maxlength="500" placeholder="Nhập một vài tóm tắt ngắn về bài viết..."><?= htmlspecialchars($news['summary'] ?? '') ?></textarea>
            <div class="form-text text-muted small">Không bắt buộc nhập, giới hạn không vượt quá 500 ký tự.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Danh mục bài viết <span class="text-danger">*</span></label>
            <select name="category" class="form-select" required>
                <option value="" disabled <?= !isset($news['category']) ? 'selected' : '' ?>>-- Chọn danh mục tin tức --</option>
                <option value="Khuyến mãi" <?= ($news['category'] ?? '') == 'Khuyến mãi' ? 'selected' : '' ?>>Khuyến mãi</option>
                <option value="Công nghệ" <?= ($news['category'] ?? '') == 'Công nghệ' ? 'selected' : '' ?>>Công nghệ</option>
                <option value="Hướng dẫn" <?= ($news['category'] ?? '') == 'Hướng dẫn' ? 'selected' : '' ?>>Hướng dẫn</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Nội dung chi tiết <span class="text-danger">*</span></label>
            <textarea name="content" class="form-control" rows="5" 
                      required minlength="1" maxlength="500" 
                      placeholder="Viết nội dung bài viết chi tiết tại đây..."><?= htmlspecialchars($news['content'] ?? '') ?></textarea>
            <div class="form-text text-muted small">Nội dung bài viết giới hạn trong khoảng từ 1 đến 500 ký tự.</div>
        </div>

        <div class="mb-4">
            <label class="form-label">Hình ảnh minh họa</label>
            <div class="d-flex align-items-center gap-3">
                <input type="file" name="image" class="form-control" accept="image/*">
                
                <?php if($news['image_url'] ?? false): ?>
                    <div class="text-center small">
                        <div class="preview-img-box">
                            <img src="image/news/<?= htmlspecialchars($news['image_url']) ?>" alt="Ảnh hiện tại" width="60" class="rounded">
                        </div>
                        <div class="text-muted mt-1" style="font-size: 0.75rem;">Ảnh cũ</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label d-block">Trạng thái bài đăng</label>
            <div class="d-flex gap-4 mt-1">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="status" id="status_show" value="1" <?= ($news['status'] ?? 1) == 1 ? 'checked' : '' ?>>
                    <label class="form-check-label text-success fw-semibold" for="status_show">
                        <i class="fa-regular fa-eye me-1"></i> Hiển thị công khai
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="status" id="status_hide" value="0" <?= ($news['status'] ?? 1) == 0 ? 'checked' : '' ?>>
                    <label class="form-check-label text-danger fw-semibold" for="status_hide">
                        <i class="fa-regular fa-eye-slash me-1"></i> Ẩn bài viết
                    </label>
                </div>
            </div>
        </div>

        <hr class="text-muted my-4">

        <div class="d-flex justify-content-center gap-3">
            <button type="submit" class="btn btn-action btn-save">
                <i class="fa-regular fa-floppy-disk"></i> Lưu dữ liệu
            </button>
            <a href="admin_news.php" class="btn btn-action btn-cancel">
                <i class="fa-solid fa-xmark"></i> Hủy bỏ
            </a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>