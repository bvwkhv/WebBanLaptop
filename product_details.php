<?php
    require_once "auth_check.php";
    require_once "database.php";
    $db = new Database();
    $id = $_GET["id"];

    $sql ="SELECT p.product_id, p.product_name, p.image_url, p.price, ps.cpu, ps.ram, ps.storage, ps.gpu, ps.screen 
        FROM products AS p 
        INNER JOIN product_specs AS ps ON p.product_id = ps.product_id 
        WHERE p.product_id = ?";

// Truyền thêm 'i' (integer) và mảng [$id] để khớp với dấu '?'
$result = $db->select($sql, 'i', [$id]); 

if (!empty($result)) {
    $products = $result[0];
} else {
    die("Không tìm thấy sản phẩm!");
}
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/styles.css">
    <title>Document</title>
</head>
<body>
    <!-- navbar -->
    <!-- navbar -->
    <nav class="navbar navbar-expand-lg navbar-light border-bottom">
  <div class="container">
    <!-- navbar rand -->
    <a class="navbar-brand home" href="index.php">Home</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <!-- menu item -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" aria-current="page" href="#">Danh mục</a>
        </li>
        </li>
        
      </ul>

      <!-- người dùng và giỏ hàng -->
      <div class="me-2 d-inline-flex align-items-center">
    
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
        <li><a class="dropdown-item" href="profile.php">Thông tin tài khoản</a>
            <a class="dropdown-item" href="order_history.php">Lịch sử đơn hàng</a>
        </li>
        
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

      <!-- form tìm kiếm -->
      <form class="d-flex mt-2 mt-lg-0">
        <input class="form-control me-2" type="search" placeholder="Tìm kiếm" aria-label="Search">
        <button class="btn btn-success" type="submit"><svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 512 512"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376C296.3 401.1 253.9 416 208 416 93.1 416 0 322.9 0 208S93.1 0 208 0 416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg>
        </button>
      </form>
    </div>
  </div>
    </nav>

    <!-- header -->
    <div class="container detail">
        <h1 class="mt-5"><?= $products["product_name"]?></h1>
        <div class="product-main-content">
            <div class="image">
                <img src="image/<?= $products["image_url"]?>" alt="product Image">
            </div>
        <!-- Bộ xử lý -->
        <div class="description-side mt-5">
        <details class="card mb-2">
            <summary class="card-header">Bộ xử lý</summary>
            <div class="card-body">
                <p><?= $products["cpu"]?></p>
            </div>
        </details>
        <!-- Ram -->
        <details class="card mb-2">
            <summary class="card-header">RAM</summary>
            <div class="card-body">
                <p><?= $products["ram"]?></p>
            </div>
        </details>
        <!-- Ổ cứng -->
        <details class="card mb-2">
            <summary class="card-header">Ổ cứng</summary>
            <div class="card-body">
                <p><?= $products["storage"]?></p>
            </div>
        </details>
        <!-- GPU -->
        <details class="card mb-2">
            <summary class="card-header">GPU</summary>
            <div class="card-body">
                <p><?= $products["gpu"]?></p>
            </div>
        </details>
        <!-- Màn hình -->
        <details class="card mb-2">
            <summary class="card-header">Màn hình</summary>
            <div class="card-body">
                <p><?= $products["screen"]?></p>
            </div>
        </details>
        </div>
        </div>
    </div>

    <!-- section -->
    <div class="text-center my-3">
    <span class="text-muted text-decoration-line-through" style="font-size: 14px;">
        <?= number_format($products['price'] * 1.1, 0, ',', '.') ?>đ
    </span>
    <h3 class="fw-bolder text-danger" style="font-size: 24px;">
        <?= number_format($products['price'], 0, ',', '.') ?> VNĐ
    </h3>
</div>
    
    <div class="card-footer p-4 pt-0 border-top-0 bg-transparent">
        <div class="text-center">
            <a class="btn btn-outline-dark bg-primary mt-auto" href="add_to_cart.php?id=<?= $products['product_id']?>"><svg xmlns="http://www.w3.org/2000/svg" width="50px" height="50px" viewBox="0 0 640 512"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M24-16C10.7-16 0-5.3 0 8S10.7 32 24 32l45.3 0c3.9 0 7.2 2.8 7.9 6.6l52.1 286.3c6.2 34.2 36 59.1 70.8 59.1L456 384c13.3 0 24-10.7 24-24s-10.7-24-24-24l-255.9 0c-11.6 0-21.5-8.3-23.6-19.7l-5.1-28.3 303.6 0c30.8 0 57.2-21.9 62.9-52.2L568.9 69.9C572.6 50.2 557.5 32 537.4 32l-412.7 0-.4-2c-4.8-26.6-28-46-55.1-46L24-16zM208 512a48 48 0 1 0 0-96 48 48 0 1 0 0 96zm224 0a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/></svg>
            <b>Thêm vào giỏ hàng</b></a>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 p-4 mt-5">
    <h4 class="fw-bold mb-4">Đánh giá sản phẩm</h4>
    <?php if(isset($_SESSION['user_id'])): ?>
        <form action="process_review.php" method="POST">
            <input type="hidden" name="product_id" value="<?= $id ?>">
            
            <div class="mb-3">
                <label class="form-label fw-bold text-warning">Chọn số sao:</label>
                <select name="rating" class="form-select w-auto rounded-pill">
                    <option value="5">⭐⭐⭐⭐⭐ (5/5)</option>
                    <option value="4">⭐⭐⭐⭐ (4/5)</option>
                    <option value="3">⭐⭐⭐ (3/5)</option>
                    <option value="2">⭐⭐ (2/5)</option>
                    <option value="1">⭐ (1/5)</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Nội dung bình luận:</label>
                <textarea name="comment" class="form-control rounded-4" rows="3" placeholder="Chia sẻ cảm nhận của bạn về máy..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary rounded-pill px-4">Gửi đánh giá</button>
        </form>
    <?php else: ?>
        <p class="text-muted">Vui lòng <a href="login.php">đăng nhập</a> để bình luận.</p>
    <?php endif; ?>

    <hr class="my-5">

    <h5 class="fw-bold mb-4">Khách hàng nói gì về sản phẩm này</h5>
    <?php
    $reviews = $db->select("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.user_id WHERE r.product_id = ? ORDER BY r.created_at DESC", "i", [$id]);
    foreach($reviews as $r):
    ?>
        <div class="mb-4 border-bottom pb-3">
            <div class="d-flex justify-content-between">
                <span class="fw-bold text-primary"><?= htmlspecialchars($r['username']) ?></span>
                <span class="text-warning">
                    <?= str_repeat('⭐', $r['rating']) ?>
                </span>
            </div>
            <p class="mb-1 text-dark"><?= htmlspecialchars($r['comment']) ?></p>
            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></small>
        </div>
    <?php endforeach; ?>
</div>

    <footer class="text-bg-dark py-5">
        <div>
            <p>© 2026 - Đồ án Chuyên ngành Công nghệ thông tin</p>
            <p>Dữ liệu sản phẩm được tổng hợp từ FPT-Shop và FIT-TDC 2019</p>
        </div>
        </footer>

    <!-- nhúng js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

</body>
</html>