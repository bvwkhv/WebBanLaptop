<?php
// Bắt đầu phiên làm việc (Session) để quản lý trạng thái đăng nhập, hiển thị thông tin user trên Navbar
session_start();

// Nhúng file cấu hình kết nối cơ sở dữ liệu
require_once "database.php";
$db = new Database();

// Lấy ID tin tức từ URL, ép kiểu (int) để phòng chống hoàn toàn lỗ hổng SQL Injection
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Truy vấn lấy chi tiết bài viết (Chỉ lấy bài viết có status = 1 tức là đang được cấu hình "Hiển thị")
$sql = "SELECT * FROM news WHERE news_id = ? AND status = 1";
$res = $db->select($sql, "i", [$id]);

// Nếu không tìm thấy tin tức tương ứng trong cơ sở dữ liệu, lập tức điều hướng an toàn về trang danh sách
if (empty($res)) {
    header("Location: news_list.php");
    exit();
}

// Gán mảng dữ liệu bản ghi tìm được vào biến bài viết $n
$n = $res[0];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($n['title']) ?> - Laptop Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2 family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="styles/styles.css">
    
    <style>
        /* Thiết lập nền trang và font chữ Inter chung toàn bộ hệ thống đồ án */
        body { 
            background-color: #fdfae6; /* Giữ tông màu kem Pastel đồng bộ nhận diện Laptop Store */
            font-family: 'Inter', sans-serif;
            color: #333;
        }

        /* --- KHỐI CONTAINER CHI TIẾT BÀI VIẾT --- */
        .detail-container {
            background: white;
            padding: 45px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            margin-top: 40px;
            margin-bottom: 50px;
            border: 1px solid #f1f5f9;
        }

        /* Huy hiệu danh mục (Khuyến mãi, Công nghệ, Hướng dẫn) */
        .news-category {
            display: inline-block;
            background-color: #ffb74d;
            color: #4a321a; /* Đổi sang màu chữ tối giúp nâng cao độ tương phản, dễ đọc trên nền vàng cam */
            padding: 6px 18px;
            border-radius: 30px;
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
        }

        /* Tiêu đề bài viết lớn */
        .news-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 20px;
            line-height: 1.3;
            letter-spacing: -0.5px;
        }

        /* Thanh Meta dữ liệu (Ngày đăng bài) */
        .news-meta {
            color: #64748b;
            font-size: 0.88rem;
            font-weight: 500;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 20px;
            margin-bottom: 35px;
        }

        /* Khung hiển thị hình ảnh đại diện lớn */
        .news-image {
            width: 100%;
            max-height: 480px;
            object-fit: cover; /* Chống méo móp, phình giãn ảnh bất kể kích thước thực tế upload */
            border-radius: 12px;
            margin-bottom: 35px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }

        /* Khu vực hiển thị văn bản chi tiết bài tin tức */
        .news-content {
            line-height: 1.85;
            color: #334155;
            font-size: 1.08rem;
            text-align: justify; /* Căn đều hai bên lề chữ giúp văn bản đều đặn, chuẩn giao diện báo chí */
        }

        /* Nút quay lại danh sách bài tin tức */
        .btn-back {
            background-color: transparent;
            color: #ffb74d;
            border: 2px solid #ffb74d;
            border-radius: 30px;
            padding: 10px 28px;
            font-weight: 600;
            font-size: 0.92rem;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 40px;
        }
        .btn-back:hover {
            background-color: #ffb74d;
            color: #4a321a !important; /* Đồng bộ màu chữ khi hover qua nút */
            box-shadow: 0 4px 12px rgba(255, 183, 77, 0.25);
            transform: translateX(-2px); /* Tạo hiệu ứng chuyển động nhẹ dịch về bên trái khi rê chuột */
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

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="detail-container">
                <span class="news-category"><?= htmlspecialchars($n['category']) ?></span>
                
                <h1 class="news-title"><?= htmlspecialchars($n['title']) ?></h1>
                
                <div class="news-meta">
                    <span><i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Ngày đăng: <?= date('d/m/Y', strtotime($n['created_at'])) ?></span>
                </div>

                <img src="image/news/<?= htmlspecialchars($n['image_url']) ?>" 
                     class="news-image shadow-sm" 
                     alt="<?= htmlspecialchars($n['title']) ?>"
                     onerror="this.src='https://placehold.co/800x450?text=Laptop+Store+News'">

                <div class="news-content">
                    <?= nl2br(htmlspecialchars($n['content'])) ?>
                </div>

                <div class="border-top mt-5 pt-2">
                    <a href="news_list.php" class="btn-back">
                        <i class="fa-solid fa-arrow-left"></i> Quay lại danh sách tin
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>