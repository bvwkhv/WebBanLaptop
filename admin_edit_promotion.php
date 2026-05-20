<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
require_once "database.php";
$db = new Database();

$id = $_GET['id'] ?? null;
$promo = null;

// Nếu có ID -> Chế độ SỬA: Lấy dữ liệu cũ
if ($id) {
    $res = $db->select("SELECT * FROM promotions WHERE promotion_id = ?", "i", [$id]);
    $promo = $res[0] ?? null;
}

// Xử lý khi nhấn LƯU
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $type = $_POST['discount_type'];
    $value = $_POST['discount_value'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $status = $_POST['status'];

    if ($id) {
        // Cập nhật
        $sql = "UPDATE promotions SET name=?, discount_type=?, discount_percent=?, start_date=?, end_date=?, status=? WHERE promotion_id=?";
        $db->execute($sql, "ssdssii", [$name, $type, $value, $start, $end, $status, $id]);
    } else {
        // Thêm mới
        $sql = "INSERT INTO promotions (name, discount_type, discount_percent, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?)";
        $db->execute($sql, "ssdssi", [$name, $type, $value, $start, $end, $status]);
    }
    header("Location: admin_promotions.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= $id ? "Sửa" : "Thêm" ?> Khuyến Mãi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #fdfae6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .form-container { max-width: 800px; margin: 50px auto; background: transparent; }
        .title { font-weight: 800; letter-spacing: 2px; margin-bottom: 40px; text-align: center; }
        label { font-weight: 600; min-width: 150px; }
        .form-control { border-radius: 0; border: none; margin-bottom: 15px; padding: 8px 15px; }
        .btn-custom { border-radius: 25px; padding: 8px 50px; font-weight: bold; border: 1px solid #ccc; transition: 0.3s; }
        .btn-save { background: #ffb74d; color: black; border-color: #e69138; }
        .btn-cancel { background: #ffb74d; color: black; border-color: #e69138; }
        .btn-custom:hover { opacity: 0.8; transform: translateY(-2px); }
        .radio-group { display: flex; gap: 20px; align-items: center; }
    </style>
</head>
<body>

<div class="container form-container">
    <h2 class="title text-uppercase"><?= $id ? "Sửa Khuyến Mãi" : "Thêm Khuyến Mãi" ?></h2>

    <form method="POST">
        <div class="row mb-3 align-items-center">
            <div class="col-md-7">
                <div class="d-flex align-items-center mb-3">
                    <label>Mã khuyến mãi :</label>
                    <input type="text" class="form-control" value="<?= $id ? 'KM'.str_pad($id, 2, '0', STR_PAD_LEFT) : '' ?>" readonly placeholder="Tự động tạo...">
                </div>
                <div class="d-flex align-items-center mb-3">
                    <label>Tên chương trình:</label>
                    <input type="text" name="name" class="form-control" value="<?= $promo['name'] ?? '' ?>" required>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <label>Loại giảm giá:</label>
                    <div class="radio-group">
                        <span><input type="radio" name="discount_type" value="percent" <?= ($promo['discount_type'] ?? 'percent') == 'percent' ? 'checked' : '' ?>> Phần trăm</span>
                        <span><input type="radio" name="discount_type" value="amount" <?= ($promo['discount_type'] ?? '') == 'amount' ? 'checked' : '' ?>> Số tiền</span>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <label>Giá trị giảm:</label>
                    <input type="number" name="discount_value" class="form-control" value="<?= $promo['discount_percent'] ?? '' ?>" required>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <label>Ngày bắt đầu :</label>
                    <input type="date" name="start_date" class="form-control" value="<?= isset($promo['start_date']) ? date('Y-m-d', strtotime($promo['start_date'])) : '' ?>" required>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <label>Ngày kết thúc :</label>
                    <input type="date" name="end_date" class="form-control" value="<?= isset($promo['end_date']) ? date('Y-m-d', strtotime($promo['end_date'])) : '' ?>" required>
                </div>
            </div>

            <div class="col-md-5 ps-5">
                <label class="d-block mb-3">Trạng thái:</label>
                <div class="mb-2">
                    <input type="radio" name="status" value="1" <?= ($promo['status'] ?? 1) == 1 ? 'checked' : '' ?>> Hoạt động
                </div>
                <div>
                    <input type="radio" name="status" value="0" <?= ($promo['status'] ?? 1) == 0 ? 'checked' : '' ?>> Tắt
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center gap-4 mt-5">
            <button type="submit" class="btn btn-custom btn-save shadow-sm">Lưu</button>
            <a href="admin_promotions.php" class="btn btn-custom btn-cancel shadow-sm text-decoration-none">Hủy</a>
        </div>
    </form>
</div>

</body>
</html>