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

// 1. Lấy thông tin sản phẩm
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

// 2. Logic Kiểm tra quyền đánh giá
$can_review = false;
$has_voted = false; 
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $check_purchase = "SELECT o.order_id FROM orders o 
              JOIN order_details oi ON o.order_id = oi.order_id 
              WHERE o.user_id = ? AND oi.product_id = ? AND o.status LIKE '%Đã giao%' LIMIT 1";
    $purchase = $db->select($check_purchase, 'ii', [$user_id, $id]);
    if (!empty($purchase)) { $can_review = true; }

    $check_voted = "SELECT rating_id FROM ratings WHERE product_id = ? AND user_id = ?";
    $voted = $db->select($check_voted, 'ii', [$id, $user_id]);
    if (!empty($voted)) { $has_voted = true; }
}

// 3. Thống kê sao tổng quát
$star_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$total_voted = 0;
$sum_stars = 0;
$sql_all_rates = "SELECT star_count, COUNT(*) as cnt FROM ratings WHERE product_id = ? GROUP BY star_count";
$all_rates = $db->select($sql_all_rates, "i", [$id]);
foreach ($all_rates as $row) {
    $star_counts[$row['star_count']] = $row['cnt'];
    $total_voted += $row['cnt'];
    $sum_stars += ($row['star_count'] * $row['cnt']);
}
$average = $total_voted > 0 ? round($sum_stars / $total_voted, 1) : 0;

// 4. --- LOGIC LỌC SAO & PHÂN TRANG (MỚI) ---
$rating_filter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0; 
$limit = 5; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where_clause = "WHERE c.product_id = ? AND c.parent_id IS NULL";
$params = [$id];
$types = "i";

if ($rating_filter >= 1 && $rating_filter <= 5) {
    $where_clause .= " AND r.star_count = ?";
    $params[] = $rating_filter;
    $types .= "i";
}

// Đếm tổng để phân trang
$sql_count = "SELECT COUNT(*) as total FROM comments c 
              LEFT JOIN ratings r ON c.user_id = r.user_id AND r.product_id = c.product_id 
              $where_clause";
$total_res = $db->select($sql_count, $types, $params);
$total_filtered_reviews = $total_res[0]['total'];
$total_pages = ceil($total_filtered_reviews / $limit);

// Lấy danh sách bình luận kèm phân trang
$sql_comments = "SELECT u.username, c.comment_id, c.content as comment, r.star_count, c.created_at
                 FROM comments c
                 JOIN users u ON c.user_id = u.user_id
                 LEFT JOIN ratings r ON u.user_id = r.user_id AND r.product_id = c.product_id
                 $where_clause
                 ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
$final_params = array_merge($params, [$limit, $offset]);
$final_types = $types . "ii";
$reviews = $db->select($sql_comments, $final_types, $final_params);

