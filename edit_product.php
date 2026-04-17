<?php
require_once "database.php";
$db = new database();

$id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id) {
    header("Location: admin_dashboard.php");
    exit();
}

$sql = "SELECT p.*, s.cpu, s.ram, s.storage, s.gpu, s.screen 
        FROM products p 
        LEFT JOIN product_specs s ON p.product_id = s.product_id 
        WHERE p.product_id = ?";
$product_data = $db->select($sql, 'i', [$id]);

if (empty($product_data)) {
    header("Location: admin_dashboard.php");
    exit();
}
$p = $product_data[0];
$brands = $db->select("SELECT * FROM brands");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/style_add_product.css">
    <title>Sửa sản phẩm</title>
    <style>
        .input-group input.is-invalid, .input-group select.is-invalid, .input-group textarea.is-invalid {
            border: 1px solid #dc3545 !important;
        }
        .form-text { font-size: 0.75rem; color: #666; margin-top: 2px; }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h2>Cập nhật sản phẩm</h2>
        </div>
        
        <form action="process_edit_product.php" method="POST" enctype="multipart/form-data" id="editProductForm">
            <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
            <input type="hidden" name="old_image" value="<?= $p['image_url'] ?>">
    
            <div class="row">
                <div class="col">
                    <div class="input-group">
                        <label>Hãng:</label>
                        <select name="brand_id" required>
                            <?php foreach($brands as $b): ?>
                                <option value="<?= $b['brand_id'] ?>" <?= ($b['brand_id'] == $p['brand_id']) ? 'selected' : '' ?>>
                                    <?= $b['brand_name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Tên laptop:</label>
                        <input type="text" name="product_name" id="product_name" value="<?= htmlspecialchars($p['product_name']) ?>" maxlength="200" required>
                        <div class="form-text">Cho phép chữ, số và các ký tự: / " . _ - ( )</div>
                    </div>

                    <div class="input-group">
                        <label>Giá bán (VNĐ):</label>
                        <input type="number" name="price" value="<?= $p['price']?>" min="0" required>
                    </div>

                    <div class="input-group">
                        <label>Ảnh sản phẩm:</label>
                        <div style="margin-bottom: 10px;">
                            <small>Ảnh hiện tại:</small><br>
                            <img src="image/<?= $p['image_url'] ?>" width="80" class="img-thumbnail">
                        </div>
                        <input type="file" name="image" id="imageInput" accept="image/png, image/jpeg, image/jpg, image/webp">
                        <div class="form-text">Bỏ trống nếu không muốn đổi ảnh.</div>
                    </div>
                </div>

                <div class="col">
                    <div class="input-group">
                        <label>CPU:</label>
                        <input type="text" name="cpu" value="<?= htmlspecialchars($p['cpu']) ?>" maxlength="100" required>
                    </div>

                    <div class="input-group">
                        <label>RAM:</label>
                        <input type="text" name="ram" value="<?= htmlspecialchars($p['ram']) ?>" maxlength="50" required>
                    </div>

                    <div class="input-group">
                        <label>Ổ cứng:</label>
                        <input type="text" name="storage" value="<?= htmlspecialchars($p['storage']) ?>" maxlength="100" required>
                    </div>

                    <div class="input-group">
                        <label>GPU:</label>
                        <input type="text" name="gpu" value="<?= htmlspecialchars($p['gpu']) ?>" maxlength="100" required>
                    </div>

                    <div class="input-group">
                        <label>Màn hình:</label>
                        <input type="text" name="screen" value="<?= htmlspecialchars($p['screen']) ?>" maxlength="100" required>
                    </div>
                </div>
            </div>

            <div class="input-group full-width">
                <label>Mô tả chi tiết:</label>
                <textarea name="description" rows="4" maxlength="500"><?= htmlspecialchars($p['description']) ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">Cập nhật sản phẩm</button>
                <button type="button" class="btn-cancel" onclick="window.history.back()">Hủy bỏ</button>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('editProductForm').addEventListener('submit', function(e) {
        let isValid = true;
        const form = e.target;
        
        // 1. Kiểm tra Tên sản phẩm (Hỗ trợ các ký tự đặc biệt trong tên Laptop)
        const productName = document.getElementById('product_name').value.trim();
        const nameRegex = /^[a-zA-Z0-9\sàáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđĐ\-\/\.\"\_\(\)]+$/;

        if (!nameRegex.test(productName)) {
            alert('Tên sản phẩm chứa ký tự lạ không hợp lệ hoặc chữ tượng hình!');
            document.getElementById('product_name').classList.add('is-invalid');
            isValid = false;
        }

        // 2. Kiểm tra định dạng ảnh (Nếu có chọn file mới)
        const imageInput = document.getElementById('imageInput');
        if (imageInput.files.length > 0) {
            const allowedExtensions = /(\.jpg|\.jpeg|\.png|\.webp)$/i;
            if (!allowedExtensions.exec(imageInput.value)) {
                alert('Vui lòng chọn file ảnh hợp lệ (jpg, jpeg, png, webp).');
                isValid = false;
            }
        }

        // 3. Kiểm tra các trường bắt buộc (không để trống/khoảng trắng)
        const requiredInputs = form.querySelectorAll('input[required], select[required]');
        requiredInputs.forEach(input => {
            if (input.value.trim() === "") {
                input.classList.add('is-invalid');
                isValid = false;
            }
        });

        if (!isValid) {
            e.preventDefault();
        }
    });

    // Bỏ viền đỏ khi gõ lại
    document.querySelectorAll('input, select').forEach(element => {
        element.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    });
    </script>
</body>
</html>