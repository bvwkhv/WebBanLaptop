<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <title>Quản lý Chat | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --bg-chat: #f4f7f6;
        }

        .chat-sidebar {
            height: 80vh;
            background: #fff;
            border-right: 1px solid #eaeaea;
            display: flex;
            flex-direction: column;
            min-width: 250px;
        }

        #user-list {
            flex-grow: 1;
            overflow-y: auto;
        }

        .user-item {
            cursor: pointer;
            padding: 15px;
            border-bottom: 1px solid #f8f9fa;
            transition: 0.2s;
            border-left: 4px solid transparent;
        }

        .user-item:hover {
            background: #f0f4f8;
        }

        .user-item.active {
            background: #e7f0ff;
            border-left-color: var(--primary-color);
        }

        .chat-content {
            height: 80vh;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        .msg-list {
            flex-grow: 1;
            overflow-y: auto;
            padding: 20px;
            background: var(--bg-chat);
            display: flex;
            flex-direction: column;
            gap: 5px;
            position: relative;
            /* Thêm dòng này */
        }

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

        /* Cấu trúc container chứa bong bóng và nút ba chấm */
        .msg-content-container {
            display: flex;
            align-items: flex-end;
            max-width: 75%;
            gap: 5px;
            position: relative;
        }

        .msg-user,
        .msg-admin {
            padding: 10px 15px;
            border-radius: 18px;
            font-size: 14px;
            line-height: 1.5;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            word-break: break-all;
        }

        .msg-user {
            background: var(--primary-color);
            color: white;
            border-radius: 18px 18px 2px 18px;
            order: 2;
        }

        .msg-admin {
            background: white;
            color: #333;
            border-radius: 18px 18px 18px 2px;
        }

        .msg-time {
            display: block;
            font-size: 10px;
            margin-top: 4px;
            opacity: 0.7;
            text-align: right;
        }

        /* Nút ba chấm nằm ở góc dưới bên trái bong bóng của mình */
        .msg-options {
            opacity: 0;
            cursor: pointer;
            color: #aaa;
            transition: 0.2s;
            padding: 2px 5px;
            order: 1;
            font-size: 12px;
        }

        .msg-content-container:hover .msg-options {
            opacity: 1;
        }

        /* Menu thả xuống hướng lên trên */
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

        .menu-item:hover {
            background: #f8f9fa;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <div class="row shadow-lg rounded-4 overflow-hidden border">
            <div class="col-md-4 col-lg-3 p-0 chat-sidebar">
                <div class="p-2 border-bottom">
                    <a href="admin_dashboard.php" class="btn btn-light btn-sm w-100 border py-2" style="font-size: 12px;">
                        <i class="fa-solid fa-house-user me-2"></i> Dashboard
                    </a>
                </div>
                <div class="search-box p-2 border-bottom">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fa fa-search text-muted"></i></span>
                        <input type="text" id="search-input" class="form-control bg-light border-0" placeholder="Tìm..." onkeyup="filterUsers()">
                    </div>
                </div>
                <div id="user-list"></div>
                <button id="btn-load-more" class="btn btn-link btn-sm w-100" onclick="showMoreUsers()" style="display:none;">Xem thêm</button>
            </div>

            <div class="col-md-8 col-lg-9 p-0 chat-content">
                <div id="chat-header" class="p-3 border-bottom fw-bold bg-white d-flex align-items-center">
                    <i class="fa-solid fa-comments me-2 text-primary"></i>
                    <span id="chat-title">Chọn khách hàng để hỗ trợ</span>
                </div>
                <div id="msg-list" class="msg-list"></div>
                <div class="p-3 bg-white border-top">
                    <div class="input-group align-items-center">
                        <input type="file" id="admin-image-input" accept="image/*" style="display: none;" onchange="previewAdminImage()">
                        <button class="btn btn-light border-0" onclick="document.getElementById('admin-image-input').click()">
                            <i class="fa-regular fa-image text-muted" style="font-size: 20px;"></i>
                        </button>

                        <input type="text" id="admin-reply" class="form-control border-0 bg-light" placeholder="Nhập tin nhắn..." onkeypress="if(event.key === 'Enter') sendReply()">
                        <button class="btn btn-primary px-4 ms-2 rounded-pill" onclick="sendReply()"><i class="fa-solid fa-paper-plane"></i></button>
                    </div>
                    <div id="admin-file-preview" class="small text-primary mt-1" style="display:none; font-size: 11px; margin-left: 45px;"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentUserId = null;
        let visibleCount = 8;
        let menuTimer = null; // Quản lý tự động đóng menu

        function loadUserList() {
            fetch('get_chat_users.php').then(res => res.text()).then(data => {
                const list = document.getElementById('user-list');
                list.innerHTML = data;
                if (currentUserId) {
                    const activeItem = list.querySelector(`.user-item[onclick*="'${currentUserId}'"]`);
                    if (activeItem) activeItem.classList.add('active');
                }
                filterUsers();
            });
        }

        function filterUsers() {
            const input = document.getElementById('search-input').value.toLowerCase();
            const items = document.getElementsByClassName('user-item');
            let matchCount = 0;
            for (let i = 0; i < items.length; i++) {
                const name = items[i].innerText.toLowerCase();
                if (name.includes(input)) {
                    items[i].style.display = (input === "" && matchCount >= visibleCount) ? "none" : "block";
                    matchCount++;
                } else {
                    items[i].style.display = "none";
                }
            }
            document.getElementById('btn-load-more').style.display = (input === "" && matchCount > visibleCount) ? "block" : "none";
        }

        function openChat(userId, username) {
            currentUserId = userId;
            document.getElementById('chat-title').innerText = "Chat: " + username;
            document.querySelectorAll('.user-item').forEach(el => el.classList.remove('active'));
            loadMessages(true);
        }

        function loadMessages(forceScroll = false) {
            if (!currentUserId) return;

            const msgList = document.getElementById('msg-list');

            fetch(`get_messages.php?user_id=${currentUserId}`)
                .then(res => res.text())
                .then(data => {
                    // Tạo một khung tạm để so sánh nội dung
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;
                    const newMsgCount = tempDiv.querySelectorAll('.message-wrapper').length;
                    const currentMsgCount = msgList.querySelectorAll('.message-wrapper').length;

                    // Kiểm tra xem menu "Gỡ" có đang mở không
                    const isMenuOpen = Array.from(document.querySelectorAll('.action-menu')).some(el => el.style.display === 'block');

                    // CHỈ CẬP NHẬT KHI: 
                    // 1. Số lượng tin nhắn thay đổi (có tin mới hoặc vừa gỡ tin)
                    // 2. Hoặc khi ép buộc cuộn (forceScroll)
                    // 3. VÀ quan trọng nhất: Không cập nhật khi đang mở menu (để tránh mất menu) 
                    //    TRỪ KHI có tin nhắn mới thực sự.

                    if (newMsgCount !== currentMsgCount || forceScroll) {
                        if (isMenuOpen && newMsgCount === currentMsgCount) {
                            // Nếu đang mở menu và số lượng tin không đổi thì kệ nó, không làm gì cả
                            return;
                        }

                        const isAtBottom = msgList.scrollHeight - msgList.scrollTop <= msgList.clientHeight + 100;
                        msgList.innerHTML = data;
                        if (isAtBottom || forceScroll) msgList.scrollTop = msgList.scrollHeight;
                    }
                });
        }

        function previewAdminImage() {
            const file = document.getElementById('admin-image-input').files[0];
            const preview = document.getElementById('admin-file-preview');
            if (file) {
                preview.innerText = "Sắp gửi: " + file.name;
                preview.style.display = "block";
            }
        }

        function sendReply() {
            const input = document.getElementById('admin-reply');
            const imageInput = document.getElementById('admin-image-input');
            const msg = input.value.trim();
            const file = imageInput.files[0];

            if ((!msg && !file) || !currentUserId) return;

            let formData = new FormData();
            formData.append('receiver_id', currentUserId);
            formData.append('message', msg);
            if (file) {
                formData.append('image', file);
            }

            fetch('admin_send_msg.php', { // Tuan kiểm tra đúng tên file xử lý nhé
                method: 'POST',
                body: formData
            }).then(res => res.json()).then(data => {
                if (data.status === 'success') {
                    input.value = "";
                    imageInput.value = "";
                    document.getElementById('admin-file-preview').style.display = "none";
                    loadMessages(true);
                }
            });
        }

        function toggleActionMenu(event, msgId) {
            event.stopPropagation();

            // Đóng tất cả các menu khác đang mở để tránh bị chồng chéo
            document.querySelectorAll('.action-menu').forEach(el => {
                if (el.id !== 'menu-' + msgId) el.style.display = 'none';
            });

            const menu = document.getElementById('menu-' + msgId);
            // Nếu đang hiện thì ẩn, đang ẩn thì hiện
            menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
        }

        // Quan trọng: Khi nhấn chuột ra ngoài (vùng trống, tin nhắn khác...) thì đóng menu
        document.addEventListener('click', function() {
            document.querySelectorAll('.action-menu').forEach(el => {
                el.style.display = 'none';
            });
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
                        if (data.status === 'success') loadMessages();
                    });
            }
        }

        setInterval(() => {
            loadUserList();
            loadMessages();
        }, 3000);
        loadUserList();
    </script>
</body>

</html>