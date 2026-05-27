<?php
session_start();
require_once "database.php";
$db = new Database();

// Kiểm tra nếu giỏ hàng trống thì không cho thanh toán, đẩy về trang chủ
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}

// 1. TÍNH TỔNG TIỀN HÀNG GỐC (Chưa qua giảm giá)
$total_raw = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_raw += $item['price'] * $item['qty'];
}

// 2. XỬ LÝ MÃ KHUYẾN MÃI (Nếu được truyền qua URL ?use_promo=ID)
$discount_amount = 0;
$promo_name = "";
if (isset($_GET['use_promo'])) {
    $promo_id = $_GET['use_promo'];
    // Chỉ lấy khuyến mãi đang kích hoạt (status = 1)
    $res = $db->select("SELECT * FROM promotions WHERE promotion_id = ? AND status = 1", "i", [$promo_id]);

    if (!empty($res)) {
        $promo = $res[0];
        $promo_name = $promo['name'];
        // Tính tiền giảm theo phần trăm hoặc số tiền cố định
        if ($promo['discount_type'] == 'percent') {
            $discount_amount = ($total_raw * $promo['discount_percent']) / 100;
        } else {
            $discount_amount = $promo['discount_percent'];
        }
    }
}
// Số tiền cuối cùng cần trả (không được âm)
$final_total = max(0, $total_raw - $discount_amount);

