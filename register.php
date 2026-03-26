<?php
    require_once "database.php";
    if(isset($_POST['dangky'])){
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = 'user';

        $db = new database();
        // BƯỚC KIỂM TRA: Tìm xem có ai dùng username hoặc email này chưa
        $check_sql = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
        $result = $db->select($check_sql);

        if(count($result) > 0){
            // Nếu mảng có phần tử, nghĩa là đã tồn tại
            echo "<script>alert('Lỗi: Tên đăng nhập hoặc Email đã được sử dụng!'); history.back();</script>";
        }
        else{
        $sql = "INSERT INTO users(username,password,email,role)
                VALUES('$username','$password','$email','$role')";
        
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
    <title>Document</title>
</head>
<body>
    <div class="container">
    <h2>Đăng ký</h2>

    <form action="register.php" method="post">
        <input type="text" placeholder="Tên tài khoản" name="username" required>
        <input type="email" placeholder="Email" name="email" required>
        <input type="password" placeholder="Mật khẩu" name="password" required>
        <button type="submit" name="dangky">Đăng ký</button>
    </form>

    <div class="link">
        Đã có tài khoản? <a href="login.php">Đăng nhập</a>
    </div>
</div>
</body>
</html>