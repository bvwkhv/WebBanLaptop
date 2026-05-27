<?php
session_start();
require_once "database.php";
$db = new Database();

// Kiểm tra xem người dùng đã đăng nhập chưa thông qua SESSION
$current_user = $_SESSION['username'] ?? '';
if (!$current_user) {
    echo "<script>alert('Vui lòng đăng nhập!'); window.location.href='login.php';</script>";
    exit();
}
$current_user_id = $_SESSION['user_id'];

// --- XỬ LÝ YÊU CẦU TRẢ HÀNG KHI USER SUBMIT FORM MODAL ---
if (isset($_POST['confirm_return'])) {
    $order_id = (int)$_POST['order_id'];
    $reason = htmlspecialchars($_POST['return_reason']);

    // 1. Cập nhật trạng thái đơn hàng thành 'Yêu cầu trả hàng'
    $sql_update = "UPDATE orders SET status = 'Yêu cầu trả hàng' WHERE order_id = ? AND user_id = ?";
    $db->execute($sql_update, 'ii', [$order_id, $current_user_id]);

    // 2. Ghi thêm một dòng log vào bảng lịch sử vận chuyển (order_history) kèm lý do khách nhập
    $description = "Khách hàng yêu cầu trả hàng. Lý do: " . $reason;
    $sql_history = "INSERT INTO order_history (order_id, status_name, description, location, created_at) 
                    VALUES (?, 'Yêu cầu trả hàng', ?, 'Hệ thống khách hàng', NOW())";
    $db->execute($sql_history, 'is', [$order_id, $description]);

    echo "<script>alert('Đã gửi yêu cầu trả hàng với lý do: $reason'); window.location.href='order_history.php';</script>";
    exit();
}

// --- LOGIC PHÂN TRANG DỮ LIỆU ---
$limit = 5; // Số lượng đơn hàng tối đa hiển thị trên một trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Lấy số trang hiện tại từ URL, mặc định là 1
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit; // Tính mốc bắt đầu lấy dữ liệu cho câu lệnh SQL LIMIT

// Tính tổng số lượng đơn hàng của cơ sở dữ liệu để phục vụ việc chia trang
$sql_count = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
$total_result = $db->select($sql_count, 'i', [$current_user_id]);
$total_orders = $total_result[0]['total'] ?? 0;
$total_pages = ceil($total_orders / $limit); // Tổng số trang thu được sau khi chia

