<?php
session_start();
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Thanh toán</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-7">
                <div class="card shadow-sm p-4">
                    <h4 class="mb-3">Thông tin giao hàng</h4>
                    <form action="process_checkout.php" method="POST">
    <div class="mb-3">
        <label class="form-label">Họ và tên</label>
        <input type="text" name="fullname" class="form-control" placeholder="Nguyễn Văn A" required>
    </div>
    
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Số điện thoại</label>
        <input type="text" name="phone" class="form-control" placeholder="0901234567" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Địa chỉ nhận hàng</label>
        <textarea name="address" class="form-control" rows="2" placeholder="Số nhà, tên đường, phường/xã..." required></textarea>
    </div>

    <div class="mb-3">
        <label class="form-label">Ghi chú đơn hàng</label>
        <textarea name="note" class="form-control" rows="3" placeholder="Ví dụ: Giao hàng giờ hành chính, gọi trước khi đến..."></textarea>
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-100">Xác nhận đặt hàng</button>
</form>
                </div>
            </div>
            
            <div class="col-md-5">
                <div class="card shadow-sm p-4">
                    <h4 class="mb-3">Đơn hàng của bạn</h4>
                    <ul class="list-group mb-3">
                        <?php 
                        $total = 0;
                        foreach($_SESSION['cart'] as $item): 
                            $subtotal = $item['price'] * $item['qty'];
                            $total += $subtotal;
                        ?>
                        <li class="list-group-item d-flex justify-content-between lh-sm">
                            <div>
                                <h6 class="my-0"><?= $item['name'] ?></h6>
                                <small class="text-muted">Số lượng: <?= $item['qty'] ?></small>
                            </div>
                            <span class="text-muted"><?= number_format($subtotal, 0, ',', '.') ?>đ</span>
                        </li>
                        <?php endforeach; ?>
                        <li class="list-group-item d-flex justify-content-between bg-light">
                            <span class="text-danger fw-bold">Tổng tiền (VNĐ)</span>
                            <strong class="text-danger"><?= number_format($total, 0, ',', '.') ?>đ</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>