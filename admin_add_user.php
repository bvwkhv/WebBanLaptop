<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
require_once "database.php";

$db = new Database();
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    // 1. Kiểm tra không được để trống các trường bắt buộc
    if (!empty($username) && !empty($email) && !empty($password) && !empty($phone)) {
        
        // Chiều dài các chuỗi ký tự
        $username_len = mb_strlen($username, 'UTF-8');
        $email_len = strlen($email);
        $password_len = strlen($password);
        $phone_len = strlen($phone);

        // --- RÀNG BUỘC BẢO MẬT BACKEND (PHP VALIDATION) ---

        // 2. Kiểm tra Họ tên (Độ dài 1 - 100, KHÔNG chứa ký tự đặc biệt, KHÔNG chứa số)
        // \p{L} là đại diện cho tất cả chữ cái (bao gồm tiếng Việt), \s là khoảng trắng
        if ($username_len < 1 || $username_len > 100) {
            $error = "Họ tên phải có độ dài từ 1 đến 100 ký tự!";
        } 
        elseif (!preg_match('/^[\p{L}\s]+$/u', $username)) {
            $error = "Họ tên chỉ được phép chứa chữ cái và khoảng trắng (không chứa số hoặc ký tự đặc biệt)!";
        }
        
        // 3. Kiểm tra Email (Đúng định dạng, Độ dài 5 - 100)
        elseif ($email_len < 5 || $email_len > 100) {
            $error = "Email phải có độ dài từ 5 đến 100 ký tự!";
        }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Định dạng Email nhập vào không hợp lệ!";
        }

        // 4. Kiểm tra Số điện thoại (Chỉ chứa số, Độ dài 10 - 11)
        elseif ($phone_len < 10 || $phone_len > 11) {
            $error = "Số điện thoại phải có độ dài từ 10 đến 11 ký tự!";
        }
        elseif (!preg_match('/^[0-9]+$/', $phone)) {
            $error = "Số điện thoại chỉ được chứa các ký tự số, không chứa chữ hoặc khoảng trắng!";
        }

        // 5. Kiểm tra Mật khẩu (Độ dài 6 - 100)
        elseif ($password_len < 6 || $password_len > 100) {
            $error = "Mật khẩu phải có độ dài từ 6 đến 100 ký tự!";
        }
        
        else {
            // 6. Kiểm tra trùng lặp Email hoặc Số điện thoại trong Hệ thống
            $check_sql = "SELECT user_id FROM users WHERE email = ? OR phone = ?";
            $existing = $db->select($check_sql, "ss", [$email, $phone]);

            if (!empty($existing)) {
                $error = "Email hoặc Số điện thoại này đã được sử dụng bởi tài khoản khác!";
            } else {
                // Tiến hành mã hóa mật khẩu và chèn vào Database
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $sql = "INSERT INTO users (username, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?)";
                $db->execute($sql, "ssssss", [$username, $email, $phone, $hashed_password, $role, $status]);
                
                $success = "Thêm người dùng mới thành công!";
                header("Refresh: 1.5; url=admin_users.php");
            }
        }
    } else {
        $error = "Vui lòng điền đầy đủ các thông tin bắt buộc, không được để trống.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm người dùng mới</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Inter', sans-serif; padding-top: 50px; }
        .form-card { background: white; border-radius: 16px; border: none; box-shadow: 0 4px 25px rgba(0,0,0,0.06); max-width: 600px; margin: 0 auto; padding: 35px; }
        .form-control { border-radius: 10px; padding: 11px 15px; border-color: #dee2e6; font-size: 0.95rem; }
        .form-control:focus { box-shadow: 0 0 0 3px rgba(215, 0, 24, 0.15); border-color: #d70018; }
        .btn-submit { background-color: #d70018; color: white; border: none; transition: 0.2s; }
        .btn-submit:hover { background-color: #b50012; color: white; }
        .radio-custom-group { background-color: #f8f9fa; padding: 12px 20px; border-radius: 10px; border: 1px solid #dee2e6; }
        .form-check-input:checked { background-color: #d70018; border-color: #d70018; }
    </style>
</head>
<body>
    <div class="container mb-5">
        <div class="card form-card">
            <h4 class="fw-bold text-dark text-center mb-4 text-uppercase">Thêm người dùng mới</h4>
            
            <?php if($error): ?> <div class="alert alert-danger border-0 small rounded-3"><?= $error ?></div> <?php endif; ?>
            <?php if($success): ?> <div class="alert alert-success border-0 small rounded-3"><?= $success ?></div> <?php endif; ?>

            <form action="admin_add_user.php" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small">Họ Tên:</label>
                    <input type="text" name="username" class="form-control" required 
                           placeholder="Nhập tên người dùng" maxlength="100"
                           pattern="^[a-zA-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂÂÊÔƠỚ́́́́́uưăâêôớ́́́́́\s]+$"
                           title="Họ tên từ 1-100 ký tự, chỉ chứa chữ cái và khoảng trắng, không chứa số hay ký tự đặc biệt.">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small">Số điện thoại:</label>
                    <input type="text" name="phone" class="form-control" required 
                           placeholder="Nhập số điện thoại (10 - 11 số)" 
                           pattern="[0-9]{10,11}" maxlength="11"
                           title="Số điện thoại phải từ 10 đến 11 ký tự số, không chứa chữ hay ký tự đặc biệt.">
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small">Email:</label>
                    <input type="email" name="email" class="form-control" required 
                           placeholder="example@gmail.com" minlength="5" maxlength="100">
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small">Mật khẩu:</label>
                    <input type="password" name="password" class="form-control" required 
                           placeholder="Mật khẩu từ 6 - 100 ký tự" minlength="6" maxlength="100">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small">Vai trò hệ thống:</label>
                    <div class="d-flex gap-4 radio-custom-group">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="role" id="roleUser" value="user" checked>
                            <label class="form-check-label fw-medium text-dark" for="roleUser">
                                <i class="bi bi-person me-1"></i> User
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="role" id="roleAdmin" value="admin">
                            <label class="form-check-label fw-medium text-dark" for="roleAdmin">
                                <i class="bi bi-star me-1"></i> Admin
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold text-secondary small">Trạng thái tài khoản:</label>
                    <div class="d-flex gap-4 radio-custom-group">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="statusActive" value="Hoạt động" checked>
                            <label class="form-check-label fw-medium text-dark" for="statusActive">
                                <i class="bi bi-check-circle text-success me-1"></i> Hoạt động
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="statusLocked" value="Đã khóa">
                            <label class="form-check-label fw-medium text-dark" for="statusLocked">
                                <i class="bi bi-x-circle text-danger me-1"></i> Đã khóa
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-submit w-50 py-2.5 rounded-pill fw-bold">
                        <i class="bi bi-check-lg me-1"></i> LƯU LẠI
                    </button>
                    <a href="admin_users.php" class="btn btn-light w-50 py-2.5 rounded-pill fw-bold border text-secondary">
                        HỦY BỎ
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>