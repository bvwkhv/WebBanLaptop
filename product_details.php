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

// 1. Lấy thông tin sản phẩm và cấu hình
$sql = "SELECT p.product_id, p.product_name, p.image_url, p.price, ps.cpu, ps.ram, ps.storage, ps.gpu, ps.screen 
        FROM products AS p 
        INNER JOIN product_specs AS ps ON p.product_id = ps.product_id 
        WHERE p.product_id = ?";

$result = $db->select($sql, 'i', [$id]);

if (!empty($result)) {
    $products = $result[0];
} else {
    die("<div class='container mt-5 alert alert-danger'>Không tìm thấy sản phẩm!</div>");
}

// 2. Logic Kiểm tra quyền đánh giá (ĐÃ FIX)
$can_review = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    echo "<script>console.log('Đang check User: $user_id | Product: $id');</script>";
    // SQL so sánh trực tiếp, tránh dùng LOWER với tiếng Việt
    $check_sql = "SELECT o.order_id FROM orders o 
              JOIN order_details oi ON o.order_id = oi.order_id 
              WHERE o.user_id = ? 
              AND oi.product_id = ? 
              AND o.status LIKE '%Đã giao%' 
              LIMIT 1";

    $purchase = $db->select($check_sql, 'ii', [$user_id, $id]);

    if (!empty($purchase)) {
        $can_review = true; // Kích hoạt biến để hiện Form
    } else {
        echo "<!-- Debug: User $user_id chưa có đơn hàng 'Đã giao' cho sản phẩm $id -->";
    }
}

