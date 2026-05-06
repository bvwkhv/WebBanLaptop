<?php
session_start();
require_once "database.php";
$db = new Database();

if (isset($_SESSION['user_id']) && !empty($_SESSION['cart'])) {
    $user_id = $_SESSION['user_id'];
    
    foreach ($_SESSION['cart'] as $id_sp => $item) {
        // Sử dụng event_type, target_id và created_at cho đúng bảng
        $sql_track = "INSERT INTO event_tracking (user_id, event_type, target_id, created_at) 
                      VALUES (?, ?, ?, NOW())";
        
        // Truyền tham số: i (user_id), s (event_type), i (target_id)
        $db->execute($sql_track, 'isi', [
            $user_id, 
            'Xem giỏ hàng', // Nội dung event_type
            $id_sp          // ID sản phẩm đóng vai trò là target_id
        ]);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Giỏ hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Giữ nguyên phần HTML bên dưới của bạn -->
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
                    <td><img src="image/<?= htmlspecialchars($item['image']) ?>" width="60"></td>
                    <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
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