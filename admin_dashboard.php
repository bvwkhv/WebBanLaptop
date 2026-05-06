<?php
require_once "database.php";
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
$db = new database();

$limit = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $limit;

$total_rows = $db->count("SELECT COUNT(*) FROM products");
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT * FROM products LIMIT ? OFFSET ?";
$products = $db->select($sql, 'ii', [$limit, $offset]);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Laptop Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Thêm Font Awesome để có icon đẹp -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --admin-primary: #4e73df;
            --admin-dark: #222e3c;
            --bg-light: #f4f7f6;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg-light);
        }

        /* Navbar & Sidebar */
        .navbar { background: white !important; border-bottom: 1px solid #e3e6f0 !important; }
        
        .sidebar-link {
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s;
            border: none !important;
            padding: 12px 20px;
            font-weight: 500;
            color: #555;
        }

        .sidebar-link:hover {
            background-color: #eaecf4;
            color: var(--admin-primary);
        }

        .sidebar-link.active {
            background-color: var(--admin-primary) !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(78, 115, 223, 0.2);
        }

        /* Table Design */
        .card { border: none; border-radius: 12px; }
        .table thead th {
            background-color: #f8f9fc;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #4e73df;
            border-top: none;
        }

        .product-img {
            width: 60px;
            height: 45px;
            object-fit: contain;
            background: white;
            padding: 2px;
            border: 1px solid #eee;
            border-radius: 6px;
        }

        .product-name-truncate {
            max-width: 350px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 500;
        }

        /* Buttons */
        .btn-action {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            margin-right: 5px;
        }

        /* Pagination */
        .pagination .page-link {
            border: none;
            margin: 0 3px;
            border-radius: 6px;
            color: #555;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--admin-primary);
            box-shadow: 0 4px 10px rgba(78, 115, 223, 0.2);
        }

        footer { background-color: var(--admin-dark) !important; color: #adb5bd; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="index.php">
            <i class="fa-solid fa-laptop-code me-2"></i>LAPTOP ADMIN
        </a>
        
        <div class="ms-auto d-flex align-items-center">
            <div class="dropdown">
                <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userMenu" data-bs-toggle="dropdown">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=4e73df&color=fff" class="rounded-circle me-2" width="30">
                    <span class="d-none d-md-inline"><?= $_SESSION['username'] ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3">
                    <li><a class="dropdown-item" href="profile.php"><i class="fa-regular fa-user me-2"></i>Hồ sơ</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-sign-out me-2"></i>Đăng xuất</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-4 flex-grow-1">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 mb-4">
            <div class="list-group shadow-sm border-0">
                <a href="admin_dashboard.php" class="list-group-item sidebar-link active">
                    <i class="fa-solid fa-box me-2"></i> Sản phẩm
                </a>
                <a href="admin_orders.php" class="list-group-item sidebar-link">
                    <i class="fa-solid fa-cart-shopping me-2"></i> Đơn hàng
                </a>
                <a href="admin_statistics.php" class="list-group-item sidebar-link">
                    <i class="fa-solid fa-chart-line me-2"></i> Thống kê
                </a>
                <a href="admin_news.php" class="list-group-item sidebar-link">
                    <i class="fa-regular fa-newspaper me-2"></i> Tin tức
                </a>
                <a href="admin_tracking.php" class="list-group-item sidebar-link">
                    <i class="fa-solid fa-eye" style="color: rgb(151, 151, 151);"></i> Theo dõi sự kiện
                </a>
                <a href="admin_promotions.php" class="list-group-item sidebar-link">
                    <i class="fa-regular fa-newspaper me-2"></i> Khuyến mãi
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0 text-dark">Quản lý sản phẩm</h4>
                <a href="add_product.php" class="btn btn-primary shadow-sm rounded-pill px-4">
                    <i class="fa-solid fa-plus me-2"></i>Thêm Laptop mới
                </a>
            </div>

            <div class="card shadow-sm p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th class="text-center">ID</th>
                                <th>Sản phẩm</th>
                                <th>Tên Laptop</th>
                                <th>Giá bán</th>
                                <th class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($products as $p): ?>
                            <tr>
                                <td class="text-center fw-bold text-muted"><?= $p['product_id']?></td>
                                <td>
                                    <img src="image/<?= $p['image_url'] ?>" class="product-img" alt="laptop">
                                </td>
                                <td>
                                    <div class="product-name-truncate" title="<?= htmlspecialchars($p['product_name']) ?>">
                                        <?= htmlspecialchars($p['product_name']) ?>
                                    </div>
                                    <small class="text-muted">Laptop Gaming / Office</small>
                                </td>
                                <td class="fw-bold text-danger">
                                    <?= number_format($p['price'], 0, ',', '.') ?>đ
                                </td>
                                <td class="text-center">
                                    <a href="edit_product.php?id=<?= $p['product_id']?>" class="btn btn-warning btn-action text-white" title="Sửa">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <a href="delete_product.php?id=<?= $p['product_id']?>" class="btn btn-danger btn-action" 
                                       onclick="return confirm('Bạn chắc chắn muốn xóa sản phẩm này?')" title="Xóa">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Bootstrap Style -->
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $current_page - 1 ?>"><i class="fa-solid fa-chevron-left"></i></a>
                        </li>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $current_page + 1 ?>"><i class="fa-solid fa-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<footer class="py-4 mt-5 text-center">
    <div class="container text-center">
        <p class="mb-1 small">© 2026 - Đồ án Chuyên ngành Công nghệ thông tin</p>
        <p class="mb-0 x-small opacity-50">Dữ liệu sản phẩm được tổng hợp từ FPT-Shop và FIT-TDC 2019</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>