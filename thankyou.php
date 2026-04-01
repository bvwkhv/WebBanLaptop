<?php
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '---';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cảm ơn bạn đã đặt hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #fff4c5; }
        .thankyou-card {
            max-width: 600px;
            margin: 80px auto;
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            text-align: center;
        }
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        .order-number {
            background: #e9ecef;
            padding: 10px 20px;
            border-radius: 50px;
            display: inline-block;
            font-weight: bold;
            color: #495057;
            margin: 20px 0;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="thankyou-card">
        <i class="bi bi-check-circle-fill success-icon"></i>
        <h1 class="display-5 fw-bold">Đặt hàng thành công!</h1>
        <p class="text-muted">Cảm ơn bạn đã tin tưởng <strong>THN Store</strong>. <br> Đơn hàng của bạn đã được tiếp nhận và đang chờ xử lý.</p>
        
        <div class="order-number">
            Mã đơn hàng: #<?= htmlspecialchars($order_id) ?>
        </div>

        <div class="mt-4 d-flex justify-content-center gap-3">
            <a href="index.php" class="btn btn-outline-dark px-4 py-2 rounded-pill">
                Tiếp tục mua sắm
            </a>
            <a href="order_details.php?id=<?= $order_id ?>" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
                Xem chi tiết đơn
            </a>
        </div>
    </div>
</div>

</body>
</html>