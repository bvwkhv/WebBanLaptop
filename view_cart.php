<?php
session_start();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Giỏ hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h2 class="mb-4">Giỏ hàng của bạn</h2>
        
        <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
            <table class="table table-white table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Ảnh</th>
                        <th>Sản phẩm</th>
                        <th>Giá</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
    <?php 
    $total_all = 0;
    foreach ($_SESSION['cart'] as $id => $item): 
        $subtotal = $item['price'] * $item['qty'];
        $total_all += $subtotal;
    ?>
    <tr>
        <td><img src="image/<?= $item['image'] ?>" width="60"></td>
        <td><strong><?= $item['name'] ?></strong></td>
        <td><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
        
        <td>
            <form action="update_cart.php" method="POST" class="d-flex align-items-center">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="number" name="qty" value="<?= $item['qty'] ?>" min="1" 
                       class="form-control form-control-sm text-center" style="width: 70px;">
                <button type="submit" class="btn btn-sm btn-outline-primary ms-2">Sửa</button>
            </form>
        </td>

        <td class="text-danger fw-bold"><?= number_format($subtotal, 0, ',', '.') ?>đ</td>
        <td>
            <a href="remove_cart.php?id=<?= $id ?>" 
               class="btn btn-sm btn-outline-danger" 
               onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">Xóa</a>
        </td>
    </tr>
    <?php endforeach; ?>
    
    <tr class="table-secondary text-end">
        <td colspan="4"><strong>Tổng cộng:</strong></td>
        <td colspan="2"><h4 class="text-danger mb-0"><?= number_format($total_all, 0, ',', '.') ?> VNĐ</h4></td>
    </tr>
</tbody>
            </table>
            <div class="d-flex justify-content-between">
                <a href="index.php" class="btn btn-secondary">Tiếp tục mua sắm</a>
                <a href="checkout.php" class="btn btn-success btn-lg">Tiến hành thanh toán</a>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">Giỏ hàng đang trống! <a href="index.php">Quay lại mua sắm</a></div>
        <?php endif; ?>
    </div>
</body>
</html>