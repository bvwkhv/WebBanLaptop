<?php
require_once "database.php";
$db = new Database();

$id = $_GET['id'] ?? null;
$news = null;

// Nếu là sửa, lấy dữ liệu cũ
if ($id) {
    $res = $db->select("SELECT * FROM news WHERE news_id = ?", "i", [$id]);
    $news = $res[0] ?? null;
}

// Xử lý khi nhấn Lưu
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $summary = $_POST['summary'];
    $category = $_POST['category'];
    $content = $_POST['content'];
    $status = $_POST['status'];
    $image = $news['image_url'] ?? '';

    // Xử lý Upload ảnh nếu có chọn file mới
    if ($_FILES['image']['name']) {
        $image = time() . '_' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "image/" . $image);
    }

    if ($id) {
        // Cập nhật
        $sql = "UPDATE news SET title=?, summary=?, content=?, category=?, image_url=?, status=? WHERE news_id=?";
        $db->execute($sql, "ssssssi", [$title, $summary, $content, $category, $image, $status, $id]);
    } else {
        // Thêm mới
        $sql = "INSERT INTO news (title, summary, content, category, image_url, status) VALUES (?, ?, ?, ?, ?, ?)";
        $db->execute($sql, "sssssi", [$title, $summary, $content, $category, $image, $status]);
    }
    header("Location: admin_news.php");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #fdfae6; }
        .form-label { font-weight: bold; width: 150px; }
        .btn-main { background-color: #ffb74d; border: none; border-radius: 20px; font-weight: bold; width: 150px; box-shadow: 0 4px #e6a745; }
    </style>
</head>
<body>
<div class="container py-5" style="max-width: 800px;">
    <h2 class="text-center fw-bold mb-5"><?= $id ? 'SỬA TIN TỨC' : 'THÊM TIN TỨC' ?></h2>

    <form method="POST" enctype="multipart/form-data" class="bg-white p-4 shadow-sm rounded">
        <div class="d-flex align-items-center mb-3">
            <label class="form-label">Tiêu đề :</label>
            <input type="text" name="title" class="form-control" value="<?= $news['title'] ?? '' ?>" required>
        </div>

        <div class="d-flex align-items-center mb-3">
            <label class="form-label">Mô tả ngắn :</label>
            <textarea name="summary" class="form-control" rows="2"><?= $news['summary'] ?? '' ?></textarea>
        </div>

        <div class="d-flex align-items-center mb-3">
            <label class="form-label">Danh mục :</label>
            <select name="category" class="form-select w-50">
                <option value="Khuyến mãi" <?= ($news['category'] ?? '') == 'Khuyến mãi' ? 'selected' : '' ?>>Khuyến mãi</option>
                <option value="Công nghệ" <?= ($news['category'] ?? '') == 'Công nghệ' ? 'selected' : '' ?>>Công nghệ</option>
                <option value="Hướng dẫn" <?= ($news['category'] ?? '') == 'Hướng dẫn' ? 'selected' : '' ?>>Hướng dẫn</option>
            </select>
        </div>

        <div class="d-flex align-items-start mb-3">
            <label class="form-label">Nội dung chi tiết</label>
            <textarea name="content" class="form-control" rows="5"><?= $news['content'] ?? '' ?></textarea>
        </div>

        <div class="d-flex align-items-center mb-3">
            <label class="form-label">Hình ảnh:</label>
            <input type="file" name="image" class="form-control w-50">
            <?php if($news['image_url'] ?? false): ?>
                <img src="image/news/<?= $news['image_url'] ?>" width="50" class="ms-2">
            <?php endif; ?>
        </div>

        <div class="d-flex align-items-center mb-4">
            <label class="form-label">Trạng thái</label>
            <div class="form-check me-4">
                <input class="form-check-input" type="radio" name="status" value="1" <?= ($news['status'] ?? 1) == 1 ? 'checked' : '' ?>>
                <label class="form-check-label">Hiển thị</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="status" value="0" <?= ($news['status'] ?? 1) == 0 ? 'checked' : '' ?>>
                <label class="form-check-label">Ẩn</label>
            </div>
        </div>

        <div class="text-center mt-5">
            <button type="submit" class="btn btn-main me-3">Lưu</button>
            <a href="admin_news.php" class="btn btn-main">Hủy</a>
        </div>
    </form>
</div>
</body>
</html>