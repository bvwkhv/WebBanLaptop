<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once "database.php";
$db = new Database();

// Xử lý xóa tin
if (isset($_GET['delete_id'])) {
    $db->execute("DELETE FROM news WHERE news_id = ?", "i", [$_GET['delete_id']]);
    header("Location: admin_news.php");
}

$all_news = $db->select("SELECT * FROM news ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body { 
        background-color: #fdfae6; 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .table-container {
        background-color: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        margin-top: 20px;
    }

    .table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table thead th {
        background-color: #ffb74d !important;
        color: white !important;
        border: none;
        padding: 15px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
    }

    .table thead th:first-child { border-top-left-radius: 10px; }
    .table thead th:last-child { border-top-right-radius: 10px; }

    .table tbody td {
        padding: 15px;
        vertical-align: middle;
        border-bottom: 1px solid #eee;
        background-color: white;
    }

    .table tbody tr:hover td {
    }

    .btn-main { 
        background-color: #ffb74d; 
        border: none; 
        border-radius: 8px; 
        font-weight: 600; 
        padding: 6px 20px; 
        color: white;
        transition: 0.3s;
    }

    .btn-main:hover {
        background-color: #f5a623;
        color: white;
        transform: translateY(-2px);
    }

    .btn-delete {
        background-color: #ff8a65;
    }
</style>
</head>
<body>
    <div class="container mt-3">
    <a href="admin_dashboard.php" class="btn btn-outline-secondary rounded-pill shadow-sm">
        <i class="fa-solid fa-arrow-left me-2"></i>Quay lại Dashboard
    </a>
    </div>
<div class="container py-5">
    <h2 class="text-center fw-bold mb-5">QUẢN LÝ TIN TỨC</h2>
    
    <a href="admin_news_form.php" class="btn btn-main mb-4 text-dark">Tạo mới</a>

    <table class="table table-custom shadow-sm">
        <thead>
            <tr class="text-center">
                <th>ID</th>
                <th>Tiêu đề</th>
                <th>Ngày đăng</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($all_news as $n): ?>
            <tr class="text-center">
                <td><?= $n['news_id'] ?></td>
                <td class="text-start ps-4"><?= $n['title'] ?></td>
                <td><?= date('d/m/Y', strtotime($n['created_at'])) ?></td>
                <td><?= $n['status'] == 1 ? 'Hiển thị' : 'Ẩn' ?></td>
                <td>
                    <a href="admin_news_form.php?id=<?= $n['news_id'] ?>" class="btn btn-main btn-sm text-dark px-3 me-2">Sửa</a>
                    <a href="?delete_id=<?= $n['news_id'] ?>" class="btn btn-main btn-sm text-dark px-3" onclick="return confirm('Xóa tin này?')">Xóa</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>