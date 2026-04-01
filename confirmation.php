<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_SESSION['cart'])) {
    header("Location: checkout.php");
    exit();
}

// Lấy dữ liệu từ Form nhập liệu
$d = $_POST; 
?>
<!DOCTYPE html>
<html>
<head>
    <title>Xác nhận đơn hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0">Xác nhận thông tin đặt hàng</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 border-end">
                        <h5>Thông tin giao hàng</h5>
                        <p><strong>Người nhận:</strong> <?= htmlspecialchars($d['fullname']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($d['email']) ?></p>
                        <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($d['phone']) ?></p>
                        <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($d['address']) ?></p>
                        <p><strong>Ghi chú:</strong> <?= htmlspecialchars($d['note'] ?: 'Không có') ?></p>
                        <p><strong>Thanh toán:</strong> 
                        <span class="badge bg-info text-dark">
                            <?= isset($d['payment_method']) ? htmlspecialchars($d['payment_method']) : 'Chưa chọn' ?>
                        </span>
                        </p>
                    </div>

                    <div class="col-md-6">
                        <h5>Chi tiết sản phẩm</h5>
                        <table class="table table-sm">
                            <?php $total = 0; foreach($_SESSION['cart'] as $item): 
                                $sub = $item['price'] * $item['qty']; $total += $sub; ?>
                                <tr>
                                    <td><?= $item['name'] ?> (x<?= $item['qty'] ?>)</td>
                                    <td class="text-end"><?= number_format($sub, 0, ',', '.') ?>đ</td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="fs-5 fw-bold text-danger">
                                <td>Tổng cộng:</td>
                                <td class="text-end"><?= number_format($total, 0, ',', '.') ?>đ</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr>

                <form action="process_checkout.php" method="POST">
                    <?php foreach($d as $key => $value): ?>
                        <input type="hidden" name="<?= $key ?>" value="<?= htmlspecialchars($value) ?>">
                    <?php endforeach; ?>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="javascript:history.back()" class="btn btn-outline-secondary">Quay lại sửa</a>
                        <button type="submit" class="btn btn-success btn-lg">Xác nhận & Đặt hàng ngay</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>