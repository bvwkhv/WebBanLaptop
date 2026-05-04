<?php
session_start();
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thanh toán đơn hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 15px; }
        .form-label { font-weight: bold; }
        .error-text { font-size: 0.85rem; color: #dc3545; display: none; }
        
        /* CSS cho khu vực QR */
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
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <div class="row">
            <div class="col-md-7">
                <div class="card shadow-sm p-4 border-0">
                    <h4 class="mb-4 text-primary">Thông tin giao hàng</h4>
                    
                    <form action="confirmation.php" method="POST" id="checkoutForm">
                        <div class="mb-3">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" name="fullname" id="fullname" class="form-control save-cb" 
                                   placeholder="Nguyễn Văn A" maxlength="100" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control save-cb" 
                                   placeholder="name@example.com" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" name="phone" id="phoneInput" class="form-control save-cb" 
                                   placeholder="0901234567" required>
                            <div id="phoneError" class="error-text mt-1">
                                Số điện thoại phải là số và có độ dài từ 10 đến 11 ký tự.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Địa chỉ nhận hàng</label>
                            <textarea name="address" id="address" class="form-control save-cb" rows="2" 
                                      maxlength="250" placeholder="Số nhà, tên đường..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ghi chú đơn hàng</label>
                            <textarea name="note" id="note" class="form-control save-cb" rows="2" 
                                      maxlength="500" placeholder="Ví dụ: Giao giờ hành chính..."></textarea>
                        </div>

                        <h5 class="mt-4">Phương thức thanh toán</h5>
                        <div class="form-check">
                            <input class="form-check-input save-cb" type="radio" name="payment_method" id="payment_cod" value="COD" checked onclick="toggleQR(false)">
                            <label class="form-check-label" for="payment_cod">Thanh toán khi nhận hàng (COD)</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input save-cb" type="radio" name="payment_method" id="payment_bank" value="Chuyển khoản" onclick="toggleQR(true)">
                            <label class="form-check-label" for="payment_bank">Chuyển khoản ngân hàng (VietQR)</label>
                        </div>

                        <!-- KHU VỰC HIỂN THỊ MÃ QR -->
                        <?php
                            $total = 0;
                            foreach($_SESSION['cart'] as $item) { $total += $item['price'] * $item['qty']; }
                            
                            // Cấu hình ngân hàng (Bạn thay đổi thông tin ở đây)
                            $BANK_ID = "MB"; // Ví dụ: VCB, MB, TCB, ICB...
                            $ACCOUNT_NO = "0977960916"; // Số tài khoản nhận tiền
                            $ACCOUNT_NAME = "NGUYEN VAN TUAN"; // Tên chủ tài khoản (không dấu)
                            $DESCRIPTION = "THANH TOAN DON HANG " . time();
                            
                            $qr_url = "https://img.vietqr.io/image/$BANK_ID-$ACCOUNT_NO-compact2.png?amount=$total&addInfo=" . urlencode($DESCRIPTION) . "&accountName=" . urlencode($ACCOUNT_NAME);
                        ?>
                        <div id="qrCodeContainer" class="mb-4 shadow-sm">
                            <p class="mb-2 small text-muted">Quét mã VietQR để thanh toán: <b><?= number_format($total, 0, ',', '.') ?>đ</b></p>
                            <img src="<?= $qr_url ?>" alt="QR Code">
                            <div class="mt-2 small text-start ps-3 d-inline-block">
                                <div><b>Ngân hàng:</b> <?= $BANK_ID ?></div>
                                <div><b>Số TK:</b> <?= $ACCOUNT_NO ?></div>
                                <div><b>Chủ TK:</b> <?= $ACCOUNT_NAME ?></div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm mt-3" id="btnSubmit">Xác nhận đặt hàng</button>
                    </form>
                </div>
            </div>
            
            <div class="col-md-5 mt-4 mt-md-0">
                <div class="card shadow-sm p-4 border-0">
                    <h4 class="mb-3">Đơn hàng của bạn</h4>
                    <ul class="list-group mb-3">
                        <?php 
                        foreach($_SESSION['cart'] as $item): 
                            $subtotal = $item['price'] * $item['qty'];
                        ?>
                        <li class="list-group-item d-flex justify-content-between lh-sm">
                            <div>
                                <h6 class="my-0"><?= htmlspecialchars($item['name']) ?></h6>
                                <small class="text-muted">Số lượng: <?= $item['qty'] ?></small>
                            </div>
                            <span class="text-muted"><?= number_format($subtotal, 0, ',', '.') ?>đ</span>
                        </li>
                        <?php endforeach; ?>
                        <li class="list-group-item d-flex justify-content-between bg-light">
                            <span class="text-danger fw-bold">Tổng cộng</span>
                            <strong class="text-danger fs-5"><?= number_format($total, 0, ',', '.') ?>đ</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
    const formId = "checkoutForm"; 
    const saveInputs = document.querySelectorAll(".save-cb");

    // Hàm ẩn/hiện mã QR
    function toggleQR(show) {
        const qrContainer = document.getElementById('qrCodeContainer');
        qrContainer.style.display = show ? 'block' : 'none';
    }

    window.onload = () => {
        // Khôi phục dữ liệu đã nhập
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

    // Lưu dữ liệu khi nhập
    saveInputs.forEach(input => {
        input.addEventListener("input", () => {
            if (input.type === 'radio') {
                const checkedRadio = document.querySelector(`input[name="${input.name}"]:checked`);
                localStorage.setItem(`${formId}_${input.name}`, checkedRadio.value);
            } else {
                localStorage.setItem(`${formId}_${input.name}`, input.value);
            }
        });
    });

    // Kiểm tra phone và xóa local khi submit
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        const phone = document.getElementById('phoneInput').value.trim();
        const isNumeric = /^\d+$/.test(phone);
        
        if (!isNumeric || phone.length < 10 || phone.length > 11) {
            e.preventDefault();
            document.getElementById('phoneError').style.display = 'block';
            document.getElementById('phoneInput').classList.add('is-invalid');
        } else {
            saveInputs.forEach(input => {
                localStorage.removeItem(`${formId}_${input.name}`);
            });
        }
    });
    </script>
</body>
</html>