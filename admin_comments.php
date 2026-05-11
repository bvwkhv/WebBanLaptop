<?php
require_once "auth_check.php"; 
require_once "database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = new Database();

// 1. Xử lý gửi phản hồi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_content'])) {
    $parent_id = $_POST['parent_id'];
    $product_id = $_POST['product_id'];
    $content = trim($_POST['reply_content']);
    $admin_id = $_SESSION['user_id']; 

    if (!empty($content)) {
        $sql = "INSERT INTO comments (product_id, user_id, parent_id, content, created_at) VALUES (?, ?, ?, ?, NOW())";
        $db->execute($sql, "iiis", [$product_id, $admin_id, $parent_id, $content]);
        header("Location: admin_comments.php?status=success&page=" . ($_GET['page'] ?? 1));
        exit();
    }
}

// --- LOGIC PHÂN TRANG ---
$limit = 5; // Số bình luận mỗi trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số bình luận gốc để tính số trang
$sql_count = "SELECT COUNT(*) as total FROM comments WHERE parent_id IS NULL";
$total_result = $db->select($sql_count);
$total_comments = $total_result[0]['total'];
$total_pages = ceil($total_comments / $limit);

// Lấy danh sách bình luận theo trang
$sql = "SELECT c.*, u.username, p.product_name 
        FROM comments c
        JOIN users u ON c.user_id = u.user_id
        JOIN products p ON c.product_id = p.product_id
        WHERE c.parent_id IS NULL 
        ORDER BY c.created_at DESC 
        LIMIT ? OFFSET ?";
$all_comments = $db->select($sql, "ii", [$limit, $offset]);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý bình luận - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; padding: 20px; font-family: 'Inter', sans-serif; }
        .comment-card { border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 20px; transition: 0.3s; }
        .comment-card:hover { transform: translateY(-5px); }
        .badge-qtv { background-color: #000000; color: white; }
        .pagination .page-link { color: #ff001e; border-radius: 8px; margin: 0 3px; }
        .pagination .page-item.active .page-link { background-color: #ffffff; border-color: #d70018; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark">Quản lý bình luận <span class="badge bg-dark fs-6"><?= $total_comments ?></span></h2>
            <a href="admin_dashboard.php" class="btn btn-outline-secondary rounded-pill px-4">Quay lại Dashboard</a>
        </div>

        <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4">Đã gửi phản hồi thành công!</div>
        <?php endif; ?>

        <div class="row">
            <?php if (empty($all_comments)): ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Không có bình luận nào để hiển thị.</p>
                </div>
            <?php else: ?>
                <?php foreach ($all_comments as $com): ?>
                    <div class="col-12">
                        <div class="card comment-card p-4">
                            <div class="mb-3">
                                <span class="badge bg-info text-dark mb-2">Sản phẩm: <?= htmlspecialchars($com['product_name']) ?></span>
                                <h6 class="fw-bold mb-1">Khách hàng: <?= htmlspecialchars($com['username']) ?></h6>
                                <small class="text-muted"><?= date('H:i d/m/Y', strtotime($com['created_at'])) ?></small>
                            </div>
                            
                            <div class="bg-light p-3 rounded-3 mb-3 border-start border-4 border-primary">
                                "<?= nl2br(htmlspecialchars($com['content'])) ?>"
                            </div>

                            <?php 
                                $check_sql = "SELECT content FROM comments WHERE parent_id = ?";
                                $replied = $db->select($check_sql, "i", [$com['comment_id']]);
                            ?>

                            <?php if (empty($replied)): ?>
                                <form action="admin_comments.php?page=<?= $page ?>" method="POST" class="mt-2">
                                    <input type="hidden" name="parent_id" value="<?= $com['comment_id'] ?>">
                                    <input type="hidden" name="product_id" value="<?= $com['product_id'] ?>">
                                    <div class="input-group">
                                        <textarea name="reply_content" class="form-control rounded-start-4" rows="2" placeholder="Nhập phản hồi..." required></textarea>
                                        <button type="submit" class="btn btn-danger px-4 rounded-end-4">Gửi Admin</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="mt-2 p-3 bg-success-subtle rounded-3">
                                    <span class="badge badge-qtv mb-2">Đã phản hồi</span>
                                    <p class="small text-dark mb-0 italic"><strong>Nội dung:</strong> <?= htmlspecialchars($replied[0]['content']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>">Trước</a>
                </li>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>">Sau</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</body>
</html>