// Truy vấn danh sách đơn hàng có giới hạn phân trang và sắp xếp theo ngày đặt mới nhất
$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT ? OFFSET ?";
$orders = $db->select($sql, 'iii', [$current_user_id, $limit, $offset]);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đơn hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-color: #fdfae6; /* Giữ màu nền vàng kem sữa yêu thích của bạn */
            font-family: 'Inter', sans-serif;
            color: #212529;
        }

        .card {
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03); /* Bóng đổ cực mịn, hiện đại */
            background-color: #ffffff;
            border: none !important;
        }

        /* --- THAY ĐỔI HỆ THỐNG NÚT BẤM HIỆN ĐẠI HƠN --- */
        
        /* Nút Tiếp tục mua sắm tinh tế */
        .btn-clean-shop {
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            color: #495057;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .btn-clean-shop:hover {
            background-color: #f8f9fa;
            color: #212529;
            border-color: #cde2cd;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        /* Nút Xem chi tiết tone Xanh Mint siêu mịn */
        .btn-modern-detail {
            background-color: #e8f5e9; 
            color: #1b5e20;
            font-weight: 500;
            border: none;
            transition: all 0.2s;
        }
        .btn-modern-detail:hover {
            background-color: #c8e6c9;
            color: #0b2f11;
        }

        /* Nút Trả hàng dùng viền đỏ nhạt, tránh bị rực quá mức */
        .btn-modern-return {
            background-color: transparent;
            border: 1px solid #f5c2c7;
            color: #842029;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-modern-return:hover {
            background-color: #ea868f;
            color: #ffffff;
            border-color: #ea868f;
        }

        /* --- ĐỊNH NGHĨA BADGE TRẠNG THÁI THEO TONE PASTEL SÁNG --- */
        .badge-custom {
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-cho-xu-ly { background-color: #fff3cd; color: #664d03; }
        .status-da-giao { background-color: #d1e7dd; color: #0f5132; }
        .status-da-huy { background-color: #f8d7da; color: #842029; }
        .status-yeu-cau-tra-hang { background-color: #cff4fc; color: #055160; }
        .status-da-hoan-tien { background-color: #e2d9f3; color: #4b2c85; }

        /* --- TỐI ƯU PHÂN TRANG --- */
        .pagination .page-link {
            color: #198754;
            background-color: #ffffff;
            border-radius: 8px;
            margin: 0 3px;
            border: 1px solid #dee2e6;
        }
        .pagination .page-item.active .page-link {
            background-color: #198754;
            border-color: #198754;
            color: white;
        }
        .pagination .page-link:hover {
            background-color: #e8f5e9;
            color: #0f5132;
        }
    </style>
</head>

<body>
    <div class="container mt-5 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1"><i class="bi bi-clock-history me-2 text-success"></i>Lịch sử đặt hàng</h3>
                <p class="text-muted small mb-0">Quản lý và theo dõi trạng thái hành trình các đơn hàng của bạn</p>
            </div>
            <a href="index.php" class="btn btn-clean-shop rounded-3 px-4 shadow-sm">
                <i class="bi bi-cart me-1"></i> Tiếp tục mua sắm
            </a>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3 ps-4 text-secondary">Mã đơn</th>
                                <th class="py-3 text-secondary">Ngày đặt</th>
                                <th class="py-3 text-secondary">Tổng tiền thanh toán</th>
                                <th class="py-3 text-secondary">Trạng thái đơn</th>
                                <th class="py-3 pe-4 text-end text-secondary">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($orders)): ?>
                                <?php foreach ($orders as $row): ?>
                                    <tr>
                                        <td class="py-3 ps-4 fw-semibold text-dark">#<?= $row['order_id'] ?></td>
                                        
                                        <td class="text-secondary"><?= date('d/m/Y H:i', strtotime($row['order_date'])) ?></td>
                                        
                                        <td class="fw-bold text-danger"><?= number_format($row['total_amount'], 0, ',', '.') ?>đ</td>
                                        
                                        <td>
                                            <?php
                                            $status_text = htmlspecialchars($row['status']);
                                            $badge_class = "status-cho-xu-ly"; // Mặc định là chờ xử lý

                                            if ($row['status'] == 'Đã giao' || $row['status'] == 'Thành công') {
                                                $badge_class = "status-da-giao";
                                            } elseif ($row['status'] == 'Đã hủy') {
                                                $badge_class = "status-da-huy";
                                            } elseif ($row['status'] == 'Yêu cầu trả hàng') {
                                                $badge_class = "status-yeu-cau-tra-hang";
                                            } elseif ($row['status'] == 'Đã hoàn tiền') {
                                                $badge_class = "status-da-hoan-tien";
                                            }
                                            ?>
                                            <span class="badge-custom <?= $badge_class ?>"><?= $status_text ?></span>
                                        </td>
                                        
                                        <td class="py-3 pe-4 text-end">
                                            <div class="d-flex gap-2 justify-content-end">
                                                <a href="order_details.php?id=<?= $row['order_id'] ?>" class="btn btn-sm btn-modern-detail rounded-3 px-3">
                                                    <i class="bi bi-eye me-1"></i>Chi tiết
                                                </a>

                                                <?php if ($row['status'] == 'Đã giao'): ?>
                                                    <button type="button" class="btn btn-sm btn-modern-return rounded-3 px-3"
                                                        onclick="openReturnModal(<?= $row['order_id'] ?>)">
                                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Trả hàng
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-bag-x fs-1 d-block mb-2 text-secondary"></i>
                                        Bạn chưa thực hiện giao dịch hoặc đơn hàng nào.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav class="d-flex justify-content-center mt-4">
                <ul class="pagination shadow-sm rounded-3">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="returnModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-white border-bottom p-3">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Yêu cầu hoàn trả đơn hàng #<span id="display_order_id"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="order_id" id="input_order_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small text-uppercase">Lý do hoàn trả:</label>
                        <textarea name="return_reason" class="form-control rounded-3" rows="4" placeholder="Vui lòng cung cấp chi tiết lỗi..." required></textarea>
                    </div>
                    <div class="bg-light rounded-3 p-3 border">
                        <p class="text-muted small mb-0"><i class="bi bi-info-circle-fill text-primary me-1"></i> <strong>Lưu ý quy trình:</strong> Yêu cầu của bạn sẽ được gửi tới bộ phận Admin kiểm tra đối chiếu lịch sử vận hành trước khi phê duyệt.</p>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 border-top">
                    <button type="button" class="btn btn-white border rounded-3 px-4 fw-medium text-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" name="confirm_return" class="btn btn-danger rounded-3 px-4 fw-medium">Gửi yêu cầu trả hàng</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openReturnModal(orderId) {
            document.getElementById('display_order_id').innerText = orderId;
            document.getElementById('input_order_id').value = orderId;
            var myModal = new bootstrap.Modal(document.getElementById('returnModal'));
            myModal.show();
        }
    </script>
</body>

</html>