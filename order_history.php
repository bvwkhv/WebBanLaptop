<?php
session_start();
require_once "database.php";
$db = new Database();

// Giả sử bạn lưu tên user trong session khi đăng nhập
$current_user = $_SESSION['username'] ?? ''; 

if (!$current_user) {
    // Nếu vẫn lỗi, hãy thử bỏ dòng alert này đi để debug xem biến có gì
    //var_dump($_SESSION); die();
    echo "<script>alert('Vui lòng đăng nhập!'); window.location.href='login.php';</script>";
    exit();
}
$current_user_id = $_SESSION['user_id']; // Lấy số 1

// Tìm tất cả đơn hàng mà cột user_id bằng 1
$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$orders = $db->select($sql, 'i', [$current_user_id]);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lịch sử đơn hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{ background-color: #fff4c5; }
    </style>
</head>
<body>
<div class="container mt-5">
    <h3 class="mb-4">Lịch sử mua hàng của bạn</h3>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $row): ?>
                        <tr>
                            <td>#<?= $row['order_id'] ?></td>
                            <td><?= $row['order_date'] ?></td>
                            <td class="fw-bold"><?= number_format($row['total_amount'], 0, ',', '.') ?>đ</td>
                            <td>
                                <span class="badge <?= $row['status'] == 'Chờ xử lý' ? 'bg-warning' : 'bg-success' ?>">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                            <td>
                                <a href="order_details.php?id=<?= $row['order_id'] ?>" class="btn btn-sm btn-outline-primary">
                                    Chi tiết
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Bạn chưa có đơn hàng nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>