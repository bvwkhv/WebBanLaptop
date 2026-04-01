<?php
require_once "database.php";
$db = new database();

// 1. Lấy ID sản phẩm từ URL
$id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id) {
    header("Location: admin_dashboard.php");
    exit();
}

// 2. Truy vấn lấy thông tin sản phẩm và thông số kỹ thuật
$sql = "SELECT p.*, s.cpu, s.ram, s.storage, s.gpu, s.screen 
        FROM products p 
        LEFT JOIN product_specs s ON p.product_id = s.product_id 
        WHERE p.product_id = ?";
$product_data = $db->select($sql, 'i', [$id]);
$p = $product_data[0];

// 3. Lấy danh sách hãng để đổ vào dropdown
$brands = $db->select("SELECT * FROM brands");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/style_add_product.css">
    <title>Document</title>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h2>Sửa sản phẩm</h2>
        </div>
        
        <form action="process_edit_product.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
            <input type="hidden" name="old_image" value="<?= $p['image_url'] ?>">
    
            <div class="row">
                <div class="col">
                    <div class="input-group">
                        <label>Hãng:</label>
                        <select name="brand_id">
                        <?php foreach($brands as $b): ?>
                        <option value="<?= $b['brand_id'] ?>" <?= ($b['brand_id'] == $p['brand_id']) ? 'selected' : '' ?>>
                        <?= $b['brand_name'] ?>
                        </option>
                        <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Tên laptop:</label>
                        <input type="text" name="product_name" value="<?= $p['product_name'] ?>" placeholder="VD: Modern 14 B11MOU" required>
                    </div>

                    <div class="input-group">
                        <label>Giá bán (VNĐ):</label>
                        <input type="number" name="price" value="<?= $p['price']?>" placeholder="VD: 15000000" required>
                    </div>

                    <div class="input-group">
                        <label>Ảnh sản phẩm:</label>
                        <img src="image/<?= $p['image_url'] ?>" width="80" style="margin-bottom: 10px;">
                        <input type="file" name="image" accept="image/*">
                    </div>
                </div>

                <div class="col">
                    <div class="input-group">
                        <label>CPU:</label>
                        <input type="text" name="cpu" value="<?= $p['cpu']?>" placeholder="i5-1135G7">
                    </div>

                    <div class="input-group">
                        <label>RAM:</label>
                        <input type="text" name="ram" value="<?= $p['ram']?>" placeholder="8GB DDR4">
                    </div>

                    <div class="input-group">
                        <label>Ổ cứng:</label>
                        <input type="text" name="storage" value="<?= $p['storage']?>" placeholder="512GB SSD">
                    </div>

                    <div class="input-group">
                        <label>GPU:</label>
                        <input type="text" name="gpu" value="<?= $p['gpu']?>" placeholder="Intel Iris Xe">
                    </div>

                    <div class="input-group">
                        <label>Màn hình:</label>
                        <input type="text" name="screen" value="<?= $p['screen']?>" placeholder="14 inch FHD">
                    </div>
                </div>
            </div>

            <div class="input-group full-width">
                <label>Mô tả chi tiết:</label>
                <textarea name="description" rows="4" placeholder="Nhập đặc điểm nổi bật..."><?= $p['description'] ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">Cập nhật sản phẩm</button>
                <button type="button" class="btn-cancel" onclick="window.history.back()">Hủy bỏ</button>
            </div>
        </form>
    </div>
</body>
</html>