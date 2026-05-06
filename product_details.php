<?php
    require_once "auth_check.php";
    require_once "database.php";
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $db = new Database();

    if (!isset($_GET["id"])) {
        header("Location: index.php");
        exit();
    }

    $id = $_GET["id"];

    $sql ="SELECT p.product_id, p.product_name, p.image_url, p.price, ps.cpu, ps.ram, ps.storage, ps.gpu, ps.screen 
        FROM products AS p 
        INNER JOIN product_specs AS ps ON p.product_id = ps.product_id 
        WHERE p.product_id = ?";

    $result = $db->select($sql, 'i', [$id]); 

    if (!empty($result)) {
        $products = $result[0];
    } else {
        die("Không tìm thấy sản phẩm!");
    }

    $can_review = false;
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        try {
            $check_sql = "SELECT o.order_id FROM orders o 
                        JOIN order_details oi ON o.order_id = oi.order_id 
                        WHERE o.user_id = ? AND oi.product_id = ? LIMIT 1";
            $purchase = $db->select($check_sql, 'ii', [$user_id, $id]);
            if (!empty($purchase)) {
                $can_review = true;
            }
        } catch (Exception $e) { $can_review = false; }
    }

    $reviews = $db->select("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.user_id WHERE r.product_id = ? ORDER BY r.created_at DESC", "i", [$id]);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($products["product_name"]) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-orange: #ffb74d;
            --price-red: #d70018;
            --bg-light: #f8f9fa;
        }

        body { 
            background-color: #fdfae6; 
            font-family: 'Inter', sans-serif;
            overflow-x: hidden; /* Ngăn chặn cuộn ngang toàn trang */
        }

        .navbar { background-color: var(--primary-orange) !important; }
        .nav-link { font-weight: 600; color: #000 !important; }

        .product-detail-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-top: 30px;
        }

        /* FIX TRÀN CHỮ TẠI ĐÂY */
        .product-title {
            font-weight: 800;
            font-size: calc(1.5rem + 1vw);
            line-height: 1.3;
            word-wrap: break-word; /* Ép xuống dòng */
            overflow-wrap: break-word;
            hyphens: auto;
            margin-bottom: 20px;
            color: #222;
        }

        .img-wrapper {
            background: var(--bg-light);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            position: sticky;
            top: 100px;
        }
        .img-wrapper img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
        }

        .price-box {
            background: #fff5f5;
            padding: 20px;
            border-radius: 15px;
            border-left: 5px solid var(--price-red);
            margin-bottom: 25px;
        }

        .spec-table th {
            width: 30%;
            background-color: #fcfcfc;
            color: #666;
            font-size: 0.85rem;
            padding: 12px;
        }
        .spec-table td { 
            font-size: 0.85rem; 
            padding: 12px;
            line-height: 1.5;
        }

        .breadcrumb-item {
            display: inline-block;
            max-width: 200px; /* Giới hạn độ dài breadcrumb */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: bottom;
        }

        .btn-buy {
            padding: 15px;
            font-weight: 800;
            border-radius: 50px;
            text-transform: uppercase;
        }

        .review-item {
            background: #fff;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #eee;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">LAPTOP STORE</a>
        <div class="ms-auto">
            <a href="view_cart.php" class="btn btn-dark btn-sm rounded-pill px-3">Giỏ hàng</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <nav class="mt-4 ms-2" aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Trang chủ</a></li>
            <li class="breadcrumb-item active" title="<?= htmlspecialchars($products["product_name"]) ?>">
                <?= htmlspecialchars($products["product_name"]) ?>
            </li>
        </ol>
    </nav>

    <div class="product-detail-card">
        <div class="row g-4">
            <!-- Cột Trái -->
            <div class="col-lg-5">
                <div class="img-wrapper">
                    <img src="image/<?= $products["image_url"] ?>" alt="Sản phẩm">
                </div>
            </div>

            <!-- Cột Phải -->
            <div class="col-lg-7">
                <h1 class="product-title"><?= htmlspecialchars($products["product_name"]) ?></h1>
                
                <div class="price-box">
                    <span class="text-muted text-decoration-line-through small">
                        <?= number_format($products['price'] * 1.1, 0, ',', '.') ?>đ
                    </span>
                    <h2 class="text-danger fw-bolder mb-0">
                        <?= number_format($products['price'], 0, ',', '.') ?> VNĐ
                    </h2>
                    <p class="text-success small fw-bold mb-0 mt-2">● Đang có hàng sẵn tại showroom</p>
                </div>

                <div class="specs-box">
                    <h6 class="fw-bold mb-3">Cấu hình chi tiết</h6>
                    <table class="table table-bordered spec-table">
                        <tbody>
                            <tr><th>CPU</th><td><?= htmlspecialchars($products["cpu"]) ?></td></tr>
                            <tr><th>RAM</th><td><?= htmlspecialchars($products["ram"]) ?></td></tr>
                            <tr><th>SSD</th><td><?= htmlspecialchars($products["storage"]) ?></td></tr>
                            <tr><th>GPU</th><td><?= htmlspecialchars($products["gpu"]) ?></td></tr>
                            <tr><th>Màn hình</th><td><?= htmlspecialchars($products["screen"]) ?></td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-grid mt-4">
                    <a href="add_to_cart.php?id=<?= $products['product_id'] ?>" class="btn btn-danger btn-buy">
                        Thêm vào giỏ hàng
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Section -->
    <div class="row mt-5">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h4 class="fw-bold mb-4 text-center">Đánh giá khách hàng</h4>

                <?php if(isset($_SESSION['user_id']) && $can_review): ?>
                    <div class="bg-light p-3 rounded-4 mb-4 border">
                        <form action="process_review.php" method="POST">
                            <input type="hidden" name="product_id" value="<?= $id ?>">
                            <select name="rating" class="form-select mb-2 rounded-pill shadow-sm">
                                <option value="5">⭐⭐⭐⭐⭐ (5/5)</option>
                                <option value="4">⭐⭐⭐⭐ (4/5)</option>
                                <option value="3">⭐⭐⭐ (3/5)</option>
                            </select>
                            <textarea name="comment" class="form-control mb-2" rows="2" placeholder="Cảm nhận của bạn..."></textarea>
                            <button class="btn btn-primary btn-sm rounded-pill px-4">Gửi</button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="review-list">
                    <?php if(empty($reviews)): ?>
                        <p class="text-center text-muted">Chưa có đánh giá.</p>
                    <?php else: ?>
                        <?php foreach($reviews as $r): ?>
                            <div class="review-item">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold text-primary"><?= htmlspecialchars($r['username']) ?></span>
                                    <span class="text-warning small"><?= str_repeat('★', $r['rating']) ?></span>
                                </div>
                                <p class="mb-1 mt-1 small"><?= htmlspecialchars($r['comment']) ?></p>
                                <small class="text-muted" style="font-size: 0.7rem;"><?= date('d/m/Y', strtotime($r['created_at'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="bg-dark text-white py-3 mt-5 text-center">
    <p class="mb-0 small opacity-50">© 2026 LAPTOP STORE - FIT TDC</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Hàm gửi dữ liệu về server
function trackEvent(type, targetId) {
    // Đảm bảo tên file này ĐÚNG với file PHP xử lý INSERT của bạn
    fetch('track_event.php', { 
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            event_type: type,
            target_id: targetId
        })
    })
    .then(res => res.json())
    .then(data => console.log("Tracked:", data))
    .catch(err => console.error("Track Error:", err));
}

document.addEventListener('DOMContentLoaded', function() {
    // 1. Theo dõi khi nhấn nút "Thêm vào giỏ hàng"
    // Đã sửa Selector từ .btn-outline-dark thành .btn-buy cho khớp HTML của bạn
    const addToCartBtn = document.querySelector('.btn-buy');
    if(addToCartBtn) {
        addToCartBtn.addEventListener('click', function() {
            trackEvent('Nhấn nút Thêm giỏ hàng', '<?= $id ?>');
        });
    }

    // 2. Theo dõi xem cấu hình (Dựa trên table vì bạn không dùng thẻ <details> nữa)
    // Ghi nhận ngay khi trang load vì bảng cấu hình hiện sẵn
    trackEvent('Xem cấu hình', 'Sản phẩm: <?= $id ?>');

    // 3. Theo dõi hành vi cuộn chuột tới phần đánh giá
    let scrolledToReviews = false;
    window.addEventListener('scroll', function() {
        const reviewSection = document.querySelector('.review-list');
        if (reviewSection && !scrolledToReviews) {
            const rect = reviewSection.getBoundingClientRect();
            if (rect.top < window.innerHeight) {
                trackEvent('Cuộn xem đánh giá', '<?= $id ?>');
                scrolledToReviews = true; 
            }
        }
    });
});
</script>
</body>
</html>