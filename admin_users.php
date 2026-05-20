<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
require_once "database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = new Database();

// --- XỬ LÝ TÌM KIẾM & PHÂN TRANG ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 5; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$where_clause = "WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    // Tìm kiếm linh hoạt theo Tên, Email và cả Số điện thoại mới
    $where_clause .= " AND (username LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_param = "%" . $search . "%";
    $params = [$search_param, $search_param, $search_param];
    $types = "sss";
}

// 1. Đếm số lượng dòng dữ liệu
$sql_count = "SELECT COUNT(*) as total FROM users $where_clause";
$total_res = !empty($params) ? $db->select($sql_count, $types, $params) : $db->select($sql_count);
$total_users = $total_res[0]['total'];
$total_pages = ceil($total_users / $limit);

// 2. Lấy dữ liệu phân trang đầy đủ các trường
$sql_users = "SELECT user_id, username, email, phone, role, status FROM users 
              $where_clause 
              ORDER BY user_id DESC LIMIT ? OFFSET ?";
$final_params = array_merge($params, [$limit, $offset]);
$final_types = $types . "ii";

$user_list = $db->select($sql_users, $final_types, $final_params);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Inter', sans-serif; padding-top: 30px; }
        .main-card { background: white; border-radius: 16px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.05); padding: 25px; }
        .table th { background-color: #f8f9fa; color: #495057; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.5px; }
        .table td { vertical-align: middle; font-size: 0.9rem; }
        .search-input { border-radius: 50px 0 0 50px; padding-left: 20px; border-color: #dee2e6; }
        .search-btn { border-radius: 0 50px 50px 0; background-color: #d70018; color: white; border: none; }
        .search-btn:hover { background-color: #b50012; }
        .btn-add { background-color: #d70018; color: white; border-radius: 50px; font-weight: 600; border: none; transition: 0.2s; }
        .btn-add:hover { background-color: #b50012; color: white; }
        
        .badge-status { padding: 6px 14px; border-radius: 50px; font-size: 0.75rem; font-weight: 600; }
        .badge-active { background-color: #e8f5e9; color: #2e7d32; }
        .badge-locked { background-color: #ffebee; color: #c62828; }

        .pagination .page-link { color: #d70018; border: none; margin: 0 3px; border-radius: 8px; font-weight: 500; }
        .pagination .page-item.active .page-link { background-color: #d70018; color: white; }
    </style>
</head>
<body>

    <div class="container pb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="admin_dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left"></i> Quay lại
                </a>
                <h3 class="fw-bold text-dark m-0">Quản lý người dùng</h3>
            </div>
            <a href="admin_add_user.php" class="btn btn-add px-4 py-2 shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Thêm người dùng mới
            </a>
        </div>

        <div class="card main-card">
            <div class="row mb-4">
                <div class="col-md-6">
                    <form action="admin_users.php" method="GET" class="input-group">
                        <input type="text" name="search" class="form-control search-input" 
                               value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Tìm kiếm tên thành viên, email, sđt...">
                        <button class="btn search-btn px-4" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>
                <div class="col-md-6 text-md-end d-flex align-items-center justify-content-md-end mt-2 mt-md-0">
                    <span class="text-muted small">Tổng số: <strong><?= $total_users ?></strong> thành viên</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="px-3" style="width: 80px;">ID</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Số điện thoại</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th class="text-end px-3" style="width: 150px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($user_list)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Không tìm thấy tài khoản nào phù hợp.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($user_list as $user): ?>
                                <tr>
                                    <td class="fw-bold px-3">#<?= $user['user_id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-weight: 600; font-size: 0.85rem;">
                                                <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                            </div>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($user['username']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td class="text-secondary font-monospace"><?= htmlspecialchars($user['phone'] ?? 'Chưa có') ?></td>
                                    <td>
                                        <?php if (strtolower($user['role']) === 'admin'): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1"></i>Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (($user['status'] ?? 'Hoạt động') === 'Đã khóa'): ?>
                                            <span class="badge badge-status badge-locked"><i class="bi bi-x-circle-fill me-1"></i>Đã khóa</span>
                                        <?php else: ?>
                                            <span class="badge badge-status badge-active"><i class="bi bi-check-circle-fill me-1"></i>Hoạt động</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end px-3">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="admin_edit_user.php?id=<?= $user['user_id'] ?>" class="btn btn-sm btn-outline-primary border-0 rounded-circle" title="Sửa thông tin">
                                                <i class="bi bi-pencil-square fs-5"></i>
                                            </a>
                                            <a href="admin_delete_user.php?id=<?= $user['user_id'] ?>" class="btn btn-sm btn-outline-danger border-0 rounded-circle" title="Xóa tài khoản" onclick="return confirm('Bạn có chắc chắn muốn xóa thành viên này?')">
                                                <i class="bi bi-trash3-fill fs-5"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link shadow-sm mx-1" href="?search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>