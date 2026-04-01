<?php
    require_once "database.php";

    $db = new database();
    //Lấy danh sách Hãng đổ vào dropdown
    $sql_brands = "SELECT * FROM brands";
    $result_brands = $db->select($sql_brands);

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
            <h2>Thêm sản phẩm mới</h2>
        </div>
        
        <form action="process_add_product.php" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col">
                    <div class="input-group">
                        <label>Hãng:</label>
                        <select name="brand_id" required>
                            <option value="">-- Chọn hãng --</option>
                            <?php foreach($result_brands as $rb){?>
                            <option value="<?= $rb['brand_id']?>"><?= $rb['brand_name']?></option>
                            <?php }?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Tên laptop:</label>
                        <input type="text" name="product_name" placeholder="VD: Modern 14 B11MOU" required>
                    </div>

                    <div class="input-group">
                        <label>Giá bán (VNĐ):</label>
                        <input type="number" name="price" placeholder="VD: 15000000" required>
                    </div>

                    <div class="input-group">
                        <label>Ảnh sản phẩm:</label>
                        <input type="file" name="image" accept="image/*" required>
                    </div>
                </div>

                <div class="col">
                    <div class="input-group">
                        <label>CPU:</label>
                        <input type="text" name="cpu" placeholder="i5-1135G7">
                    </div>

                    <div class="input-group">
                        <label>RAM:</label>
                        <input type="text" name="ram" placeholder="8GB DDR4">
                    </div>

                    <div class="input-group">
                        <label>Ổ cứng:</label>
                        <input type="text" name="storage" placeholder="512GB SSD">
                    </div>

                    <div class="input-group">
                        <label>GPU:</label>
                        <input type="text" name="gpu" placeholder="Intel Iris Xe">
                    </div>

                    <div class="input-group">
                        <label>Màn hình:</label>
                        <input type="text" name="screen" placeholder="14 inch FHD">
                    </div>
                </div>
            </div>

            <div class="input-group full-width">
                <label>Mô tả chi tiết:</label>
                <textarea name="description" rows="4" placeholder="Nhập đặc điểm nổi bật..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">Lưu sản phẩm</button>
                <button type="button" class="btn-cancel" onclick="window.history.back()">Hủy bỏ</button>
            </div>
        </form>
    </div>
</body>
</html>