function getAdminReply($db, $comment_id) {
    $sql = "SELECT u.username, c.content FROM comments c 
            JOIN users u ON c.user_id = u.user_id 
            WHERE c.parent_id = ?";
    return $db->select($sql, "i", [$comment_id]);
}
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
    <style>
        :root { --primary-orange: #ffb74d; --price-red: #d70018; --bg-light: #f8f9fa; }
        body { background-color: #fdfae6; font-family: 'Inter', sans-serif; }
        .navbar { background-color: var(--primary-orange) !important; }
        .product-detail-card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-top: 20px; }
        .product-title { font-weight: 800; font-size: 1.8rem; color: #222; }
        .img-wrapper { background: var(--bg-light); border-radius: 15px; padding: 20px; text-align: center; }
        .img-wrapper img { max-width: 100%; height: auto; border-radius: 10px; }
        .price-box { background: #fff5f5; padding: 20px; border-radius: 15px; border-left: 5px solid var(--price-red); margin-bottom: 25px; }
        .spec-table th { width: 35%; background-color: #f8f9fa; font-size: 0.9rem; padding: 12px; }
        .admin-reply-box { background-color: #f1f1f1; border-left: 4px solid #d70018; position: relative; }
        .admin-reply-box::before { content: ""; position: absolute; top: -10px; left: 20px; border-left: 10px solid transparent; border-right: 10px solid transparent; border-bottom: 10px solid #f1f1f1; }
        .qtv-badge { background-color: #d70018; color: white; font-size: 10px; font-weight: bold; padding: 2px 6px; border-radius: 4px; }
        /* Tùy chỉnh phân trang & lọc */
        .filter-btn { border: 1px solid #eee; background: white; color: #555; transition: 0.2s; }
        .filter-btn.active { background-color: var(--price-red); color: white; border-color: var(--price-red); }
        .pagination .page-link { color: #000000; border: none; margin: 0 5px; border-radius: 8px; }
        .pagination .page-item.active .page-link { background-color: #d70018; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="image/logolaptop.jpg" width="40" height="40" class="rounded-circle me-2">
                <span class="fw-bold">LAPTOP STORE</span>
            </a>
            <div class="ms-auto d-flex align-items-center">
                <a href="view_cart.php" class="btn btn-dark btn-sm rounded-pill px-3">Giỏ hàng</a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="product-detail-card">
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="img-wrapper"><img src="image/<?= $products["image_url"] ?>"></div>
                </div>
                <div class="col-lg-7">
                    <h1 class="product-title mb-3"><?= htmlspecialchars($products["product_name"]) ?></h1>
                    <div class="mb-3"><span class="badge bg-warning text-dark px-3 py-2 rounded-pill">⭐ <?= $average ?> / 5</span></div>
                    <div class="price-box"><h2 class="text-danger fw-bolder mb-0"><?= number_format($products['price'], 0, ',', '.') ?> VNĐ</h2></div>
                    <div class="specs-box">
                        <table class="table table-bordered spec-table mb-0">
                            <tr><th>CPU</th><td><?= htmlspecialchars($products["cpu"]) ?></td></tr>
                            <tr><th>RAM</th><td><?= htmlspecialchars($products["ram"]) ?></td></tr>
                            <tr><th>SSD</th><td><?= htmlspecialchars($products["storage"]) ?></td></tr>
                            <tr><th>Màn hình</th><td><?= htmlspecialchars($products["screen"]) ?></td></tr>
                        </table>
                    </div>
                    <div class="d-grid mt-4">
                        <a href="add_to_cart.php?id=<?= $products['product_id'] ?>" onclick="trackAddToCart('<?= $products['product_id'] ?>')" class="btn btn-danger py-3 fw-bold rounded-pill shadow-sm">THÊM VÀO GIỎ HÀNG</a>
                    </div>
                </div>
            </div>

            <div class="mt-5 border-top pt-5">
                <h4 class="fw-bold mb-4 text-center text-uppercase">Đánh giá & Nhận xét</h4>
                
                <div class="row align-items-center mb-5 bg-light p-4 rounded-4 shadow-sm">
                    <div class="col-md-4 text-center border-end">
                        <h1 class="text-danger fw-bold display-4"><?= $average ?></h1>
                        <div class="text-warning fs-4 mb-2"><?php for($i=1; $i<=5; $i++) echo ($i <= round($average)) ? '★' : '☆'; ?></div>
                        <p class="text-muted small"><?= $total_voted ?> lượt đánh giá</p>
                    </div>
                    <div class="col-md-8 px-lg-5">
                        <?php for ($i = 5; $i >= 1; $i--): $percent = $total_voted > 0 ? ($star_counts[$i] / $total_voted) * 100 : 0; ?>
                        <div class="d-flex align-items-center mb-1">
                            <span class="small" style="width: 50px;"><?= $i ?> sao</span>
                            <div class="progress flex-grow-1 mx-2" style="height: 8px;"><div class="progress-bar bg-danger" style="width: <?= $percent ?>%"></div></div>
                            <span class="small text-muted" style="width: 30px;"><?= $star_counts[$i] ?></span>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="filter-section mb-4 d-flex align-items-center gap-2 flex-wrap">
                    <span class="fw-bold me-2">Lọc theo:</span>
                    <a href="product_details.php?id=<?= $id ?>&rating=0" class="btn btn-sm rounded-pill px-3 filter-btn <?= $rating_filter == 0 ? 'active' : '' ?>">Tất cả</a>
                    <?php for($i=5; $i>=1; $i--): ?>
                        <a href="product_details.php?id=<?= $id ?>&rating=<?= $i ?>" class="btn btn-sm rounded-pill px-3 filter-btn <?= $rating_filter == $i ? 'active' : '' ?>">
                            <?= $i ?> <i class="bi bi-star-fill small"></i>
                        </a>
                    <?php endfor; ?>
                </div>

                <?php if (isset($_SESSION['user_id']) && $can_review && !$has_voted): ?>
                    <div class="bg-white p-4 rounded-4 mb-5 border shadow-sm">
                        <h6 class="fw-bold mb-3">Gửi đánh giá của bạn</h6>
                        <form action="process_review.php" method="POST">
                            <input type="hidden" name="product_id" value="<?= $id ?>">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <select name="rating" class="form-select rounded-pill" required>
                                        <option value="5">⭐⭐⭐⭐⭐</option>
                                        <option value="4">⭐⭐⭐⭐</option>
                                        <option value="3">⭐⭐⭐</option>
                                        <option value="2">⭐⭐</option>
                                        <option value="1">⭐</option>
                                    </select>
                                </div>
                                <div class="col-12"><textarea name="comment" class="form-control rounded-4" rows="3" placeholder="Chia sẻ cảm nhận về sản phẩm..." required></textarea></div>
                            </div>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold mt-3">Gửi đánh giá</button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="review-list">
                    <?php if (empty($reviews)): ?>
                        <div class="text-center py-4 bg-white rounded-4 shadow-sm">
                            <p class="text-muted mb-0">Không có bình luận nào cho mức đánh giá này.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reviews as $r): ?>
                            <div class="review-item mb-4 pb-3 border-bottom">
                                <div class="d-flex gap-3">
                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 45px; height: 45px; font-weight: bold;">
                                        <?= strtoupper(substr($r['username'], 0, 1)) ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($r['username']) ?></h6>
                                            <small class="text-muted"><?= date('d/m/Y', strtotime($r['created_at'])) ?></small>
                                        </div>
                                        <div class="text-warning my-1" style="font-size: 0.85rem;">
                                            <?= str_repeat('★', $r['star_count'] ?? 0) ?><?= str_repeat('☆', 5 - ($r['star_count'] ?? 0)) ?>
                                        </div>
                                        <p class="mb-2 text-dark" style="font-size: 0.95rem; line-height: 1.6;"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>

                                        <?php 
                                        $replies = getAdminReply($db, $r['comment_id']);
                                        foreach ($replies as $reply): ?>
                                            <div class="admin-reply-box mt-3 p-3 rounded-4">
                                                <div class="d-flex align-items-center mb-1">
                                                    <span class="qtv-badge me-2">QTV</span>
                                                    <strong class="text-dark small">Quản trị viên</strong>
                                                </div>
                                                <p class="mb-0 text-muted small"><?= nl2br(htmlspecialchars($reply['content'])) ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($total_pages > 1): ?>
                        <nav class="mt-5">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                        <a class="page-link shadow-sm" href="product_details.php?id=<?= $id ?>&rating=<?= $rating_filter ?>&page=<?= $i ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5 text-center">
        <p class="mb-0 small opacity-75">© 2026 LAPTOP STORE - FIT TDC Project</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function trackEvent(type, targetId) {
            fetch('track_event.php', { method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ event_type: type, target_id: targetId })
            }).catch(err => console.error(err));
        }
        document.addEventListener('DOMContentLoaded', () => trackEvent('Xem chi tiết', '<?= $id ?>'));
        function trackAddToCart(id) { trackEvent('click_add_to_cart', id); }
    </script>
</body>
</html>