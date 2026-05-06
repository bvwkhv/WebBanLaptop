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
<nav class="navbar navbar-expand-lg navbar-light border-bottom">
  <div class="container">
    <a class="navbar-brand home" href="index.php">Home</a>
    <a class="navbar-brand home" href="news_list.php">Tin tức</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0"></ul>

      <form class="d-flex mx-auto mt-2 mt-lg-0" action="index.php" method="GET" style="width: 100%; max-width: 500px;">
        <input class="form-control me-2" type="search" name="search" placeholder="Tìm kiếm nhanh..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        <button class="btn btn-success" type="submit">
          <svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 512 512">
            <path fill="currentColor" d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376C296.3 401.1 253.9 416 208 416 93.1 416 0 322.9 0 208S93.1 0 208 0 416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/>
          </svg>
        </button>
      </form>

      <div class="ms-auto d-inline-flex align-items-center">
        <div class="dropdown custom-user-dropdown">
          <a href="#" class="btn btn-danger btn-sm d-inline-flex justify-content-center align-items-center user dropdown-toggle" 
             id="userMenu" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
            <svg xmlns="http://www.w3.org/2000/svg" width="26px" height="30px" viewBox="0 0 448 512">
              <path fill="rgb(255, 255, 255)" d="M224 248a120 120 0 1 0 0-240 120 120 0 1 0 0 240zm-29.7 56C95.8 304 16 383.8 16 482.3 16 498.7 29.3 512 45.7 512l356.6 0c16.4 0 29.7-13.3 29.7-29.7 0-98.5-79.8-178.3-178.3-178.3l-59.4 0z"/>
            </svg>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userMenu">
            <?php if (isset($_SESSION['user_id'])): ?>
              <li><h6 class="dropdown-header text-dark">Chào, <?= $_SESSION['username'] ?></h6></li>
              <li><a class="dropdown-item" href="profile.php">Thông tin tài khoản</a></li>
              <li><a class="dropdown-item" href="order_history.php">Lịch sử đơn hàng</a></li>
              <?php if ($_SESSION['role'] == 'admin'): ?>
                <li><a class="dropdown-item fw-bold text-primary" href="admin_dashboard.php">Trang Quản Trị</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php">Đăng xuất</a></li>
            <?php else: ?>
              <li><a class="dropdown-item" href="login.php">Đăng nhập</a></li>
              <li><a class="dropdown-item" href="register.php">Đăng ký</a></li>
            <?php endif; ?>
          </ul>
        </div>

        <a href="view_cart.php" class="btn btn-danger btn-sm d-inline-flex justify-content-center align-items-center shopping-cart ms-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="30px" height="26px" viewBox="0 0 640 512">
            <path fill="rgb(255, 255, 255)" d="M24-16C10.7-16 0-5.3 0 8S10.7 32 24 32l45.3 0c3.9 0 7.2 2.8 7.9 6.6l52.1 286.3c6.2 34.2 36 59.1 70.8 59.1L456 384c13.3 0 24-10.7 24-24s-10.7-24-24-24l-255.9 0c-11.6 0-21.5-8.3-23.6-19.7l-5.1-28.3 303.6 0c30.8 0 57.2-21.9 62.9-52.2L568.9 69.9C572.6 50.2 557.5 32 537.4 32l-412.7 0-.4-2c-4.8-26.6-28-46-55.1-46L24-16zM208 512a48 48 0 1 0 0-96 48 48 0 1 0 0 96zm224 0a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/>
          </svg>
          <span class="ms-1">Giỏ hàng</span>
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