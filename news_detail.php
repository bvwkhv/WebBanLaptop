<?php
require_once "database.php";
$db = new Database();

// Lấy ID tin tức từ URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Truy vấn lấy chi tiết bài viết
$sql = "SELECT * FROM news WHERE news_id = ? AND status = 1";
$res = $db->select($sql, "i", [$id]);

// Nếu không tìm thấy tin tức, quay về trang danh sách
if (empty($res)) {
    header("Location: news_list.php");
    exit();
}

$n = $res[0];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($n['title']) ?></title>
    <!-- Nhúng Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #fdfae6; } /* Màu nền vàng nhạt đồng bộ */
        .detail-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-top: 50px;
            margin-bottom: 50px;
        }
        .news-category {
            display: inline-block;
            background-color: #ffb74d;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .news-title {
            font-weight: 800;
            color: #333;
            margin-bottom: 20px;
            line-height: 1.3;
        }
        .news-meta {
            color: #888;
            font-size: 0.9rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .news-image {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .news-content {
            line-height: 1.8;
            color: #444;
            font-size: 1.1rem;
            text-align: justify;
        }
        .btn-back {
            background-color: transparent;
            color: #ffb74d;
            border: 2px solid #ffb74d;
            border-radius: 25px;
            padding: 8px 25px;
            font-weight: bold;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-top: 40px;
        }
        .btn-back:hover {
            background-color: #ffb74d;
            color: white;
        }
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

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="detail-container">
                <!-- Danh mục -->
                <span class="news-category"><?= htmlspecialchars($n['category']) ?></span>
                
                <!-- Tiêu đề -->
                <h1 class="news-title"><?= htmlspecialchars($n['title']) ?></h1>
                
                <!-- Thông tin ngày đăng -->
                <div class="news-meta">
                    <span>📅 Ngày đăng: <?= date('d/m/Y', strtotime($n['created_at'])) ?></span>
                </div>

                <!-- Hình ảnh bài viết -->
                <img src="image/<?= $n['image_url'] ?>" 
                     class="news-image shadow-sm" 
                     alt="<?= htmlspecialchars($n['title']) ?>"
                     onerror="this.src='https://placehold.co/800x400?text=News+Image'">

                <!-- Nội dung chi tiết -->
                <div class="news-content">
                    <?= nl2br(htmlspecialchars($n['content'])) ?>
                </div>

                <!-- Nút quay lại -->
                <a href="news_list.php" class="btn-back">← Quay lại danh sách</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>