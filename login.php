<?php
    require_once "database.php";
    session_start();

    if(isset($_POST['dangnhap'])){
        $username = $_POST['username'];
        $password = $_POST['password'];

        $db = new database();
        // 1. Chỉ tìm theo Username
        $sql = "SELECT * FROM users WHERE username = '$username'";
        $result = $db->select($sql);

        if ($result && count($result) > 0) {
            $row = $result[0];

            // 2. Kiểm tra mật khẩu đã mã hóa
            if (password_verify($password, $row['password'])) {
                
                // Lưu thông tin vào Session
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];

                // 3. Điều hướng dựa trên quyền (ENUM: 'admin', 'user')
                if ($row['role'] == "admin") {
                    header("Location: admin_dashboard.php"); // Trang quản trị
                } else {
                    header("Location: index.php"); // Trang chủ khách hàng
                }
                exit();
            } else {
                echo "<script>alert('Sai mật khẩu!'); history.back();</script>";
            }
        } else {
            echo "<script>alert('Tên đăng nhập không tồn tại!'); history.back();</script>";
        }
        $db->close();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/styles2.css">
    <title>Document</title>
</head>
<body>
    <div class="container">
    <h2>Đăng nhập</h2>

    <form action="login.php" method="post">
        <input type="text" placeholder="Tên tài khoản" name="username" required>
        <input type="password" placeholder="Mật khẩu" name="password" required>
        <button type="submit" name="dangnhap">Đăng nhập</button>
    </form>

    <div class="link">
        Chưa có tài khoản? <a href="register.php">Đăng ký</a>
    </div>
</div>
</body>
</html>