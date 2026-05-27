<?php
// Bắt đầu phiên làm việc để quản lý quyền đăng nhập của Admin
session_start();

// Kiểm tra quyền hạn: Nếu không phải Admin, chặn quyền truy cập và điều hướng về trang chủ
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Nhúng cấu hình kết nối cơ sở dữ liệu
require_once "database.php";
$db = new Database();

// Lấy ID từ URL (nếu có -> Chế độ CẬP NHẬT, nếu không -> Chế độ THÊM MỚI)
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$promo = null;
$errors = []; // Mảng chứa các thông báo lỗi nếu Validate thất bại

// Nếu có ID, thực hiện truy vấn lấy thông tin dữ liệu khuyến mãi cũ để đổ vào Form
if ($id) {
    // Kiểm tra tính hợp lệ của ID (Ràng buộc: ID phải từ 1 đến 50)
    if ($id < 1 || $id > 50) {
        header("Location: admin_promotions.php");
        exit();
    }
    $res = $db->select("SELECT * FROM promotions WHERE promotion_id = ?", "i", [$id]);
    $promo = $res[0] ?? null;
    
    if (!$promo) {
        header("Location: admin_promotions.php");
        exit();
    }
}

// XỬ LÝ KHI NGƯỜI DÙNG BẤM NÚT LƯU (SUBMIT FORM)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy và làm sạch dữ liệu đầu vào bằng trim()
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['discount_type'] ?? 'percent');
    $value = isset($_POST['discount_value']) ? (float)$_POST['discount_value'] : 0;
    $start = trim($_POST['start_date'] ?? '');
    $end = trim($_POST['end_date'] ?? '');
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

    // Đổ ngược dữ liệu vừa POST vào mảng $promo để giữ lại giá trị trên form nếu dính lỗi Validation
    $promo = [
        'name' => $name,
        'discount_type' => $type,
        'discount_percent' => $value,
        'start_date' => $start,
        'end_date' => $end,
        'status' => $status
    ];

    // --- HỆ THỐNG RÀNG BUỘC DỮ LIỆU BACKEND (VALIDATION) ---
    
    // 1. Kiểm tra Tên chương trình khuyến mãi (Min 1 - Max 255 ký tự, không trống)
    if (empty($name)) {
        $errors[] = "Tên chương trình khuyến mãi không được phép để trống.";
    } elseif (mb_strlen($name, 'UTF-8') > 255) {
        $errors[] = "Tên chương trình khuyến mãi không được vượt quá 255 ký tự.";
    }

    // 2. Kiểm tra tính trùng lặp của Tên chương trình khuyến mãi trong Database
    if (!$id) {
        // Chế độ thêm mới: Tên không được trùng với bất kỳ bản ghi nào cũ
        $check_duplicate = $db->select("SELECT * FROM promotions WHERE name = ?", "s", [$name]);
    } else {
        // Chế độ sửa: Tên không được trùng với các bản ghi của ID khác
        $check_duplicate = $db->select("SELECT * FROM promotions WHERE name = ? AND promotion_id != ?", "si", [$name, $id]);
    }
    if (!empty($check_duplicate)) {
        $errors[] = "Tên chương trình khuyến mãi này đã tồn tại, vui lòng chọn tên khác.";
    }

    // 3. Kiểm tra Loại giảm giá
    if ($type !== 'percent' && $type !== 'amount') {
        $errors[] = "Loại giảm giá không hợp lệ (Chỉ chấp nhận phần trăm hoặc số tiền).";
    }

    // 4. Kiểm tra Giá trị giảm giá hợp lệ (Min > 0, nếu là phần trăm thì Max 100)
    if ($value <= 0) {
        $errors[] = "Giá trị giảm phải lớn hơn 0.";
    } elseif ($type === 'percent' && $value > 100) {
        $errors[] = "Giá trị giảm theo phần trăm không được phép vượt quá 100%.";
    }

    // 5. Kiểm tra Thời gian (Ngày bắt đầu bắt buộc, Ngày kết thúc > Ngày bắt đầu)
    if (empty($start)) {
        $errors[] = "Ngày bắt đầu chương trình là bắt buộc.";
    }
    if (empty($end)) {
        $errors[] = "Ngày kết thúc chương trình là bắt buộc.";
    }
    if (!empty($start) && !empty($end) && strtotime($end) <= strtotime($start)) {
        $errors[] = "Ngày kết thúc phải lớn hơn ngày bắt đầu chương trình.";
    }

    // --- THỰC THI QUY TRÌNH LƯU VÀO DATABASE NẾU KHÔNG CÓ LỖI ---
    if (empty($errors)) {
        if ($id) {
            // SỬA: Cập nhật bản ghi hiện tại
            $sql = "UPDATE promotions SET name=?, discount_type=?, discount_percent=?, start_date=?, end_date=?, status=? WHERE promotion_id=?";
            $db->execute($sql, "ssdssii", [$name, $type, $value, $start, $end, $status, $id]);
        } else {
            // THÊM MỚI: Ràng buộc số lượng ID trong hệ thống (Max 50 mã khuyến mãi)
            $count_res = $db->select("SELECT COUNT(*) as total FROM promotions");
            $total_promo = $count_res[0]['total'] ?? 0;

            if ($total_promo >= 50) {
                $errors[] = "Hệ thống đã đạt giới hạn tối đa 50 mã khuyến mãi. Không thể thêm mới.";
            } else {
                $sql = "INSERT INTO promotions (name, discount_type, discount_percent, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?)";
                $db->execute($sql, "ssdssi", [$name, $type, $value, $start, $end, $status]);
            }
        }

        // Nếu quá trình lưu thành công và không phát sinh lỗi giới hạn, quay lại trang quản lý danh sách
        if (empty($errors)) {
            header("Location: admin_promotions.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? "Sửa" : "Thêm" ?> Khuyến Mãi - Laptop Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        /* Thiết lập giao diện đồng bộ màu kem nhạt */
        body { 
            background-color: #fdfae6; 
            font-family: 'Inter', sans-serif; 
            color: #333;
        }
        .form-container { 
            max-width: 850px; 
            margin: 40px auto; 
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #f1f5f9;
        }
        .title { 
            font-weight: 800; 
            letter-spacing: -0.5px; 
            margin-bottom: 35px; 
            color: #1e293b;
        }
        label { 
            font-weight: 600; 
            color: #475569;
            font-size: 0.92rem;
        }
        
        /* Đổi input sang kiểu viền nhẹ bo góc bo tròn hiện đại thay vì border phẳng vuông */
        .form-control, .form-select { 
            border-radius: 8px; 
            border: 1px solid #cbd5e1; 
            padding: 10px 16px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #ffb74d;
            box-shadow: 0 0 0 3px rgba(255, 183, 77, 0.15);
        }
        
        /* Thiết kế nút bấm hành động */
        .btn-custom { 
            border-radius: 25px; 
            padding: 10px 45px; 
            font-weight: 700; 
            font-size: 0.95rem;
            transition: all 0.2s; 
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-save { 
            background: #ffb74d; 
            color: #4a321a; 
            border: 2px solid #ffb74d; 
        }
        .btn-save:hover { 
            background: transparent;
            color: #ffb74d;
            transform: translateY(-2px); 
        }
        .btn-cancel { 
            background: #f1f5f9; 
            color: #64748b; 
            border: 2px solid #f1f5f9;
        }
        .btn-cancel:hover { 
            background: transparent;
            color: #64748b;
            border-color: #cbd5e1;
            transform: translateY(-2px); 
        }

        .radio-card-group { 
            display: flex; 
            gap: 20px; 
        }
        .radio-card {
            border: 1px solid #cbd5e1;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }
        .radio-card input {
            accent-color: #fb8c00;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="form-container">
        <h2 class="title text-center text-uppercase"><?= $id ? "<i class='fa-regular fa-pen-to-square'></i> Cập nhật khuyến mãi" : "<i class='fa-solid fa-plus'></i> Tạo mới khuyến mãi" ?></h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
                <ul class="mb-0 py-1">
                    <?php foreach ($errors as $error): ?>
                        <li class="small fw-semibold"><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?> </ul>
            </div>
        <?php endif; ?>

        <form method="POST" id="promoForm" onsubmit="return validateForm()">
            <div class="row g-4">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label">Mã khuyến mãi (Hệ thống tự định danh):</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-hashtag"></i></span>
                            <input type="text" class="form-control bg-light border-start-0 fw-bold text-secondary" 
                                   value="<?= $id ? 'KM' . str_pad($id, 2, '0', STR_PAD_LEFT) : '' ?>" readonly placeholder="Mã tăng tự động từ KM01 - KM50...">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tên chương trình khuyến mãi <span class="text-danger">*</span>:</label>
                        <input type="text" name="name" id="name" class="form-control" 
                               value="<?= htmlspecialchars($promo['name'] ?? '') ?>" 
                               placeholder="Ví dụ: Khuyến mãi Chào Hè 2026" required>
                        <div class="form-text small text-muted">Độ dài từ 1 đến 255 ký tự và không được trùng lặp tên.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block mb-2">Loại hình giảm giá <span class="text-danger">*</span>:</label>
                        <div class="radio-card-group">
                            <label class="radio-card">
                                <input type="radio" name="discount_type" value="percent" id="typePercent" 
                                    <?= ($promo['discount_type'] ?? 'percent') === 'percent' ? 'checked' : '' ?> onchange="updateValueConstraint()">
                                <span><i class="fa-solid fa-percent text-muted me-1"></i> Phần trăm (%)</span>
                            </label>
                            <label class="radio-card">
                                <input type="radio" name="discount_type" value="amount" id="typeAmount" 
                                    <?= ($promo['discount_type'] ?? '') === 'amount' ? 'checked' : '' ?> onchange="updateValueConstraint()">
                                <span><i class="fa-solid fa-money-bill-wave text-muted me-1"></i> Số tiền (đ)</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Giá trị chiết khấu giảm giá <span class="text-danger">*</span>:</label>
                        <input type="number" step="any" name="discount_value" id="discount_value" class="form-control" 
                               value="<?= htmlspecialchars($promo['discount_percent'] ?? '') ?>" 
                               placeholder="Nhập giá trị số lớn hơn 0..." required>
                        <div id="valueHelp" class="form-text small text-muted">Nếu chọn Phần trăm, giá trị nhập phải từ 0.01 đến 100.</div>
                    </div>

                    <div class="row row-cols-1 row-cols-sm-2 g-3">
                        <div>
                            <label class="form-label">Ngày kích hoạt bắt đầu <span class="text-danger">*</span>:</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" 
                                   value="<?= isset($promo['start_date']) ? date('Y-m-d', strtotime($promo['start_date'])) : '' ?>" required>
                        </div>
                        <div>
                            <label class="form-label">Ngày đáo hạn kết thúc <span class="text-danger">*</span>:</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" 
                                   value="<?= isset($promo['end_date']) ? date('Y-m-d', strtotime($promo['end_date'])) : '' ?>" required>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 border-start ps-4">
                    <label class="form-label d-block mb-3"><i class="fa-solid fa-toggle-on text-secondary me-1"></i> Trạng thái hoạt động:</label>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="status" id="statusActive" value="1" <?= ($promo['status'] ?? 1) == 1 ? 'checked' : '' ?>>
                        <label class="form-check-label text-success fw-semibold" for="statusActive">
                            Cho phép áp dụng
                        </label>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="statusInactive" value="0" <?= ($promo['status'] ?? 1) == 0 ? 'checked' : '' ?>>
                        <label class="form-check-label text-danger fw-semibold" for="statusInactive">
                            Tạm thời tắt/Dừng
                        </label>
                    </div>
                    
                    <div class="mt-4 p-3 bg-light rounded-3 border">
                        <small class="text-muted d-block lh-sm"><i class="fa-solid fa-circle-info me-1"></i> <strong>Lưu ý quy tắc:</strong> Tổng số lượng mã khuyến mãi lưu trữ trong hệ thống Database tối đa không được vượt quá <strong>50 mã</strong>.</small>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-center gap-3 mt-5 pt-3 border-top">
                <button type="submit" class="btn btn-custom btn-save shadow-sm"><i class="fa-regular fa-floppy-disk"></i> Lưu dữ liệu</button>
                <a href="admin_promotions.php" class="btn btn-custom btn-cancel shadow-sm text-decoration-none"><i class="fa-solid fa-xmark"></i> Hủy bỏ</a>
            </div>
        </form>
    </div>
</div>

<script>
// Hàm tự động cập nhật gợi ý ràng buộc khi Admin thay đổi nút chọn Loại giảm giá (%) hoặc (đ)
function updateValueConstraint() {
    const isPercent = document.getElementById('typePercent').checked;
    const valueHelp = document.getElementById('valueHelp');
    const valueInput = document.getElementById('discount_value');

    if (isPercent) {
        valueHelp.innerText = "Nếu chọn Phần trăm, giá trị nhập phải lớn hơn 0 và tối đa là 100%.";
        valueInput.setAttribute('max', '100');
    } else {
        valueHelp.innerText = "Nếu chọn Số tiền, giá trị nhập phải lớn hơn 0đ (không giới hạn trần).";
        valueInput.removeAttribute('max');
    }
}

// Chạy khởi tạo kiểm tra loại giảm giá ngay khi load trang để hiển thị đúng thiết lập max của thẻ input
document.addEventListener("DOMContentLoaded", function() {
    updateValueConstraint();
});

// HÀM CHÍNH: VALIDATE DỮ LIỆU TRƯỚC KHI SUBMIT FORM (LƯU)
function validateForm() {
    const name = document.getElementById('name').value.trim();
    const isPercent = document.getElementById('typePercent').checked;
    const discountValue = parseFloat(document.getElementById('discount_value').value);
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    // 1. Kiểm tra trống và độ dài tên khuyến mãi
    if (name === "") {
        alert("Vui lòng nhập tên chương trình khuyến mãi.");
        return false;
    }
    if (name.length > 255) {
        alert("Tên chương trình khuyến mãi không được dài quá 255 ký tự.");
        return false;
    }

    // 2. Kiểm tra giá trị giảm giá hợp lệ
    if (isNaN(discountValue) || discountValue <= 0) {
        alert("Giá trị giảm giá phải là số và lớn hơn 0.");
        return false;
    }
    // Ràng buộc riêng cho loại Phần trăm (%) không được lớn hơn 100
    if (isPercent && discountValue > 100) {
        alert("Giá trị giảm theo phần trăm không được phép vượt quá 100%.");
        return false;
    }

    // 3. Kiểm tra tính hợp lệ của mốc thời gian diễn ra sự kiện
    if (!startDate) {
        alert("Vui lòng chọn ngày bắt đầu chương trình.");
        return false;
    }
    if (!endDate) {
        alert("Vui lòng chọn ngày kết thúc chương trình.");
        return false;
    }
    
    const startTimestamp = new Date(startDate).getTime();
    const endTimestamp = new Date(endDate).getTime();

    if (endTimestamp <= startTimestamp) {
        alert("Ngày kết thúc chương trình phải lớn hơn ngày bắt đầu.");
        return false;
    }

    return true; // Cho phép gửi form đi nếu vượt qua tất cả các chốt kiểm tra dữ liệu
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>