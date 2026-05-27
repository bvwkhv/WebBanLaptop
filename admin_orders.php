<?php
// Bắt đầu phiên làm việc để kiểm tra quyền đăng nhập
session_start();

// --- 1. KIỂM TRA QUYỀN TRUY CẬP (BẢO MẬT) ---
// Nếu chưa đăng nhập hoặc vai trò không phải là 'admin', chặn truy cập và đá về trang chủ
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Nhúng file cấu hình Database và khởi tạo đối tượng kết nối
require_once "database.php";
$db = new Database();

// --- 2. XỬ LÝ CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG QUA AJAX (POST) ---
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];

    // Lấy trạng thái hiện tại của đơn hàng trong DB để so sánh logic
    $current_order = $db->select("SELECT status FROM orders WHERE order_id = ?", "i", [$order_id]);

    if (empty($current_order)) {
        echo "error_not_found";
        exit;
    }

    $old_status = $current_order[0]['status'];

    // Quy tắc 1: Định nghĩa các trạng thái cuối (Đơn hàng đã đóng, không cho phép sửa đổi nữa)
    $final_statuses = ['Đã giao', 'Đã hủy', 'Đã hoàn tiền'];
    if (in_array($old_status, $final_statuses)) {
        echo "error_locked"; 
        exit;
    }

    // Quy tắc 2: Định cấp độ ưu tiên trạng thái để chặn cập nhật ngược quy trình quản lý vận đơn
    $status_priority = [
        'Chờ xử lý' => 1,
        'Đã xác nhận' => 2,
        'Đang trên đường giao' => 3,
        'Đã giao' => 4,
        'Đã hủy' => 5,
        'Yêu cầu trả hàng' => 6,
        'Đã hoàn tiền' => 7
    ];

    // Nếu cố tình chuyển về trạng thái có cấp độ thấp hơn (Hạ cấp đơn hàng) -> Chặn, trừ quyền Hủy đơn đặc biệt
    if (isset($status_priority[$new_status]) && isset($status_priority[$old_status])) {
        if ($status_priority[$new_status] < $status_priority[$old_status] && $new_status !== 'Đã hủy') {
            echo "error_logic_back";
            exit;
        }
    }

    // Quy tắc 3: Tiến hành cập nhật trạng thái mới vào bảng orders
    $sql = "UPDATE orders SET status = ? WHERE order_id = ?";
    $db->execute($sql, 'si', [$new_status, $order_id]);

    // Khởi tạo các chuỗi mô tả log hành trình mặc định
    $description = "Trạng thái đơn hàng đã được thay đổi thành: " . $new_status;
    $location = "Hệ thống quản lý";

    // Phân nhánh ghi log chi tiết tự động dựa theo trạng thái admin chọn
    if ($new_status === 'Đã xác nhận') {
        $description = "Người bán đã xác nhận đơn hàng và đang chuẩn bị sản phẩm.";
    } elseif ($new_status === 'Đang trên đường giao') {
        $description = "Shipper đang mang kiện hàng đến địa chỉ của bạn.";
        $location = "Bưu cục giao hàng";
    } elseif ($new_status === 'Đã giao') {
        $description = "Đơn hàng đã được giao thành công đến tay khách hàng.";
    } elseif ($new_status === 'Đã hủy') {
        $description = "Đơn hàng đã bị hủy bởi quản trị viên.";
    }

    // Ghi nhận mốc lịch sử mới vào bảng order_history để hiển thị bên dòng thời gian timeline người dùng
    $sql_history = "INSERT INTO order_history (order_id, status_name, description, location, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
    $db->execute($sql_history, 'isss', [$order_id, $new_status, $description, $location]);

    echo "success";
    exit;
}

// --- 3. CẤU HÌNH LOGIC PHÂN TRANG DỮ LIỆU ---
$limit = 5; // Số lượng đơn hàng hiển thị trên một trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit; // Tính điểm bắt đầu lấy dữ liệu trong truy vấn SQL

