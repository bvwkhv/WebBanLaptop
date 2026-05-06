<?php
session_start();
require_once "database.php";
$db = new Database();

// Lấy từ khóa tìm kiếm nếu có
$search = $_GET['search'] ?? '';
$now = date('Y-m-d');

$sql = "SELECT * FROM promotions WHERE status = 1 AND ? BETWEEN start_date AND end_date";
$params = [$now];

if ($search) {
    $sql .= " AND (name LIKE ? OR promotion_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$promotions = $db->select($sql, "s" . ($search ? "ss" : ""), $params);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chọn Khuyến Mãi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #fdfae6; font-family: 'Segoe UI', sans-serif; }
        .container-box { max-width: 700px; margin: 30px auto; }
        .header-title { text-align: center; font-weight: 800; letter-spacing: 2px; margin-bottom: 20px; text-transform: uppercase; }
        
        /* Thanh tìm kiếm */
        .search-box { background: white; border: 1px solid #ddd; padding: 5px 15px; display: flex; align-items: center; margin-bottom: 30px; }
        .search-box input { border: none; outline: none; width: 100%; padding: 8px; }
        .search-btn { background: #64b5f6; border: none; padding: 5px 10px; border-radius: 5px; color: white; }

        /* Item khuyến mãi */
        .promo-item { border-bottom: 1px solid #ccc; padding: 20px 0; position: relative; }
        .promo-name { font-weight: bold; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .promo-name::before { content: "🎫"; font-size: 1rem; }
        .promo-info { color: #555; font-size: 0.95rem; margin-left: 30px; margin-top: 5px; }
        
        /* Nút áp dụng/đóng */
        .btn-apply { 
            background: #ffb74d; border: 1px solid #e69138; border-radius: 15px; 
            padding: 5px 30px; font-weight: bold; margin-top: 10px; margin-left: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-close-page { 
            background: #ffb74d; border: 1px solid #e69138; border-radius: 20px; 
            padding: 8px 60px; font-weight: bold; margin-top: 40px;
        }
    </style>
</head>
<body>

<div class="container container-box">
    <h2 class="header-title">CHỌN KHUYẾN MÃI</h2>
    <hr>

    <!-- Thanh tìm kiếm -->
    <form action="" method="GET" class="search-box shadow-sm">
        <input type="text" name="search" placeholder="Tìm mã khuyến mãi..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="search-btn">🔍</button>
    </form>

    <!-- Danh sách khuyến mãi -->
    <div class="promo-list">
        <?php if (empty($promotions)): ?>
            <p class="text-center mt-4">Không tìm thấy khuyến mãi nào phù hợp.</p>
        <?php else: ?>
            <?php foreach ($promotions as $p): ?>
                <div class="promo-item">
                    <div class="promo-name text-uppercase">
                        <?= $p['name'] ?> - Giảm <?= $p['discount_type'] == 'percent' ? $p['discount_percent'].'%' : number_format($p['discount_percent'], 0, ',', '.').'đ' ?>
                    </div>
                    <div class="promo-info">
                        Áp dụng cho: Mọi sản phẩm <br>
                        HSD: <?= date('d/m', strtotime($p['start_date'])) ?> - <?= date('d/m', strtotime($p['end_date'])) ?>
                    </div>
                    <a href="checkout.php?use_promo=<?= $p['promotion_id'] ?>" class="btn btn-apply">Áp dụng</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Nút Đóng -->
    <div class="text-center">
        <a href="checkout.php" class="btn btn-close-page shadow-sm">Đóng</a>
    </div>
</div>

</body>
</html>