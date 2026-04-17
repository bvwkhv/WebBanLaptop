<?php
    require_once "database.php";
    $db = new database();
    $sql_brands = "SELECT * FROM brands";
    $result_brands = $db->select($sql_brands);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/style_add_product.css">
    <title>Thêm sản phẩm mới</title>
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
            <h2>Thêm sản phẩm mới</h2>
        </div>
        
        <form action="process_add_product.php" method="POST" enctype="multipart/form-data" id="addProductForm">
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
                        <input type="text" name="product_name" id="product_name" maxlength="200" placeholder="VD: MSI Gaming Katana 15..." required>
                        <div class="form-text">Cho phép chữ, số và các ký tự: / " . _ - ( )</div>
                    </div>

                    <div class="input-group">
                        <label>Giá bán (VNĐ):</label>
                        <input type="number" name="price" min="0" placeholder="VD: 15000000" required>
                    </div>

                    <div class="input-group">
                        <label>Ảnh sản phẩm:</label>
                        <input type="file" name="image" id="imageInput" accept="image/png, image/jpeg, image/jpg, image/webp" required>
                        <div class="form-text">Định dạng: JPG, PNG, WEBP.</div>
                    </div>
                </div>

                <div class="col">
                    <div class="input-group">
                        <label>CPU:</label>
                        <input type="text" name="cpu" maxlength="100" placeholder="i7 13620H" required>
                    </div>

                    <div class="input-group">
                        <label>RAM:</label>
                        <input type="text" name="ram" maxlength="50" placeholder="16GB DDR5" required>
                    </div>

                    <div class="input-group">
                        <label>Ổ cứng:</label>
                        <input type="text" name="storage" maxlength="100" placeholder="1TB SSD" required>
                    </div>

                    <div class="input-group">
                        <label>GPU:</label>
                        <input type="text" name="gpu" maxlength="100" placeholder="RTX 4060 8GB" required>
                    </div>

                    <div class="input-group">
                        <label>Màn hình:</label>
                        <input type="text" name="screen" maxlength="100" placeholder="15.6 inch FHD" required>
                    </div>
                </div>
            </div>

            <div class="input-group full-width">
                <label>Mô tả chi tiết:</label>
                <textarea name="description" rows="4" maxlength="500" placeholder="Nhập đặc điểm nổi bật..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">Lưu sản phẩm</button>
                <button type="button" class="btn-cancel" onclick="window.history.back()">Hủy bỏ</button>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('addProductForm').addEventListener('submit', function(e) {
        let isValid = true;
        const form = e.target;
        
        // 1. Kiểm tra Tên sản phẩm (Chấp nhận tên dài có thông số kỹ thuật)
        const productName = document.getElementById('product_name').value.trim();
        // Regex hỗ trợ tiếng Việt + số + dấu gạch ngang, xẹt, ngoặc kép, chấm, gạch dưới, ngoặc đơn
        const nameRegex = /^[a-zA-Z0-9\sàáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđĐ\-\/\.\"\_\(\)]+$/;

        if (!nameRegex.test(productName)) {
            alert('Tên sản phẩm chứa ký tự đặc biệt không hợp lệ hoặc chữ tượng hình!');
            document.getElementById('product_name').classList.add('is-invalid');
            isValid = false;
        }

        // 2. Kiểm tra ảnh
        const imageInput = document.getElementById('imageInput');
        if (imageInput.files.length > 0) {
            const allowedExtensions = /(\.jpg|\.jpeg|\.png|\.webp)$/i;
            if (!allowedExtensions.exec(imageInput.value)) {
                alert('Vui lòng chọn file ảnh hợp lệ (jpg, jpeg, png, webp).');
                isValid = false;
            }
        }

        // 3. Kiểm tra các trường bắt buộc khác
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