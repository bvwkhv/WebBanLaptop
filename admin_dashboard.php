<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
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
<body class="d-flex flex-column min-vh-100">
    <div class="flex-grow-1">

    
    <!-- navbar -->
    <nav class="navbar navbar-expand-lg navbar-light border-bottom">
  <div class="container">
    <!-- navbar rand -->
    <a class="navbar-brand home" href="index.php">Home</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-between" id="navbarSupportedContent">
        <!-- menu item -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

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
        <li><a class="dropdown-item" href="profile.php">Thông tin tài khoản</a></li>
        
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

    <a href="#" class="btn btn-danger btn-sm d-inline-flex justify-content-center align-items-center shopping-cart ms-2">
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

    <div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2">
            <div class="list-group shadow-sm">
                <a href="dashboard.php" class="list-group-item list-group-item-action active" style="background-color: #FF8C00; border-color: #FF8C00;">
                    Quản lý sản phẩm
                </a>
                <a href="manage_orders.php" class="list-group-item list-group-item-action">Quản lý đơn hàng</a>
                <a href="manage_users.php" class="list-group-item list-group-item-action">Quản lý khách hàng</a>
            </div>
        </div>

        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Danh sách sản phẩm Laptop</h5>
                    <a href="product_add.php" class="btn btn-success btn-sm">+ Thêm sản phẩm</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Hình ảnh</th>
                                    <th>Tên Laptop</th>
                                    <th>Giá bán</th>
                                    <th>Số lượng</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td><img src="../uploads/laptop1.jpg" width="50" alt=""></td>
                                    <td>MSI Modern 14</td>
                                    <td>15.000.000đ</td>
                                    <td>10</td>
                                    <td>
                                        <a href="edit.php?id=1" class="btn btn-warning btn-sm">Sửa</a>
                                        <a href="delete.php?id=1" class="btn btn-danger btn-sm" onclick="return confirm('Xóa nhé?')">Xóa</a>
                                        <a href="edit.php?id=1" class="btn btn-primary btn-sm">Chi tiết</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
        <!-- Footer -->
        <footer class="text-bg-dark py-5">
        <div>
            <!-- <p>© 2026 - Đồ án Chuyên ngành Công nghệ thông tin</p> -->
            <!-- <p>Dữ liệu sản phẩm được tổng hợp từ FPT-Shop và FIT-TDC 2019</p> -->
        </div>
        </footer>

    <!-- Nhúng js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    