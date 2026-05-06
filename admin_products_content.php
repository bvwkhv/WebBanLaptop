<?php
require_once "database.php";
$db = new database();

$limit = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $limit;

$total_rows = $db->count("SELECT COUNT(*) FROM products");
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT * FROM products LIMIT ? OFFSET ?";
$products = $db->select($sql, 'ii', [$limit, $offset]);
?>

<style>
    /* CSS dành riêng cho bảng nếu cần */
    .product-img { width: 60px; height: 45px; object-fit: contain; border-radius: 6px; border: 1px solid #eee; }
    .product-name-truncate { max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 500; }
    .btn-action { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; margin-right: 5px; color: white !important; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0 text-dark">Quản lý sản phẩm</h4>
    <a href="add_product.php" class="btn btn-primary shadow-sm rounded-pill px-4">
        <i class="fa-solid fa-plus me-2"></i>Thêm Laptop
    </a>
</div>

<div class="card shadow-sm border-0 p-3 rounded-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
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
                    <td><img src="image/<?= $p['image_url'] ?>" class="product-img"></td>
                    <td>
                        <div class="product-name-truncate"><?= htmlspecialchars($p['product_name']) ?></div>
                        <small class="text-muted">Laptop TDC Store</small>
                    </td>
                    <td class="fw-bold text-danger"><?= number_format($p['price'], 0, ',', '.') ?>đ</td>
                    <td class="text-center">
                        <a href="edit_product.php?id=<?= $p['product_id']?>" class="btn btn-warning btn-action"><i class="fa-solid fa-pen"></i></a>
                        <a href="delete_product.php?id=<?= $p['product_id']?>" class="btn btn-danger btn-action" onclick="return confirm('Xóa?')"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Phân trang (Lưu ý: trong AJAX, phân trang cần gọi lại loadContent thay vì href) -->
    <nav class="mt-3">
        <ul class="pagination justify-content-center border-0">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                    <button class="page-link border-0 mx-1 rounded-2" onclick="loadContent('admin_products_content.php?page=<?= $i ?>', document.querySelector('.sidebar-link.active'))">
                        <?= $i ?>
                    </button>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>