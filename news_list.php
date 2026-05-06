<?php
require_once "database.php";
$db = new Database();

// 1. Cấu hình phân trang
$limit = 4; // Số lượng tin mỗi trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// 2. Lấy tham số lọc
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? 'Tất cả';
$sort = $_GET['sort'] ?? 'Mới nhất';

// 3. Xây dựng câu lệnh SQL lọc dữ liệu
$where = " WHERE status = 1";
$params = [];
$types = "";

if ($search) {
    $where .= " AND title LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}
if ($category !== 'Tất cả') {
    $where .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

// 4. Đếm tổng số tin để tính tổng số trang
$sql_count = "SELECT COUNT(*) as total FROM news" . $where;
$res_count = $db->select($sql_count, $types, $params);
$total_news = $res_count[0]['total'];
$total_pages = ceil($total_news / $limit);

// 5. Lấy dữ liệu tin tức theo trang (Limit & Offset)
$order = ($sort === 'Mới nhất') ? " ORDER BY created_at DESC" : " ORDER BY created_at ASC";
$sql_news = "SELECT * FROM news" . $where . $order . " LIMIT ? OFFSET ?";
$params_news = array_merge($params, [$limit, $offset]);
$types_news = $types . "ii";

$news_list = $db->select($sql_news, $types_news, $params_news);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tin Tức</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/styles.css">
    <style>
        body { background-color: #fdfae6; }
        .filter-sidebar { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .news-card { background: white; border-radius: 12px; border: none; transition: 0.3s; }
        .btn-orange { background-color: #ffb74d; color: white; border-radius: 20px; font-weight: bold; border: none; }
        .empty-state { padding: 50px; text-align: center; background: white; border-radius: 12px; color: #888; }
        
        /* Style cho phân trang */
        .pagination .page-link { color: #ff8a33; border: none; margin: 0 5px; border-radius: 5px; }
        .pagination .page-item.active .page-link { background-color: #ffb74d; color: white; }
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
                    <a href="#" class="btn btn-dark btn-sm rounded-circle p-2" id="userMenu" data-bs-toggle="dropdown">
                        <svg width="20" height="20" fill="white" viewBox="0 0 448 512"><path d="M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4z"/></svg>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><h6 class="dropdown-header">Hi, <?= $_SESSION['username'] ?></h6></li>
                            <li><a class="dropdown-item" href="profile.php">Tài khoản</a></li>
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                <li><a class="dropdown-item text-primary fw-bold" href="admin_dashboard.php">Trang quản lý</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">Đăng xuất</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item" href="login.php">Đăng nhập</a></li>
                            <li><a class="dropdown-item" href="register.php">Đăng ký</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Giỏ hàng -->
                <a href="view_cart.php" class="btn btn-dark btn-sm rounded-pill px-3 d-flex align-items-center">
                    <svg width="18" height="18" fill="white" viewBox="0 0 576 512" class="me-2"><path d="M528.1 171.5L482 297.3c-11 30.2-39.6 50.7-71.7 50.7H203.1c-32.1 0-60.7-20.5-71.7-50.7L85.4 171.5c-4.1-11.3 4.3-23.5 16.4-23.5H411.6c12.1 0 20.5 12.2 16.4 23.5zM429.3 48H146.7c-12.1 0-20.5 12.2-16.4 23.5L176.4 128h223.2l46.1-56.5C449.8 60.2 441.4 48 429.3 48zM160 464a48 48 0 1 0 96 0 48 48 0 1 0 -96 0zm256 0a48 48 0 1 0 96 0 48 48 0 1 0 -96 0z"/></svg>
                    <span>Giỏ hàng</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h1 class="text-center fw-bold mb-5" style="letter-spacing: 2px;">TIN TỨC</h1>

    <div class="row">
        <!-- Sidebar Bộ lọc -->
        <div class="col-md-3 mb-4">
            <div class="filter-sidebar">
                <form action="" method="GET">
                    <h6 class="fw-bold mb-3">DANH MỤC</h6>
                    <?php foreach(['Tất cả', 'Khuyến mãi', 'Công nghệ', 'Hướng dẫn'] as $cat): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="category" value="<?= $cat ?>" 
                                   <?= $category == $cat ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="form-check-label"><?= $cat ?></label>
                        </div>
                    <?php endforeach; ?>

                    <h6 class="fw-bold mt-4 mb-3">SẮP XẾP</h6>
                    <select name="sort" class="form-select form-select-sm mb-4" onchange="this.form.submit()">
                        <option value="Mới nhất" <?= $sort == 'Mới nhất' ? 'selected' : '' ?>>Mới nhất</option>
                        <option value="Cũ nhất" <?= $sort == 'Cũ nhất' ? 'selected' : '' ?>>Cũ nhất</option>
                    </select>

                    <a href="news_list.php" class="btn btn-outline-secondary btn-sm w-100">Reset Bộ lọc</a>
                </form>
            </div>
        </div>

        <!-- Danh sách tin tức -->
        <div class="col-md-9">
            <form action="" method="GET" class="d-flex mb-4">
                <input type="text" name="search" class="form-control me-2 shadow-sm" 
                       placeholder="Nhập từ khóa tìm kiếm..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-primary px-4 shadow-sm" type="submit">🔍</button>
            </form>

            <div class="news-list">
                <?php if (!empty($news_list)): ?>
                    <?php foreach($news_list as $n): ?>
                        <div class="news-card p-3 shadow-sm mb-4">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <img src="image/<?= $n['image_url'] ?>" class="img-fluid rounded shadow-sm" alt="News" onerror="this.src='https://placehold.co/600x400?text=News'">
                                </div>
                                <div class="col-md-8">
                                    <h4 class="fw-bold text-dark mt-2 mt-md-0"><?= $n['title'] ?></h4>
                                    <p class="text-muted small"><?= $n['summary'] ?></p>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <span class="text-muted small"><?= date('d/m/Y', strtotime($n['created_at'])) ?> | <b><?= $n['category'] ?></b></span>
                                        <a href="news_detail.php?id=<?= $n['news_id'] ?>" class="btn btn-orange px-4 shadow-sm">Chi tiết</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Hiển thị Thanh phân trang -->
                    <?php if ($total_pages > 1): ?>
                    <nav class="mt-5">
                        <ul class="pagination justify-content-center">
                            <!-- Nút về trang trước -->
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= $search ?>&category=<?= $category ?>&sort=<?= $sort ?>">«</a>
                            </li>

                            <!-- Các số trang -->
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= $search ?>&category=<?= $category ?>&sort=<?= $sort ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <!-- Nút trang kế tiếp -->
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= $search ?>&category=<?= $category ?>&sort=<?= $sort ?>">»</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-state shadow-sm">
                        <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="100" class="mb-3 opacity-50">
                        <h4>Chưa có tin tức nào!</h4>
                        <p>Vui lòng thử lại với từ khóa hoặc bộ lọc khác.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>