// 3. CẤU HÌNH NGÂN HÀNG VÀ TẠO MÃ VIETQR TỰ ĐỘNG
$BANK_ID = "MB";
$ACCOUNT_NO = "0977960916";
$ACCOUNT_NAME = "NGUYEN VAN TUAN";
$DESCRIPTION = "THANH TOAN DON HANG " . time();
// API VietQR lấy ảnh QR code dynamic theo số tiền cuối cùng
$qr_url = "https://img.vietqr.io/image/$BANK_ID-$ACCOUNT_NO-compact2.png?amount=$final_total&addInfo=" . urlencode($DESCRIPTION) . "&accountName=" . urlencode($ACCOUNT_NAME);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thanh toán đơn hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Inter', sans-serif;
        }

        .card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #dee2e6;
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
            border-color: #0d6efd;
        }

        /* Định dạng text hiển thị lỗi Validate Form */
        .error-text {
            font-size: 0.85rem;
            color: #dc3545;
            display: none;
        }

        /* Khối hiển thị ảnh QR chuyển khoản VietQR */
        #qrCodeContainer {
            display: none;
            text-align: center;
            background: #f8f9fa;
            border: 2px dashed #0d6efd;
            border-radius: 12px;
            padding: 20px;
            margin-top: 15px;
        }

        #qrCodeContainer img {
            max-width: 200px;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        /* Tùy biến nhóm chọn Phương thức thanh toán */
        .payment-method-group {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-radius: 12px;
            border: 1px solid #dee2e6;
        }

        /* THAY ĐỔI: Nút áp dụng ưu đãi thiết kế dạng Card mini đồng bộ layout, không bị lệch màu */
        .promo-box {
            background: white;
            border-radius: 16px;
            border: 1px dashed #198754;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        }

        .btn-apply-promo {
            background-color: #198754;
            color: white;
            font-weight: 600;
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 0.9rem;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-apply-promo:hover {
            background-color: #146c43;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container mt-5 mb-5">
        <div class="row g-4">
            
            <div class="col-lg-7">
                <div class="card p-4">
                    <h4 class="mb-4 fw-bold text-dark"><i class="bi bi-truck me-2 text-primary"></i>Thông tin giao hàng</h4>
                    
                    <form action="confirmation.php" method="POST" id="checkoutForm">
                        <input type="hidden" name="applied_promo_id" value="<?= $_GET['use_promo'] ?? '' ?>">
                        <input type="hidden" name="total_amount" value="<?= $final_total ?>">

                        <div class="mb-3">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" name="fullname" id="fullname" class="form-control save-cb" placeholder="Nhập đầy đủ họ tên" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email nhận thông báo</label>
                            <input type="email" name="email" id="email" class="form-control save-cb" placeholder="name@example.com" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" name="phone" id="phoneInput" class="form-control save-cb" placeholder="Ví dụ: 0901234567" required>
                            <div id="phoneError" class="error-text mt-1"><i class="bi bi-exclamation-circle me-1"></i>Số điện thoại phải chứa 10-11 ký tự số.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Địa chỉ nhận hàng</label>
                            <textarea name="address" id="address" class="form-control save-cb" rows="2" placeholder="Số nhà, tên đường, phường/xã, quận/huyện..." required></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Ghi chú đơn hàng (Tùy chọn)</label>
                            <textarea name="note" id="note" class="form-control save-cb" rows="2" placeholder="Lưu ý cho tài xế giao hàng..."></textarea>
                        </div>

                        <h5 class="mb-3 fw-bold text-dark"><i class="bi bi-wallet2 me-2 text-primary"></i>Phương thức thanh toán</h5>
                        <div class="payment-method-group mb-4">
                            <div class="form-check mb-2">
                                <input class="form-check-input save-cb" type="radio" name="payment_method" id="payment_cod" value="COD" checked onclick="toggleQR(false)">
                                <label class="form-check-label fw-medium" for="payment_cod">
                                    Thanh toán khi nhận hàng (COD)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input save-cb" type="radio" name="payment_method" id="payment_bank" value="Chuyển khoản" onclick="toggleQR(true)">
                                <label class="form-check-label fw-medium" for="payment_bank">
                                    Chuyển khoản tự động qua Chợ VietQR
                                </label>
                            </div>

                            <div id="qrCodeContainer" class="shadow-sm">
                                <p class="mb-2 small text-muted">Vui lòng quét mã để chuyển khoản chính xác: <b><?= number_format($final_total, 0, ',', '.') ?>đ</b></p>
                                <img src="<?= $qr_url ?>" alt="VietQR Đơn Hàng">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 py-3 rounded-3 fw-bold shadow-sm">
                            <i class="bi bi-bag-check-fill me-2"></i>XÁC NHẬN ĐẶT HÀNG
                        </button>
                    </form>
                </div>

                <div class="mt-4">
                    <div class="promo-box">
                        <div class="d-flex align-items-center gap-3">
                            <div class="fs-2 text-success"><i class="bi bi-ticket-perforated"></i></div>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark">Khuyến mãi & Ưu đãi</h6>
                                <p class="mb-0 text-muted small">Chọn hoặc nhập mã giảm giá để tối ưu chi phí</p>
                            </div>
                        </div>
                        <a href="select_promotion.php" class="btn-apply-promo shadow-sm">
                            Chọn Voucher
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card p-4 bg-white">
                    <h4 class="mb-4 fw-bold text-dark"><i class="bi bi-receipt me-2 text-primary"></i>Đơn hàng của bạn</h4>
                    
                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start px-0 bg-transparent py-3">
                                <div class="me-auto">
                                    <h6 class="my-0 fw-semibold text-dark small-title"><?= htmlspecialchars($item['name']) ?></h6>
                                    <small class="text-muted d-block mt-1">Số lượng: <?= $item['qty'] ?></small>
                                </div>
                                <span class="fw-medium text-secondary"><?= number_format($item['price'] * $item['qty'], 0, ',', '.') ?>đ</span>
                            </li>
                        <?php endforeach; ?>

                        <?php if ($discount_amount > 0): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent text-success py-3">
                                <div>
                                    <h6 class="my-0 fw-bold"><i class="bi bi-patch-check me-1"></i> Ưu đãi: <?= htmlspecialchars($promo_name) ?></h6>
                                    <a href="checkout.php" class="text-danger small mt-1 d-inline-block text-decoration-none fw-medium">[Bỏ áp dụng]</a>
                                </div>
                                <strong class="fs-6">-<?= number_format($discount_amount, 0, ',', '.') ?>đ</strong>
                            </li>
                        <?php endif; ?>

                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent pt-4 border-top">
                            <span class="text-dark fw-bold fs-5">Thành tiền</span>
                            <strong class="text-danger fs-4"><?= number_format($final_total, 0, ',', '.') ?>đ</strong>
                        </li>
                    </ul>
                </div>
            </div>
            
        </div>
    </div>

    <script>
        const formId = "checkoutForm";
        const saveInputs = document.querySelectorAll(".save-cb");

        // Hàm Ẩn / Hiện khung ảnh QR khi thay đổi Radio chuyển khoản
        function toggleQR(show) {
            document.getElementById('qrCodeContainer').style.display = show ? 'block' : 'none';
        }

        // Tự động khôi phục dữ liệu người dùng đã gõ trước đó từ LocalStorage nếu chưa submit đơn
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

        // Lắng nghe thao tác gõ của người dùng để lưu dữ liệu tạm tránh mất form khi load lại trang
        saveInputs.forEach(input => {
            input.addEventListener("input", () => {
                const val = input.type === 'radio' ? document.querySelector(`input[name="${input.name}"]:checked`).value : input.value;
                localStorage.setItem(`${formId}_${input.name}`, val);
            });
        });

        // Bắt sự kiện Submit đơn hàng, Validate số điện thoại phía Frontend bằng RegExp (10 - 11 số)
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const phone = document.getElementById('phoneInput').value.trim();
            if (!/^\d{10,11}$/.test(phone)) {
                e.preventDefault(); // Chặn hành vi gửi form lên server nếu sai định dạng SĐT
                document.getElementById('phoneError').style.display = 'block';
                document.getElementById('phoneInput').classList.add('is-invalid');
            } else {
                // Xóa bộ nhớ tạm LocalStorage sau khi hoàn tất đặt hàng thành công
                saveInputs.forEach(input => localStorage.removeItem(`${formId}_${input.name}`));
            }
        });
    </script>
</body>

</html>