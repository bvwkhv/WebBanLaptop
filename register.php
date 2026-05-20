<?php
    require_once "database.php";
    if(isset($_POST['dangky'])){
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password_raw = $_POST['password']; // Lấy mật khẩu chưa mã hóa để kiểm tra độ dài
        $role = 'user';
        $status = 'Hoạt động';

        // --- LỚP BẢO MẬT BACKEND (PHP VALIDATION) ---
        
        // 1. Kiểm tra định dạng Email chuẩn
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "<script>alert('Lỗi: Định dạng Email không hợp lệ!'); history.back();</script>";
            exit();
        }

        // 2. Kiểm tra độ dài mật khẩu (từ 6 ký tự trở lên)
        if (strlen($password_raw) < 6) {
            echo "<script>alert('Lỗi: Mật khẩu phải từ 6 ký tự trở lên!'); history.back();</script>";
            exit();
        }

        $password = password_hash($password_raw, PASSWORD_DEFAULT);
        $db = new database();

        // BƯỚC KIỂM TRA TRÙNG LẶP: Tìm xem có ai dùng username, email hoặc phone này chưa
        $check_sql = "SELECT * FROM users WHERE username = '$username' OR email = '$email' OR phone = '$phone'";
        $result = $db->select($check_sql);

        if(count($result) > 0){
            echo "<script>alert('Lỗi: Tên đăng nhập, Email hoặc Số điện thoại đã được sử dụng!'); history.back();</script>";
        }
        else{
            $sql = "INSERT INTO users(username, password, email, phone, role, status)
                    VALUES('$username', '$password', '$email', '$phone', '$role', '$status')";
        
            if($db->execute($sql)){
                echo "<script>alert('Đăng ký thành công!'); window.location='login.php';</script>";
            }
            else{
                echo "<script>alert('Có lỗi xảy ra khi đăng ký!'); history.back();</script>";
            }
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
    <title>Đăng ký</title>
</head>
<body>
    <div class="container">
    <h2>Đăng ký</h2>

    <form action="register.php" method="post">
        <input type="text" placeholder="Tên tài khoản" name="username" required>
        
        <input type="email" placeholder="Email" name="email" required>
        
        <input type="text" placeholder="Số điện thoại" name="phone" required pattern="[0-9]{10,11}" title="Vui lòng nhập đúng số điện thoại từ 10-11 chữ số">
        
        <input type="password" placeholder="Mật khẩu (từ 6 ký tự trở lên)" name="password" required minlength="6" title="Mật khẩu phải chứa ít nhất 6 ký tự">
        
        <button type="submit" name="dangky">Đăng ký</button>
    </form>

    <div class="link">
        Đã có tài khoản? <a href="login.php">Đăng nhập</a>
    </div>
</div>
</body>
</html>