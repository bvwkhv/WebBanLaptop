<?php
require_once "database.php";
$db = new database();

// 1. Lấy ID từ URL
$id = isset($_GET['id']) ? $_GET['id'] : null;

if ($id) {
    // 2. Lấy tên ảnh trước khi xóa để xóa file trong thư mục (tiết kiệm bộ nhớ server)
    $sql_img = "SELECT image_url FROM products WHERE product_id = ?";
    $product = $db->select($sql_img, 'i', [$id]);
    
    if (count($product) > 0) {
        $image_name = $product[0]['image_url'];
        $image_path = "image/" . $image_name;

        // 3. Xóa dữ liệu trong Database
        // Xóa ở bảng product_specs trước (bảng phụ)
        $db->execute("DELETE FROM product_specs WHERE product_id = ?", 'i', [$id]);
        
        // Xóa ở bảng products (bảng chính)
        $result = $db->execute("DELETE FROM products WHERE product_id = ?", 'i', [$id]);

        if ($result) {
            // 4. Nếu xóa DB thành công thì xóa luôn file ảnh trong thư mục image
            if (file_exists($image_path) && $image_name != "") {
                unlink($image_path); 
            }
            echo "<script>alert('Xóa sản phẩm thành công!'); window.location='admin_dashboard.php';</script>";
        } else {
            echo "<script>alert('Lỗi: Không thể xóa sản phẩm.'); window.location='admin_dashboard.php';</script>";
        }
    }
} else {
    header("Location: admin_dashboard.php");
}
?>