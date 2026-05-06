<?php
session_start();
require_once "database.php"; // Cần để truy vấn tên mã giảm giá
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_SESSION['cart'])) {
    header("Location: checkout.php");
    exit();
}

// Lấy dữ liệu từ Form nhập liệu
$d = $_POST; 

// Lấy thông tin mã giảm giá để hiển thị tên
$promo_name = "";
if (!empty($d['applied_promo_id'])) {
    $res = $db->select("SELECT name FROM promotions WHERE promotion_id = ?", "i", [$d['applied_promo_id']]);
    if (!empty($res)) {
        $promo_name = $res[0]['name'];
    }
}

// Tổng tiền hàng chưa giảm
$total_raw = 0;
foreach($_SESSION['cart'] as $item) {
    $total_raw += $item['price'] * $item['qty'];
}

// Số tiền giảm giá
$final_total = isset($d['total_amount']) ? (float)$d['total_amount'] : $total_raw;
$discount_amount = $total_raw - $final_total;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Xác nhận đơn hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5 mb-5">
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
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th class="text-end">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($_SESSION['cart'] as $item): 
                                    $sub = $item['price'] * $item['qty']; ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['name']) ?> (x<?= $item['qty'] ?>)</td>
                                        <td class="text-end"><?= number_format($sub, 0, ',', '.') ?>đ</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td class="text-end">Tạm tính:</td>
                                    <td class="text-end"><?= number_format($total_raw, 0, ',', '.') ?>đ</td>
                                </tr>
                                <?php if ($discount_amount > 0): ?>
                                    <tr class="text-success">
                                        <td class="text-end">Giảm giá (<?= htmlspecialchars($promo_name) ?>):</td>
                                        <td class="text-end">-<?= number_format($discount_amount, 0, ',', '.') ?>đ</td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="fs-5 fw-bold text-danger">
                                    <td class="text-end">Tổng cộng:</td>
                                    <td class="text-end"><?= number_format($final_total, 0, ',', '.') ?>đ</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <hr>

                <form action="process_checkout.php" method="POST">
                    <!-- Truyền toàn bộ dữ liệu cũ sang trang xử lý lưu DB -->
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