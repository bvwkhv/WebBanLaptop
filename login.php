<?php
    require_once "database.php";
    session_start();

    if(isset($_POST['dangnhap'])){
        // Đổi tên biến thành $login_input để đại diện cho cả Email hoặc Phone
        $login_input = trim($_POST['login_input']);
        $password = $_POST['password'];

        $db = new database();
        
        // CẬP NHẬT LẠI SQL: Tìm kiếm tài khoản trùng khớp với Email HOẶC Số điện thoại
        $sql = "SELECT * FROM users WHERE email = '$login_input' OR phone = '$login_input'";
        $result = $db->select($sql);

        if ($result && count($result) > 0) {
            $row = $result[0];

            // Kiểm tra mật khẩu đã mã hóa
            if (password_verify($password, $row['password'])) {
                
                // Kiểm tra trạng thái tài khoản
                if (isset($row['status']) && $row['status'] === 'Đã khóa') {
                    echo "<script>
                        alert('Tài khoản của bạn đã bị khóa và không thể đăng nhập vào'); 
                        history.back();
                    </script>";
                    exit(); 
                }
                
                // Lưu thông tin vào Session
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];

                // Điều hướng dựa trên quyền
                if ($row['role'] == "admin") {
                    header("Location: admin_dashboard.php"); 
                } else {
                    header("Location: index.php"); 
                }
                exit();
            } else {
                echo "<script>alert('Sai mật khẩu hoặc tài khoản!'); history.back();</script>";
            }
        } else {
            echo "<script>alert('Tài khoản (Email hoặc Số điện thoại) không tồn tại!'); history.back();</script>";
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
    <title>Đăng nhập</title>
</head>
<body>
    <div class="container">
    <h2>Đăng nhập</h2>

    <form action="login.php" method="post">
        <input type="text" placeholder="Email hoặc Số điện thoại" name="login_input" required>
        <input type="password" placeholder="Mật khẩu" name="password" required>
        <button type="submit" name="dangnhap">Đăng nhập</button>
    </form>

    <div class="link">
        Chưa có tài khoản? <a href="register.php">Đăng ký</a>
    </div>
</div>
</body>
</html>