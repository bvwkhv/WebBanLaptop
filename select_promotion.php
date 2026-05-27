<?php
session_start();
require_once "database.php";
$db = new Database();

// Lấy từ khóa tìm kiếm từ thanh Search (nếu có), mặc định để rỗng
$search = $_GET['search'] ?? '';

// Lấy ngày hiện tại ở định dạng Y-m-d để so sánh thời hạn voucher
$now = date('Y-m-d');

// Câu lệnh SQL cơ bản: Chỉ lấy các mã đang kích hoạt (status = 1) và ngày hiện tại nằm trong hạn sử dụng
$sql = "SELECT * FROM promotions WHERE status = 1 AND ? BETWEEN start_date AND end_date";
$params = [$now];

// Nếu người dùng có nhập từ khóa tìm kiếm
if ($search) {
    // Nối thêm điều kiện tìm kiếm theo tên voucher hoặc theo mã ID voucher
    $sql .= " AND (name LIKE ? OR promotion_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Thực thi câu lệnh SQL với tập hợp tham số động thông qua hàm select của class Database
$promotions = $db->select($sql, "s" . ($search ? "ss" : ""), $params);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chọn Khuyến Mãi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            background-color: #f4f6f9; 
            font-family: 'Inter', sans-serif; 
        }
        
        .container-box { 
            max-width: 650px; 
            margin: 40px auto; 
        }

        /* Thanh tìm kiếm thiết kế bo tròn, đổ bóng nhẹ tinh tế */
        .search-box { 
            background: white; 
            border: 1px solid #dee2e6; 
            padding: 6px 12px; 
            display: flex; 
            align-items: center; 
            border-radius: 12px;
            margin-bottom: 25px; 
        }

        .search-box input { 
            border: none; 
            outline: none; 
            width: 100%; 
            padding: 8px; 
            font-size: 0.95rem;
        }

        .search-btn { 
            background: #0d6efd; 
            border: none; 
            padding: 8px 16px; 
            border-radius: 8px; 
            color: white; 
            transition: 0.2s;
        }

        .search-btn:hover {
            background: #0b5ed7;
        }

        /* Thiết kế giao diện item Khuyến mãi mô phỏng chiếc Vé Coupon phẳng hiện đại */
        .coupon-card {
            background: white;
            border-radius: 14px;
            border: 1px solid #e0e0e0;
            display: flex;
            margin-bottom: 16px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
            position: relative;
        }

        /* Phần cánh trái của vé: Chứa biểu tượng và màu sắc nhận diện nổi bật */
        .coupon-left {
            background: linear-gradient(135deg, #198754, #157347);
            color: white;
            width: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            position: relative;
        }

        /* Tạo vết vát hình bán nguyệt ở góc nối (giả lập đường xé vé coupon) */
        .coupon-left::after {
            content: "";
            position: absolute;
            right: -6px;
            top: 50%;
            transform: translateY(-50%);
            width: 12px;
            height: 12px;
            background-color: #f4f6f9;
            border-radius: 50%;
        }

        /* Phần cánh phải của vé: Chứa thông tin chi tiết nội dung */
        .coupon-right {
            padding: 16px 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .promo-name { 
            font-weight: 700; 
            font-size: 1.1rem; 
            color: #212529;
        }

        .promo-info { 
            color: #6c757d; 
            font-size: 0.85rem; 
            margin-top: 4px;
            line-height: 1.4;
        }

        /* Nút áp dụng nằm ngay trong Card bên phải */
        .btn-apply { 
            background: #198754; 
            border: none;
            color: white;
            border-radius: 8px; 
            padding: 6px 18px; 
            font-weight: 600; 
            font-size: 0.85rem;
            transition: 0.2s;
        }

        .btn-apply:hover { 
            background: #146c43; 
            color: white;
        }

        /* Nút Đóng / Quay lại trang Checkout */
        .btn-close-page { 
            background: white; 
            border: 1px solid #dee2e6; 
            border-radius: 10px; 
            padding: 10px 40px; 
            font-weight: 600; 
            color: #495057;
            transition: 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-close-page:hover {
            background: #e9ecef;
            color: #212529;
        }
    </style>
</head>
<body>

<div class="container container-box">
    
    <div class="text-center mb-4">
        <h3 class="fw-bold text-dark text-uppercase tracking-wide">Chọn mã giảm giá</h3>
        <p class="text-muted small">Áp dụng mã ưu đãi phù hợp để tiết kiệm tối đa chi phí đơn hàng</p>
    </div>

    <form action="select_promotion.php" method="GET" class="search-box shadow-sm">
        <i class="bi bi-search text-muted ms-2 me-1"></i>
        <input type="text" name="search" placeholder="Nhập tên hoặc ký hiệu mã giảm giá..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="search-btn fw-medium">Tìm kiếm</button>
    </form>

    <div class="promo-list mt-2">
        <?php if (empty($promotions)): ?>
            <div class="text-center py-5 bg-white rounded-4 border">
                <div class="text-muted fs-1 mb-2"><i class="bi bi-ticket-detailed"></i></div>
                <p class="text-secondary mb-0">Hiện không có mã khuyến mãi nào khả dụng hoặc đã hết hạn.</p>
            </div>
        <?php else: ?>
            <?php foreach ($promotions as $p): ?>
                <div class="coupon-card">
                    
                    <div class="coupon-left">
                        <i class="bi bi-ticket-perforated-fill"></i>
                    </div>
                    
                    <div class="coupon-right">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="promo-name text-uppercase">
                                    <?= htmlspecialchars($p['name']) ?>
                                </div>
                                <div class="text-success fw-bold small mt-1">
                                    Mức giảm: <?= $p['discount_type'] == 'percent' ? $p['discount_percent'].'%' : number_format($p['discount_percent'], 0, ',', '.').'đ' ?>
                                </div>
                                <div class="promo-info">
                                    <i class="bi bi-info-circle me-1"></i>Áp dụng: Tất cả danh mục sản phẩm <br>
                                    <i class="bi bi-clock me-1"></i>Hạn dùng: <?= date('d/m/Y', strtotime($p['start_date'])) ?> đến <?= date('d/m/Y', strtotime($p['end_date'])) ?>
                                </div>
                            </div>
                            
                            <div class="align-self-center ps-2">
                                <a href="checkout.php?use_promo=<?= $p['promotion_id'] ?>" class="btn btn-apply shadow-sm">
                                    Áp dụng
                                </a>
                            </div>
                        </div>
                    </div>
                    
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="text-center mt-4">
        <a href="checkout.php" class="btn-close-page shadow-sm">
            <i class="bi bi-arrow-left me-2"></i>Quay lại đơn hàng
        </a>
    </div>
</div>

</body>
</html>