// Đếm tổng số lượng đơn hàng hiện có để tính toán tổng số trang
$total_result = $db->select("SELECT COUNT(*) as total FROM orders");
$total_orders = $total_result[0]['total'];
$total_pages = ceil($total_orders / $limit);

// Lấy danh sách đơn hàng theo giới hạn phân trang (Sắp xếp đơn mới nhất lên đầu)
$orders = $db->select("SELECT * FROM orders ORDER BY order_id DESC LIMIT $limit OFFSET $offset");

/**
 * Hàm hỗ trợ lấy lý do hoàn trả hàng của khách từ lịch sử log đơn
 */
function getReturnReason($db, $order_id) {
    $sql = "SELECT description FROM order_history WHERE order_id = ? AND status_name = 'Yêu cầu trả hàng' ORDER BY created_at DESC LIMIT 1";
    $result = $db->select($sql, "i", [$order_id]);
    return !empty($result) ? $result[0]['description'] : '';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #fdfae7; /* Màu nền kem sữa nhẹ nhàng đồng bộ hệ thống */
            font-family: 'Inter', sans-serif;
            color: #333333;
        }

        /* Thùng chứa bảng danh sách */
        .table-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
            border: none;
        }

        /* Định hình lại cấu trúc bảng dữ liệu sạch (Clean Table) */
        .table {
            margin-bottom: 0;
        }
        .table th {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.85rem;
            text-uppercase: uppercase;
            letter-spacing: 0.5px;
            padding: 16px;
            background-color: #f8f9fa !important;
            border-bottom: 1px solid #edf2f7;
        }
        .table td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
        }
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        .table tbody tr:hover {
            background-color: #fcfbf4 !important; /* Hiệu ứng highlight nhẹ khi rê chuột qua dòng */
        }

        /* --- TÁI CẤU TRÚC ĐƯỜNG NÉT SELECT DROPDOWN TRẠNG THÁI (TONE PASTEL) --- */
        .status-select {
            border-radius: 30px;
            font-size: 0.8rem;
            padding: 6px 16px;
            cursor: pointer;
            font-weight: 600;
            width: 175px;
            border: none;
            text-align: center;
            box-shadow: none !important;
            transition: all 0.2s ease;
        }
        .status-select:focus {
            transform: scale(1.02);
        }

        /* Các mã màu Pastel mượt mà cho từng nhóm trạng thái */
        .status-waiting { background-color: #fff3cd; color: #664d03; }
        .status-confirmed { background-color: #d1e7dd; color: #0f5132; }
        .status-shipping { background-color: #cff4fc; color: #055160; }
        .status-shipped { background-color: #e2f0d9; color: #2e6930; }
        .status-cancelled { background-color: #f8d7da; color: #842029; }
        .status-return-pending { background-color: #f3f4f6; color: #4b5563; border: 1px dashed #d1d5db; }
        .status-refunded { background-color: #e2d9f3; color: #4b2c85; }

        /* Cột hiển thị lý do hoàn trả */
        .reason-col { max-width: 170px; }
        .reason-text {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.8rem;
            font-weight: 500;
            color: #dc3545;
            background: #fff5f5;
            padding: 6px 12px;
            border-radius: 8px;
            border-left: 3px solid #dc3545;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .reason-text:hover {
            background: #fee2e2;
            transform: translateY(-1px);
        }

        /* Nút bấm điều hướng chung */
        .btn-custom-nav {
            background: white;
            border: 1px solid #dee2e6;
            color: #495057;
            font-weight: 500;
            padding: 8px 18px;
            transition: all 0.2s ease;
        }
        .btn-custom-nav:hover {
            background: #f8f9fa;
            color: #212529;
            border-color: #ced4da;
        }

        /* CSS Tùy biến thanh phân trang (Pagination) */
        .pagination .page-link {
            color: #495057;
            border: 1px solid #edf2f7;
            padding: 8px 16px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .pagination .page-item.active .page-link {
            background-color: #212529;
            border-color: #212529;
            color: white;
        }
        .pagination .page-link:hover {
            background-color: #f8f9fa;
            color: #212529;
        }
    </style>
</head>

<body>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">
                    <i class="bi bi-box-seam me-2 text-dark"></i>Quản Lý Đơn Hàng
                </h3>
                <p class="text-muted small mb-0 mt-1">Hệ thống kiểm duyệt trạng thái vận đơn và xử lý yêu cầu hoàn trả.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="admin_dashboard.php" class="btn btn-custom-nav rounded-3 shadow-sm text-dark">
                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                </a>
                <a href="admin_statistics.php" class="btn btn-dark rounded-3 shadow-sm px-4">
                    <i class="bi bi-graph-up-arrow me-1"></i> Thống kê
                </a>
            </div>
        </div>

        <div class="table-card p-4">
            <div class="table-responsive">
                <table class="table table-borderless align-middle">
                    <thead class="text-center">
                        <tr>
                            <th style="width: 100px;">Mã đơn</th>
                            <th class="text-start">Khách hàng</th>
                            <th>Ngày đặt</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái duyệt</th>
                            <th>Lý do trả hàng</th>
                            <th style="width: 80px;">Xem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $row): ?>
                            <tr>
                                <td class="text-center fw-bold text-secondary">#<?= $row['order_id'] ?></td>
                                
                                <td>
                                    <div class="fw-bold text-dark text-nowrap">
                                        <?= htmlspecialchars(mb_strimwidth($row['customer_name'] ?? 'Khách lẻ', 0, 20, "...")) ?>
                                    </div>
                                    <div class="small text-muted mt-1"><i class="bi bi-telephone me-1"></i><?= $row['phone'] ?? '' ?></div>
                                </td>
                                
                                <td class="text-center text-secondary"><?= date('d/m/Y', strtotime($row['order_date'])) ?></td>
                                
                                <td class="text-center fw-bold text-dark"><?= number_format($row['total_amount'], 0, ',', '.') ?>đ</td>
                                
                                <td class="text-center">
                                    <select class="form-select form-select-sm status-change status-select mx-auto 
                                    <?= ($row['status'] == 'Chờ xử lý') ? 'status-waiting' : '' ?>
                                    <?= ($row['status'] == 'Đã xác nhận') ? 'status-confirmed' : '' ?>
                                    <?= ($row['status'] == 'Đang trên đường giao') ? 'status-shipping' : '' ?>
                                    <?= ($row['status'] == 'Đã giao') ? 'status-shipped' : '' ?>
                                    <?= ($row['status'] == 'Đã hủy') ? 'status-cancelled' : '' ?>
                                    <?= ($row['status'] == 'Yêu cầu trả hàng') ? 'status-return-pending' : '' ?>
                                    <?= ($row['status'] == 'Đã hoàn tiền') ? 'status-refunded' : '' ?>"
                                        data-id="<?= $row['order_id'] ?>"
                                        <?= ($row['status'] == 'Đã hoàn tiền' || $row['status'] == 'Đã hủy') ? 'disabled' : '' ?>>

                                        <option value="Chờ xử lý" <?= $row['status'] == 'Chờ xử lý' ? 'selected' : '' ?>>🕒 Chờ xử lý</option>
                                        <option value="Đã xác nhận" <?= $row['status'] == 'Đã xác nhận' ? 'selected' : '' ?>>✅ Đã xác nhận</option>
                                        <option value="Đang trên đường giao" <?= $row['status'] == 'Đang trên đường giao' ? 'selected' : '' ?>>🚚 Đang giao hàng</option>
                                        <option value="Đã giao" <?= $row['status'] == 'Đã giao' ? 'selected' : '' ?>>📦 Đã giao</option>
                                        <option value="Đã hủy" <?= $row['status'] == 'Đã hủy' ? 'selected' : '' ?>>❌ Đã hủy</option>
                                        <option value="Yêu cầu trả hàng" <?= $row['status'] == 'Yêu cầu trả hàng' ? 'selected' : '' ?> disabled>📩 Yêu cầu trả</option>
                                        <option value="Đã hoàn tiền" <?= $row['status'] == 'Đã hoàn tiền' ? 'selected' : '' ?>>💰 Đã hoàn tiền</option>
                                    </select>
                                </td>
                                
                                <td class="reason-col">
                                    <?php if ($row['status'] == 'Yêu cầu trả hàng'):
                                        $reason = getReturnReason($db, $row['order_id']); ?>
                                        <div class="reason-text"
                                             onclick="showReasonModal('<?= $row['order_id'] ?>', `<?= addslashes(htmlspecialchars($reason)) ?>`)">
                                            <i class="bi bi-info-circle me-1"></i> <?= htmlspecialchars($reason) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center text-muted small">—</div>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center">
                                    <a href="order_details.php?id=<?= $row['order_id'] ?>" class="btn btn-sm btn-light border rounded-3 text-secondary">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link rounded-start-3" href="?page=<?= $page - 1 ?>">Trước</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link rounded-end-3" href="?page=<?= $page + 1 ?>">Sau</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="reasonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0 style='border-radius: 12px; overflow: hidden;'">
                <div class="modal-header bg-dark text-white p-3">
                    <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-exclamation-circle me-2 text-warning"></i>Chi tiết lý do trả hàng</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="fw-bold mb-2 text-secondary">Mã đơn hàng: <span id="modalOrderId" class="text-dark"></span></p>
                    <div class="p-3 bg-light rounded border">
                        <p id="modalReasonContent" class="mb-0 text-dark" style="white-space: pre-wrap; line-height: 1.6; font-size: 0.9rem;"></p>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 pt-0">
                    <button type="button" class="btn btn-light border rounded-pill px-4 btn-sm" data-bs-dismiss="modal">Đóng cửa sổ</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        /**
         * Kích hoạt hiển thị cửa sổ Modal chứa nội dung lý do trả hàng của khách
         */
        function showReasonModal(orderId, reason) {
            document.getElementById('modalOrderId').innerText = '#' + orderId;
            document.getElementById('modalReasonContent').innerText = reason;
            var myModal = new bootstrap.Modal(document.getElementById('reasonModal'));
            myModal.show();
        }

        /**
         * Lắng nghe sự kiện thay đổi trạng thái của các thẻ Select và gửi AJAX về Server
         */
        document.querySelectorAll('.status-change').forEach(select => {
            select.addEventListener('change', function() {
                const orderId = this.getAttribute('data-id');
                const newStatus = this.value;
                const el = this;

                // Làm mờ tạm thời phần tử select để báo hiệu trạng thái đang xử lý bất đồng bộ
                el.style.opacity = '0.5';
                
                // Khởi tạo FormData đóng gói dữ liệu đẩy qua POST
                const formData = new FormData();
                formData.append('update_status', 'true');
                formData.append('order_id', orderId);
                formData.append('status', newStatus);

                // Thực hiện gửi yêu cầu ngầm bằng Fetch API
                fetch('admin_orders.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.text())
                    .then(data => {
                        const result = data.trim();
                        // Phân tích kết quả trả về từ Backend PHP xử lý
                        if (result === "success") {
                            location.reload(); // Tải lại trang để cập nhật giao diện và ghi nhận Log lịch sử mới
                        } else if (result === "error_locked") {
                            alert("Đơn hàng này đã kết thúc xử lý (Đã giao/Hủy/Hoàn tiền), không thể thay đổi trạng thái!");
                            location.reload();
                        } else if (result === "error_logic_back") {
                            alert("Lỗi quy trình: Không thể cập nhật trạng thái quay lùi lại bước trước đó!");
                            location.reload();
                        } else {
                            alert("Hệ thống phát hiện lỗi không xác định: " + result);
                            location.reload();
                        }
                    })
                    .catch(error => {
                        alert("Lỗi kết nối mạng, vui lòng thử lại sau!");
                        location.reload();
                    });
            });
        });
    </script>
</body>
</html>