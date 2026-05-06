<?php
session_start();
require_once "database.php";
$db = new Database();

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}

// 1. TÍNH TỔNG TIỀN HÀNG GỐC
$total_raw = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_raw += $item['price'] * $item['qty'];
}

// 2. XỬ LÝ KHUYẾN MÃI
$discount_amount = 0;
$promo_name = "";
if (isset($_GET['use_promo'])) {
    $promo_id = $_GET['use_promo'];
    $res = $db->select("SELECT * FROM promotions WHERE promotion_id = ? AND status = 1", "i", [$promo_id]);

    if (!empty($res)) {
        $promo = $res[0];
        $promo_name = $promo['name'];
        if ($promo['discount_type'] == 'percent') {
            $discount_amount = ($total_raw * $promo['discount_percent']) / 100;
        } else {
            $discount_amount = $promo['discount_percent'];
        }
    }
}
$final_total = max(0, $total_raw - $discount_amount);

// 3. CẤU HÌNH NGÂN HÀNG QR
$BANK_ID = "MB";
$ACCOUNT_NO = "0977960916";
$ACCOUNT_NAME = "NGUYEN VAN TUAN";
$DESCRIPTION = "THANH TOAN DON HANG " . time();
$qr_url = "https://img.vietqr.io/image/$BANK_ID-$ACCOUNT_NO-compact2.png?amount=$final_total&addInfo=" . urlencode($DESCRIPTION) . "&accountName=" . urlencode($ACCOUNT_NAME);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thanh toán đơn hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .card {
            border-radius: 15px;
        }

        .form-label {
            font-weight: bold;
        }

        .error-text {
            font-size: 0.85rem;
            color: #dc3545;
            display: none;
        }

        #qrCodeContainer {
            display: none;
            text-align: center;
            background: #fff;
            border: 2px dashed #0d6efd;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }

        #qrCodeContainer img {
            max-width: 220px;
            height: auto;
            border: 1px solid #eee;
        }

        .btn-promo {
            background: #ffb74d;
            border-radius: 20px;
            font-weight: bold;
            color: black;
            border: 1px solid #e69138;
            text-decoration: none;
            padding: 10px 20px;
            display: inline-block;
        }

        .btn-promo:hover {
            background: #ffa726;
            color: black;
        }
    </style>
</head>

<body>
    <div class="container mt-5 mb-5">
        <div class="row">
            <!-- CỘT TRÁI: THÔNG TIN GIAO HÀNG -->
            <div class="col-md-7">
                <div class="card shadow-sm p-4 border-0">
                    <h4 class="mb-4 text-primary">Thông tin giao hàng</h4>
                    <form action="confirmation.php" method="POST" id="checkoutForm">
                        <input type="hidden" name="applied_promo_id" value="<?= $_GET['use_promo'] ?? '' ?>">
                        <input type="hidden" name="total_amount" value="<?= $final_total ?>">

                        <div class="mb-3">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" name="fullname" id="fullname" class="form-control save-cb" placeholder="Nguyễn Văn A" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control save-cb" placeholder="name@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" name="phone" id="phoneInput" class="form-control save-cb" placeholder="0901234567" required>
                            <div id="phoneError" class="error-text mt-1">Số điện thoại không hợp lệ.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Địa chỉ nhận hàng</label>
                            <textarea name="address" id="address" class="form-control save-cb" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ghi chú đơn hàng</label>
                            <textarea name="note" id="note" class="form-control save-cb" rows="2"></textarea>
                        </div>

                        <h5 class="mt-4">Phương thức thanh toán</h5>
                        <div class="form-check">
                            <input class="form-check-input save-cb" type="radio" name="payment_method" id="payment_cod" value="COD" checked onclick="toggleQR(false)">
                            <label class="form-check-label" for="payment_cod">Thanh toán khi nhận hàng (COD)</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input save-cb" type="radio" name="payment_method" id="payment_bank" value="Chuyển khoản" onclick="toggleQR(true)">
                            <label class="form-check-label" for="payment_bank">Chuyển khoản VietQR</label>
                        </div>

                        <div id="qrCodeContainer" class="mb-4 shadow-sm">
                            <p class="mb-2 small text-muted">Quét mã VietQR: <b><?= number_format($final_total, 0, ',', '.') ?>đ</b></p>
                            <img src="<?= $qr_url ?>" alt="QR Code">
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm mt-3">Xác nhận đặt hàng</button>
                    </form>
                </div>

                <div class="mt-4">
                    <a href="select_promotion.php" class="btn-promo shadow-sm">
                        🎟 Áp dụng ưu đãi và giảm giá
                    </a>
                </div>
            </div>

            <!-- CỘT PHẢI: TÓM TẮT ĐƠN HÀNG -->
            <div class="col-md-5">
                <div class="card shadow-sm p-4 border-0">
                    <h4 class="mb-3">Đơn hàng của bạn</h4>
                    <ul class="list-group mb-3">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <li class="list-group-item d-flex justify-content-between lh-sm">
                                <div>
                                    <h6 class="my-0"><?= htmlspecialchars($item['name']) ?></h6>
                                    <small class="text-muted">SL: <?= $item['qty'] ?></small>
                                </div>
                                <span class="text-muted"><?= number_format($item['price'] * $item['qty'], 0, ',', '.') ?>đ</span>
                            </li>
                        <?php endforeach; ?>

                        <?php if ($discount_amount > 0): ?>
                            <li class="list-group-item d-flex justify-content-between bg-light text-success align-items-center">
                                <div>
                                    <h6 class="my-0">Ưu đãi: <?= htmlspecialchars($promo_name) ?></h6>
                                    <!-- Nút Bỏ áp dụng: Nó sẽ dẫn link về lại checkout.php mà không có tham số use_promo -->
                                    <a href="checkout.php" class="text-danger small" style="text-decoration: none;">[Bỏ áp dụng]</a>
                                </div>
                                <strong>-<?= number_format($discount_amount, 0, ',', '.') ?>đ</strong>
                            </li>
                        <?php endif; ?>

                        <li class="list-group-item d-flex justify-content-between bg-light">
                            <span class="text-danger fw-bold">Tổng cộng</span>
                            <strong class="text-danger fs-5"><?= number_format($final_total, 0, ',', '.') ?>đ</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        const formId = "checkoutForm";
        const saveInputs = document.querySelectorAll(".save-cb");

        function toggleQR(show) {
            document.getElementById('qrCodeContainer').style.display = show ? 'block' : 'none';
        }

        window.onload = () => {
            saveInputs.forEach(input => {
                const savedValue = localStorage.getItem(`${formId}_${input.name}`);
                if (savedValue) {
                    if (input.type === 'radio') {
                        if (input.value === savedValue) {
                            input.checked = true;
                            if (input.id === 'payment_bank') toggleQR(true);
                        }
                    } else {
                        input.value = savedValue;
                    }
                }
            });
        };

        saveInputs.forEach(input => {
            input.addEventListener("input", () => {
                const val = input.type === 'radio' ? document.querySelector(`input[name="${input.name}"]:checked`).value : input.value;
                localStorage.setItem(`${formId}_${input.name}`, val);
            });
        });

        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const phone = document.getElementById('phoneInput').value.trim();
            if (!/^\d{10,11}$/.test(phone)) {
                e.preventDefault();
                document.getElementById('phoneError').style.display = 'block';
                document.getElementById('phoneInput').classList.add('is-invalid');
            } else {
                saveInputs.forEach(input => localStorage.removeItem(`${formId}_${input.name}`));
            }
        });
    </script>
</body>

</html>