// 3. Lấy danh sách đánh giá
$reviews = $db->select("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.user_id WHERE r.product_id = ? ORDER BY r.created_at DESC", "i", [$id]);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($products["product_name"]) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/styles.css">
    <style>
        :root {
            --primary-orange: #ffb74d;
            --price-red: #d70018;
            --bg-light: #f8f9fa;
        }

        body {
            background-color: #fdfae6;
            font-family: 'Inter', sans-serif;
        }

        .navbar {
            background-color: var(--primary-orange) !important;
        }

        .product-detail-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            margin-top: 20px;
        }

        .product-title {
            font-weight: 800;
            font-size: 1.8rem;
            line-height: 1.3;
            word-wrap: break-word;
            color: #222;
        }

        .img-wrapper {
            background: var(--bg-light);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }

        .img-wrapper img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            transition: 0.3s;
        }

        .img-wrapper img:hover {
            transform: scale(1.02);
        }

        .price-box {
            background: #fff5f5;
            padding: 20px;
            border-radius: 15px;
            border-left: 5px solid var(--price-red);
            margin-bottom: 25px;
        }

        .spec-table th {
            width: 35%;
            background-color: #f8f9fa;
            color: #555;
            font-size: 0.9rem;
            padding: 12px;
        }

        .spec-table td {
            font-size: 0.9rem;
            padding: 12px;
            font-weight: 500;
        }

        .btn-buy {
            padding: 15px;
            font-weight: 800;
            border-radius: 50px;
            text-transform: uppercase;
            transition: 0.3s;
        }

        .btn-buy:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(215, 0, 24, 0.3);
        }

        .review-item {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #eee;
            transition: 0.3s;
        }

        .review-item:hover {
            border-color: var(--primary-orange);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="image/logolaptop.jpg" width="40" height="40" class="rounded-circle me-2" alt="">
                <span class="fw-bold">LAPTOP STORE</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                    <li class="nav-item"><a class="nav-link" href="index.php">Trang chủ</a></li>
                    <li class="nav-item"><a class="nav-link" href="news_list.php">Tin tức</a></li>
                </ul>

                <form class="d-flex mx-auto search-group w-100" style="max-width: 450px;" action="index.php" method="GET">
                    <input class="form-control" type="search" name="search" placeholder="Tìm kiếm laptop..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button class="btn px-4" type="submit">🔍</button>
                </form>

                <div class="ms-auto d-flex align-items-center mt-3 mt-lg-0">
                    <!-- User Menu -->
                    <div class="dropdown me-3">
                        <a href="#" class="btn btn-dark btn-sm rounded-circle p-2 d-flex align-items-center justify-content-center"
                            id="userMenu" data-bs-toggle="dropdown" aria-expanded="false" style="width: 38px; height: 38px;">
                            <svg width="18" height="18" fill="white" viewBox="0 0 448 512">
                                <path d="M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4z" />
                            </svg>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 py-2" aria-labelledby="userMenu" style="min-width: 200px; border-radius: 12px;">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <li class="px-3 py-2">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 14px;">
                                            <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                                        </div>
                                        <div class="overflow-hidden">
                                            <p class="mb-0 fw-bold text-truncate" style="font-size: 14px; max-width: 130px;">Hi, <?= $_SESSION['username'] ?></p>
                                            <small class="text-muted" style="font-size: 11px;">Khách hàng</small>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <hr class="dropdown-divider mx-2">
                                </li>
                                <!-- <li><a class="dropdown-item py-2" href="profile.php"><i class="fa-solid fa-user-gear me-2 text-muted"></i> Tài khoản</a></li> -->
                                <li><a class="dropdown-item py-2" href="order_history.php"><i class="fa-solid fa-clock-rotate-left me-2 text-muted"></i> Lịch sử đơn hàng</a></li>

                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <li><a class="dropdown-item py-2 text-primary fw-bold" href="admin_dashboard.php"><i class="fa-solid fa-gauge-high me-2"></i> Trang quản lý</a></li>
                                <?php endif; ?>

                                <li>
                                    <hr class="dropdown-divider mx-2">
                                </li>
                                <li><a class="dropdown-item py-2 text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Đăng xuất</a></li>

                            <?php else: ?>
                                <li><a class="dropdown-item py-2" href="login.php"><i class="fa-solid fa-right-to-bracket me-2 text-muted"></i> Đăng nhập</a></li>
                                <li><a class="dropdown-item py-2" href="register.php"><i class="fa-solid fa-user-plus me-2 text-muted"></i> Đăng ký</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Giỏ hàng -->
                    <a href="view_cart.php" class="btn btn-dark btn-sm rounded-pill px-3 d-flex align-items-center">
                        <svg width="18" height="18" fill="white" viewBox="0 0 576 512" class="me-2">
                            <path d="M528.1 171.5L482 297.3c-11 30.2-39.6 50.7-71.7 50.7H203.1c-32.1 0-60.7-20.5-71.7-50.7L85.4 171.5c-4.1-11.3 4.3-23.5 16.4-23.5H411.6c12.1 0 20.5 12.2 16.4 23.5zM429.3 48H146.7c-12.1 0-20.5 12.2-16.4 23.5L176.4 128h223.2l46.1-56.5C449.8 60.2 441.4 48 429.3 48zM160 464a48 48 0 1 0 96 0 48 48 0 1 0 -96 0zm256 0a48 48 0 1 0 96 0 48 48 0 1 0 -96 0z" />
                        </svg>
                        <span>Giỏ hàng</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <nav class="mt-4 ms-2" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted small">Trang chủ</a></li>
                <li class="breadcrumb-item active small text-truncate" style="max-width: 250px;"><?= htmlspecialchars($products["product_name"]) ?></li>
            </ol>
        </nav>

        <div class="product-detail-card">
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="img-wrapper">
                        <img src="image/<?= $products["image_url"] ?>" alt="<?= htmlspecialchars($products["product_name"]) ?>">
                    </div>
                </div>
                <div class="col-lg-7">
                    <h1 class="product-title mb-3"><?= htmlspecialchars($products["product_name"]) ?></h1>
                    <div class="price-box">
                        <div class="text-muted text-decoration-line-through small mb-1">Giá niêm yết: <?= number_format($products['price'] * 1.1, 0, ',', '.') ?>đ</div>
                        <h2 class="text-danger fw-bolder mb-0"><?= number_format($products['price'], 0, ',', '.') ?> VNĐ</h2>
                        <p class="text-success small fw-bold mb-0 mt-2"><i class="bi bi-check-circle-fill me-1"></i> Sẵn hàng tại Showroom</p>
                    </div>
                    <div class="specs-box">
                        <h6 class="fw-bold mb-3"><i class="bi bi-cpu me-2"></i>Thông số kỹ thuật</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered spec-table mb-0">
                                <tbody>
                                    <tr>
                                        <th>CPU</th>
                                        <td><?= htmlspecialchars($products["cpu"]) ?></td>
                                    </tr>
                                    <tr>
                                        <th>RAM</th>
                                        <td><?= htmlspecialchars($products["ram"]) ?></td>
                                    </tr>
                                    <tr>
                                        <th>SSD</th>
                                        <td><?= htmlspecialchars($products["storage"]) ?></td>
                                    </tr>
                                    <tr>
                                        <th>GPU</th>
                                        <td><?= htmlspecialchars($products["gpu"]) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Màn hình</th>
                                        <td><?= htmlspecialchars($products["screen"]) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="d-grid mt-4">
                        <a href="add_to_cart.php?id=<?= $products['product_id'] ?>"
                            onclick="trackAddToCart('<?= $products['product_id'] ?>')"
                            class="btn btn-danger btn-buy shadow">
                            <i class="bi bi-cart-plus me-2"></i>Thêm vào giỏ hàng
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Review Section -->
        <div class="row mt-5">
            <div class="col-lg-10 mx-auto">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h4 class="fw-bold mb-4 text-center">Đánh giá từ khách hàng</h4>

                    <?php if ($can_review): ?>
                        <div class="bg-light p-4 rounded-4 mb-5 border border-warning-subtle">
                            <h6 class="fw-bold mb-3">Gửi bình luận của bạn</h6>
                            <form action="process_review.php" method="POST">
                                <input type="hidden" name="product_id" value="<?= $id ?>">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <select name="rating" class="form-select mb-2 rounded-pill shadow-sm" required>
                                            <option value="5">⭐⭐⭐⭐⭐ (Rất tốt)</option>
                                            <option value="4">⭐⭐⭐⭐ (Tốt)</option>
                                            <option value="3">⭐⭐⭐ (Bình thường)</option>
                                            <option value="2">⭐⭐ (Kém)</option>
                                            <option value="1">⭐ (Tệ)</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <textarea name="comment" class="form-control mb-3 rounded-4 shadow-sm" rows="3" placeholder="Sản phẩm dùng rất tốt..." required></textarea>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">Gửi đánh giá ngay</button>
                            </form>
                        </div>
                    <?php elseif (isset($_SESSION['user_id'])): ?>
                        <div class="alert alert-secondary text-center rounded-pill small py-2 mb-5">
                            <i class="bi bi-info-circle me-2"></i> Bạn cần <b>mua sản phẩm</b> và đơn hàng ở trạng thái <b>"Đã giao"</b> để đánh giá.
                        </div>
                    <?php else: ?>
                        <div class="text-center mb-5">
                            <a href="login.php" class="btn btn-outline-primary btn-sm rounded-pill px-4">Đăng nhập để để lại đánh giá</a>
                        </div>
                    <?php endif; ?>

                    <div class="review-list">
                        <?php if (empty($reviews)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-chat-dots text-muted display-6"></i>
                                <p class="text-muted mt-2">Sản phẩm này chưa có đánh giá nào.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reviews as $r): ?>
                                <div class="review-item shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold text-primary"><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($r['username']) ?></span>
                                        <span class="text-warning small"><?= str_repeat('★', $r['rating']) ?><?= str_repeat('☆', 5 - $r['rating']) ?></span>
                                    </div>
                                    <p class="mb-2 text-dark small"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
                                    <div class="d-flex justify-content-between mt-2">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-normal" style="font-size: 0.65rem;">
                                            <i class="bi bi-check-circle-fill me-1"></i>Đã mua hàng
                                        </span>
                                        <small class="text-muted" style="font-size: 0.7rem;"><?= date('d/m/Y', strtotime($r['created_at'])) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5 text-center">
        <div class="container">
            <p class="mb-0 small opacity-75">© 2026 LAPTOP STORE - FIT TDC Project</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function trackEvent(type, targetId) {
            fetch('track_event.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    event_type: type,
                    target_id: targetId
                })
            }).catch(err => console.error("Track Error:", err));
        }
        document.addEventListener('DOMContentLoaded', function() {
            trackEvent('Xem chi tiết sản phẩm', '<?= $id ?>');
        });

        function trackAddToCart(productId) {
            console.log("Đang tracking sản phẩm:", productId);

            fetch('track_event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        event_type: 'click_add_to_cart', // Tên này phải khớp với cái SQL bên trên
                        target_id: productId
                    })
                })
                .then(res => res.json())
                .then(data => console.log("Hệ thống đã ghi nhận:", data))
                .catch(err => console.error("Lỗi tracking:", err));
        }
    </script>
</body>

</html>