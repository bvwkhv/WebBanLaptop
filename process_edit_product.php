<?php
require_once "database.php";
$db = new database();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['product_id'];
    $brand_id = $_POST['brand_id'];
    $product_name = $_POST['product_name'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    // Xử lý ảnh: Nếu có upload ảnh mới thì lấy ảnh mới, không thì dùng ảnh cũ
    $image_name = $_POST['old_image']; 
    if (!empty($_FILES['image']['name'])) {
        $image_name = $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "image/" . $image_name);
    }

    // 1. Cập nhật bảng products
    $sql_product = "UPDATE products SET brand_id=?, product_name=?, price=?, image_url=?, description=? WHERE product_id=?";
    $db->execute($sql_product, 'isdssi', [$brand_id, $product_name, $price, $image_name, $description, $id]);

    // 2. Cập nhật bảng product_specs
    $sql_specs = "UPDATE product_specs SET cpu=?, ram=?, storage=?, gpu=?, screen=? WHERE product_id=?";
    $db->execute($sql_specs, 'sssssi', [$_POST['cpu'], $_POST['ram'], $_POST['storage'], $_POST['gpu'], $_POST['screen'], $id]);

    echo "<script>alert('Cập nhật thành công!'); window.location='admin_dashboard.php';</script>";
}
?>