<?php
require_once "database.php";
session_start();
$db = new Database();

// 1. Cấu hình hiển thị và Phân trang (Load more)
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 9;

// 2. Logic lọc dữ liệu
$sql = "SELECT * FROM products WHERE 1=1";
$params = [];
$types = "";

if (!empty($_GET['brand'])) {
    $sql .= " AND brand_id = ?";
    $params[] = $_GET['brand'];
    $types .= "i";
}

if (!empty($_GET['price_range'])) {
    $range = explode('-', $_GET['price_range']);
    $min = (float)$range[0] * 1000000;
    $max = (float)$range[1] * 1000000;
    $sql .= " AND price BETWEEN ? AND ?";
    $params[] = $min;
    $params[] = $max;
    $types .= "dd";
}

if (!empty($_GET['search'])) {
    $sql .= " AND product_name LIKE ?";
    $params[] = "%" . $_GET['search'] . "%";
    $types .= "s";
}

$sql .= " ORDER BY product_id DESC LIMIT $limit";

if (!empty($params)) {
    $result = $db->select($sql, $types, $params);
} else {
    $result = $db->select($sql);
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laptop Store - Thế giới Laptop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/styles.css">
    <style>
        /* Thêm các định dạng cho menu Gỡ của User */
        .message-wrapper {
            display: flex;
            flex-direction: column;
            margin-bottom: 10px;
            width: 100%;
        }

        .message-wrapper.me {
            align-items: flex-end;
        }

        .message-wrapper.other {
            align-items: flex-start;
        }

        .msg-content-container {
            display: flex;
            align-items: flex-end;
            max-width: 85%;
            gap: 5px;
            position: relative;
        }

        .msg-options {
            opacity: 0;
            cursor: pointer;
            color: #aaa;
            transition: 0.2s;
            padding: 2px 5px;
            font-size: 12px;
            order: 1;
        }

        .msg-content-container:hover .msg-options {
            opacity: 1;
        }

        .msg-user {
            background: #007bff;
            color: white;
            padding: 8px 12px;
            border-radius: 15px 15px 2px 15px;
            order: 2;
        }

        .msg-admin {
            background: #e9ecef;
            color: #333;
            padding: 8px 12px;
            border-radius: 15px 15px 15px 2px;
        }

        .action-menu {
            display: none;
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            z-index: 1000;
            min-width: 60px;
            bottom: 25px;
            left: 0;
        }

        .menu-item {
            padding: 6px 10px;
            font-size: 12px;
            cursor: pointer;
            text-align: center;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="image/logolaptop.jpg" width="40" height="40" class="rounded-circle me-2" alt="">
                <span class="fw-bold">LAPTOP STORE</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                    <li class="nav-item"><a class="nav-link" href="index.php">Trang chủ</a></li>
                    <li class="nav-item"><a class="nav-link" href="news_list.php">Tin tức</a></li>
                </ul>

                <form class="d-flex mx-auto search-group w-100" style="max-width: 450px;" action="index.php" method="GET">
                    <input class="form-control" type="search" name="search" placeholder="Tìm kiếm laptop..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button class="btn px-4" type="submit">🔍</button>
                </form>

                <div class="ms-auto d-flex align-items-center mt-3 mt-lg-0">
                    <!-- User Menu -->
                    <div class="dropdown me-3">
                        <a href="#" class="btn btn-dark btn-sm rounded-circle p-2" id="userMenu" data-bs-toggle="dropdown">
                            <svg width="20" height="20" fill="white" viewBox="0 0 448 512">
                                <path d="M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4z" />
                            </svg>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <li>
                                    <h6 class="dropdown-header">Hi, <?= $_SESSION['username'] ?></h6>
                                </li>
                                <li><a class="dropdown-item" href="profile.php">Tài khoản</a></li>
                                <li><a class="dropdown-item" href="order_history.php">Lịch sử đặt hàng</a></li>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <li><a class="dropdown-item text-primary fw-bold" href="admin_dashboard.php">Trang quản lý</a></li>
                                <?php endif; ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="logout.php">Đăng xuất</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="login.php">Đăng nhập</a></li>
                                <li><a class="dropdown-item" href="register.php">Đăng ký</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Giỏ hàng -->
                    <a href="view_cart.php" class="btn btn-dark btn-sm rounded-pill px-3 d-flex align-items-center">
                        <svg width="18" height="18" fill="white" viewBox="0 0 576 512" class="me-2">
                            <path d="M528.1 171.5L482 297.3c-11 30.2-39.6 50.7-71.7 50.7H203.1c-32.1 0-60.7-20.5-71.7-50.7L85.4 171.5c-4.1-11.3 4.3-23.5 16.4-23.5H411.6c12.1 0 20.5 12.2 16.4 23.5zM429.3 48H146.7c-12.1 0-20.5 12.2-16.4 23.5L176.4 128h223.2l46.1-56.5C449.8 60.2 441.4 48 429.3 48zM160 464a48 48 0 1 0 96 0 48 48 0 1 0 -96 0zm256 0a48 48 0 1 0 96 0 48 48 0 1 0 -96 0z" />
                        </svg>
                        <span>Giỏ hàng</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Banner Slider -->
    <header class="container gallery-container">
        <div class="row justify-content-center">
            <div class="col-lg-11">
                <img id="main-img" src="./image/Hinh_galler_1.webp" alt="Khuyến mãi">
            </div>
        </div>
    </header>

    <div class="container mt-5 pb-5">
        <div class="row">
            <!-- Sidebar Filter -->
            <aside class="col-lg-3 mb-4">
                <div class="sidebar-filter">
                    <div class="filter-title d-flex align-items-center">
                        <span class="me-2">⚡</span> BỘ LỌC TÌM KIẾM
                    </div>
                    <form method="GET" action="index.php">
                        <div class="filter-group">
                            <label>Thương hiệu</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="brand" id="bAll" value="" <?= empty($_GET['brand']) ? 'checked' : '' ?> onchange="this.form.submit()">
                                <label class="form-check-label" for="bAll">Tất cả hãng</label>
                            </div>
                            <?php
                            $brands = $db->select("SELECT * FROM brands");
                            foreach ($brands as $b): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="brand" id="b<?= $b['brand_id'] ?>" value="<?= $b['brand_id'] ?>" <?= (@$_GET['brand'] == $b['brand_id']) ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <label class="form-check-label" for="b<?= $b['brand_id'] ?>"><?= $b['brand_name'] ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="filter-group mt-4">
                            <label>Mức giá dự kiến</label>
                            <select name="price_range" class="form-select" onchange="this.form.submit()">
                                <option value="">Chọn khoảng giá</option>
                                <option value="0-10" <?= (@$_GET['price_range'] == '0-10') ? 'selected' : '' ?>>Dưới 10 triệu</option>
                                <option value="10-20" <?= (@$_GET['price_range'] == '10-20') ? 'selected' : '' ?>>10 - 20 triệu</option>
                                <option value="20-30" <?= (@$_GET['price_range'] == '20-30') ? 'selected' : '' ?>>20 - 30 triệu</option>
                                <option value="30-1000" <?= (@$_GET['price_range'] == '30-1000') ? 'selected' : '' ?>>Trên 30 triệu</option>
                            </select>
                        </div>
                        <a href="index.php" class="btn btn-sm text-muted mt-3 d-block text-center fw-bold">Xóa tất cả bộ lọc</a>
                    </form>
                </div>
            </aside>

            <!-- Product List -->
            <main class="col-lg-9">
                <div class="row g-4">
                    <?php if (empty($result)): ?>
                        <div class="col-12 text-center py-5">
                            <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="80" class="opacity-25 mb-3">
                            <h5 class="text-muted">Không tìm thấy sản phẩm nào phù hợp.</h5>
                        </div>
                    <?php else: ?>
                        <?php foreach ($result as $r): ?>
                            <div class="col-6 col-md-4">
                                <div class="card h-100 product-card shadow-sm">
                                    <img class="card-img-top" src="image/<?= $r["image_url"] ?>" alt="Laptop">
                                    <div class="card-body p-3">
                                        <h6 class="product-name"><?= htmlspecialchars($r["product_name"]) ?></h6>
                                        <p class="product-price mb-0"><?= number_format($r["price"], 0, ',', '.') ?>đ</p>
                                    </div>
                                    <div class="card-footer bg-transparent border-0 p-3 pt-0">
                                        <a class="btn btn-detail w-100 py-2" href="product_details.php?id=<?= $r["product_id"] ?>">Chi tiết</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Nút xem thêm -->
                <?php if (!empty($result)): ?>
                    <div class="text-center mt-5">
                        <button onclick="loadMore()" class="btn btn-loadmore shadow-sm">Xem thêm sản phẩm</button>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    <div id="chat-wrapper" style="position: fixed; bottom: 30px; right: 30px; z-index: 1000; display: flex; flex-direction: column; align-items: flex-end;">

        <div id="chat-box" class="shadow-lg border-0 rounded-4 overflow-hidden" style="display: none; width: 350px; height: 450px; background: white; margin-bottom: 15px;">
            <div class="chat-header d-flex align-items-center justify-content-between p-3 bg-primary text-white">
                <div class="d-flex align-items-center">
                    <div class="bg-light rounded-circle me-2" style="width: 10px; height: 10px;"></div>
                    <span class="fw-bold small">Hỗ trợ trực tuyến</span>
                </div>
                <button onclick="toggleChat()" class="btn-close btn-close-white" style="font-size: 10px;"></button>
            </div>

            <div id="chat-messages" class="p-3" style="height: 330px; overflow-y: auto; background: #f8f9fa;">
                <div class="text-center small text-muted mb-2">Chào bạn! LAPTOP STORE có thể giúp gì cho bạn?</div>
            </div>

            <div class="chat-input-area p-2 border-top">
                <div class="input-group">
                    <input type="text" id="user-msg" class="form-control form-control-sm border-0"
                        placeholder="Nhập tin nhắn..."
                        onkeypress="if(event.key === 'Enter') sendMessage()">
                    <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="sendMessage()">Gửi</button>
                </div>
            </div>
        </div>

        <button onclick="toggleChat()" class="btn btn-primary rounded-circle shadow-lg d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; flex-shrink: 0;">
            <svg width="30" height="30" fill="white" viewBox="0 0 16 16">
                <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm3.5 7a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-1 0V9a.5.5 0 0 1 .5-.5zm-7 0a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-1 0V9a.5.5 0 0 1 .5-.5zM8 8.5a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-1 0V9a.5.5 0 0 1 .5-.5z" />
                <path d="M13.468 12.37C14.445 11.238 15 9.8 15 8c0-3.866-3.134-7-7-7S1 4.134 1 8s3.134 7 7 7c1.17 0 2.273-.287 3.245-.801l3.541.801-.784-3.63zM8 13A5 5 0 1 1 8 3a5 5 0 0 1 0 10z" />
            </svg>
        </button>
    </div>
    <footer class="bg-dark text-white py-5">
        <div class="container text-center">
            <p>© 2026 - Đồ án Chuyên ngành Công nghệ thông tin</p>
            <p>Dữ liệu sản phẩm được tổng hợp từ FPT-Shop và FIT-TDC 2019</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Slider ảnh
        const images = ["./image/Hinh_galler_1.webp", "./image/Hinh_galler_2.webp", "./image/Hinh_galler_3.webp", "./image/Hinh_galler_4.webp"];
        let idx = 0;
        setInterval(() => {
            idx = (idx + 1) % images.length;
            const el = document.getElementById("main-img");
            if (el) {
                el.style.opacity = '0';
                setTimeout(() => {
                    el.src = images[idx];
                    el.style.opacity = '1';
                }, 500);
            }
        }, 4000);

        // Load more giữ vị trí cuộn
        window.onload = function() {
            let pos = sessionStorage.getItem('scrollPos');
            if (pos) window.scrollTo(0, pos);
            sessionStorage.removeItem('scrollPos');
        };

        function loadMore() {
            sessionStorage.setItem('scrollPos', window.scrollY);
            let url = new URL(window.location.href);
            let cur = parseInt(url.searchParams.get("limit")) || 9;
            url.searchParams.set("limit", cur + 9);
            window.location.href = url.href;
        }

        function toggleChat() {
            const chatBox = document.getElementById('chat-box');
            chatBox.style.display = (chatBox.style.display === 'none' || chatBox.style.display === '') ? 'block' : 'none';
            if (chatBox.style.display === 'block') loadMessages(true);
        }

        function loadMessages(forceScroll = false) {
            const chatBox = document.getElementById('chat-messages');

            fetch(`get_messages.php?user_id=4`)
                .then(res => res.text())
                .then(data => {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;
                    const newCount = tempDiv.querySelectorAll('.message-wrapper').length;
                    const currentCount = chatBox.querySelectorAll('.message-wrapper').length;
                    const isMenuOpen = Array.from(document.querySelectorAll('.action-menu')).some(el => el.style.display === 'block');

                    // Logic tối ưu: Chỉ cập nhật khi có tin mới/xóa tin và không đang mở menu
                    if (newCount !== currentCount || forceScroll) {
                        if (isMenuOpen && newCount === currentCount) return;

                        const isAtBottom = chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 100;
                        chatBox.innerHTML = data;
                        if (isAtBottom || forceScroll) chatBox.scrollTop = chatBox.scrollHeight;
                    }
                });
        }

        // Chạy load tin nhắn ngay khi mở chat và định kỳ 3 giây/lần
        setInterval(() => {
            const chatBox = document.getElementById('chat-box');
            if (chatBox.style.display === 'block') {
                loadMessages();
            }
        }, 3000);

        function toggleActionMenu(event, msgId) {
    event.stopPropagation();
    // Đóng tất cả các menu khác đang mở
    document.querySelectorAll('.action-menu').forEach(el => {
        if (el.id !== 'menu-' + msgId) el.style.display = 'none';
    });
    
    const menu = document.getElementById('menu-' + msgId);
    if (menu) {
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }
}

// Click ra ngoài thì đóng menu
document.addEventListener('click', function() {
    document.querySelectorAll('.action-menu').forEach(el => el.style.display = 'none');
});

        function confirmDelete(messageId) {
            if (confirm("Bạn muốn gỡ tin nhắn này?")) {
                let formData = new FormData();
                formData.append('message_id', messageId);
                fetch('delete_message.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') loadMessages(true);
                    });
            }
        }

        // Đóng menu khi click ra ngoài
        document.addEventListener('click', () => {
            document.querySelectorAll('.action-menu').forEach(el => el.style.display = 'none');
        });

        function sendMessage() {
            const input = document.getElementById('user-msg');
            const msg = input.value.trim();
            if (!msg) return;
            let formData = new FormData();
            formData.append('message', msg);
            fetch('send_message.php', {
                    method: 'POST',
                    body: formData
                })
                .then(() => {
                    input.value = "";
                    loadMessages(true);
                });
        }
    </script>
</body>

</html>