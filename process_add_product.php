<?php
require_once "database.php";
$db = new database();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Lấy dữ liệu từ Form (Thông tin chung)
    $brand_id     = $_POST['brand_id'];
    $product_name = $_POST['product_name'];
    $price        = $_POST['price'];
    $description  = $_POST['description'];

    // 2. Xử lý Upload Ảnh
    $image_name = $_FILES['image']['name'];
    $target_dir = "image/"; // Đường dẫn thư mục chứa ảnh
    $target_file = $target_dir . basename($image_name);
    
    // Di chuyển file vào thư mục uploads
    if (!empty($image_name)) {
        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
    }

    // 3. CHÈN VÀO BẢNG products
    // 'isdss' tương ứng: int (brand_id), string (name), double (price), string (image), string (desc)
    $sql_product = "INSERT INTO products (brand_id, product_name, price, image_url, description) 
                    VALUES (?, ?, ?, ?, ?)";
    
    $params_product = [$brand_id, $product_name, $price, $image_name, $description];
    $insert_p = $db->execute($sql_product, 'isdss', $params_product);

    if ($insert_p) {
        // 4. LẤY ID VỪA TẠO (Sử dụng hàm mới thêm vào database.php)
        $last_id = $db->lastInsertId();

        // 5. Lấy dữ liệu thông số kỹ thuật (product_specs)
        $cpu     = $_POST['cpu'];
        $ram     = $_POST['ram'];
        $storage = $_POST['storage'];
        $gpu     = $_POST['gpu'];
        $screen  = $_POST['screen'];

        // 6. CHÈN VÀO BẢNG product_specs
        $sql_specs = "INSERT INTO product_specs (product_id, cpu, ram, storage, gpu, screen) 
                    VALUES (?, ?, ?, ?, ?, ?)";
        
        $params_specs = [$last_id, $cpu, $ram, $storage, $gpu, $screen];
        $insert_s = $db->execute($sql_specs, 'isssss', $params_specs);

        if ($insert_s) {
            echo "<script>alert('Thêm sản phẩm thành công!'); window.location='product_list.php';</script>";
        } else {
            echo "Lỗi khi thêm thông số kỹ thuật.";
        }
    } else {
        echo "Lỗi khi thêm sản phẩm chính.";
    }
}

// Ngắt kết nối
$db->close();
?>