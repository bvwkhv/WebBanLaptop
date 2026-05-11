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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Chat | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --bg-chat: #f4f7f6;
        }

        /* Container chính chiếm hết chiều cao màn hình */
        .chat-container-row {
            height: 90vh;
            margin-top: 10px;
        }

        .chat-sidebar {
            height: 100%;
            background: #fff;
            border-right: 1px solid #eaeaea;
            display: flex;
            flex-direction: column;
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

        .user-item.active {
            background: #e7f0ff;
            border-left-color: var(--primary-color);
        }

        .chat-content {
            height: 100%;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        .msg-list {
            flex-grow: 1;
            overflow-y: auto;
            padding: 15px;
            background: var(--bg-chat);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* Mobile Optimization */
        @media (max-width: 768px) {
            .chat-container-row {
                height: 100vh;
                margin-top: 0;
            }
            
            /* Ẩn danh sách khi đang chat và ngược lại */
            .chat-sidebar {
                width: 100% !important;
                display: flex;
            }
            .chat-content {
                width: 100% !important;
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                z-index: 1000;
            }

            /* Class điều khiển hiển thị trên mobile */
            body.is-chatting .chat-sidebar { display: none; }
            body.is-chatting .chat-content { display: flex; }
            
            .msg-content-container { max-width: 90% !important; }
        }

        .message-wrapper { display: flex; flex-direction: column; width: 100%; }
        .message-wrapper.me { align-items: flex-end; }
        .msg-content-container { display: flex; align-items: flex-end; max-width: 75%; gap: 8px; position: relative; }
        
        .msg-user, .msg-admin {
            padding: 10px 15px;
            border-radius: 18px;
            font-size: 14px;
            word-wrap: break-word;
        }
        .msg-user { background: var(--primary-color); color: white; border-radius: 18px 18px 2px 18px; order: 2; }
        .msg-admin { background: white; color: #333; border-radius: 18px 18px 18px 2px; }
        
        .msg-options { cursor: pointer; color: #ccc; order: 1; padding: 5px; }
        .action-menu {
            display: none; position: absolute; background: white; border: 1px solid #ddd;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 8px; z-index: 1000;
            bottom: 30px; left: 0; min-width: 60px;
        }
    </style>
</head>

<body>
    <div class="container-fluid px-md-4">
        <div class="row chat-container-row shadow-sm rounded-4 overflow-hidden border">
            <div class="col-md-4 col-lg-3 p-0 chat-sidebar" id="sidebar-panel">
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold">Tin nhắn</h6>
                    <a href="admin_dashboard.php" class="btn btn-light btn-sm"><i class="fa-solid fa-house"></i></a>
                </div>
                <div class="p-2 border-bottom">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fa fa-search text-muted"></i></span>
                        <input type="text" id="search-input" class="form-control bg-light border-0" placeholder="Tìm khách hàng..." onkeyup="filterUsers()">
                    </div>
                </div>
                <div id="user-list"></div>
            </div>

            <div class="col-md-8 col-lg-9 p-0 chat-content" id="chat-panel">
                <div id="chat-header" class="p-3 border-bottom fw-bold bg-white d-flex align-items-center">
                    <button class="btn btn-light d-md-none me-2" onclick="closeChat()">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <i class="fa-solid fa-circle-user me-2 text-primary" style="font-size: 20px;"></i>
                    <span id="chat-title">Chọn khách hàng</span>
                </div>

                <div id="msg-list" class="msg-list">
                    <div class="text-center mt-5 text-muted">Chọn một cuộc trò chuyện để bắt đầu</div>
                </div>

                <div class="p-3 bg-white border-top">
                    <div class="input-group align-items-center">
                        <input type="file" id="admin-image-input" accept="image/*" style="display: none;" onchange="previewAdminImage()">
                        <button class="btn btn-light border-0 me-1" onclick="document.getElementById('admin-image-input').click()">
                            <i class="fa-regular fa-image text-muted" style="font-size: 20px;"></i>
                        </button>

                        <input type="text" id="admin-reply" class="form-control border-0 bg-light rounded-pill" placeholder="Nhập nội dung phản hồi..." onkeypress="if(event.key === 'Enter') sendReply()">
                        <button class="btn btn-primary px-4 ms-2 rounded-pill" onclick="sendReply()">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>
                    <div id="admin-file-preview" class="small text-primary mt-2" style="display:none; margin-left: 45px;"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentUserId = null;
        let visibleCount = 10;

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

        function openChat(userId, username) {
            currentUserId = userId;
            document.getElementById('chat-title').innerText = username;
            
            // Xử lý giao diện Mobile
            if (window.innerWidth <= 768) {
                document.body.classList.add('is-chatting');
            }

            document.querySelectorAll('.user-item').forEach(el => el.classList.remove('active'));
            loadMessages(true);
        }

        function closeChat() {
            document.body.classList.remove('is-chatting');
            currentUserId = null;
        }

        function loadMessages(forceScroll = false) {
            if (!currentUserId) return;
            const msgList = document.getElementById('msg-list');

            fetch(`get_messages.php?user_id=${currentUserId}`)
                .then(res => res.text())
                .then(data => {
                    const isAtBottom = msgList.scrollHeight - msgList.scrollTop <= msgList.clientHeight + 100;
                    msgList.innerHTML = data;
                    if (isAtBottom || forceScroll) msgList.scrollTop = msgList.scrollHeight;
                });
        }

        function previewAdminImage() {
            const file = document.getElementById('admin-image-input').files[0];
            const preview = document.getElementById('admin-file-preview');
            if (file) {
                preview.innerHTML = `<i class="fa-solid fa-file-image me-1"></i> Sắp gửi: ${file.name} <i class="fa-solid fa-xmark ms-2 text-danger" onclick="clearImage()" style="cursor:pointer"></i>`;
                preview.style.display = "block";
            }
        }

        function clearImage() {
            document.getElementById('admin-image-input').value = "";
            document.getElementById('admin-file-preview').style.display = "none";
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
            if (file) formData.append('image', file);

            fetch('admin_send_msg.php', {
                method: 'POST',
                body: formData
            }).then(res => res.json()).then(data => {
                if (data.status === 'success') {
                    input.value = "";
                    clearImage();
                    loadMessages(true);
                }
            });
        }

        function filterUsers() {
            const input = document.getElementById('search-input').value.toLowerCase();
            const items = document.getElementsByClassName('user-item');
            for (let i = 0; i < items.length; i++) {
                const name = items[i].innerText.toLowerCase();
                items[i].style.display = name.includes(input) ? "block" : "none";
            }
        }

        function toggleActionMenu(event, msgId) {
            event.stopPropagation();
            const menu = document.getElementById('menu-' + msgId);
            const isOpen = menu.style.display === 'block';
            document.querySelectorAll('.action-menu').forEach(el => el.style.display = 'none');
            menu.style.display = isOpen ? 'none' : 'block';
        }

        document.addEventListener('click', () => {
            document.querySelectorAll('.action-menu').forEach(el => el.style.display = 'none');
        });

        function confirmDelete(messageId) {
            if (confirm("Gỡ tin nhắn này?")) {
                let formData = new FormData();
                formData.append('message_id', messageId);
                fetch('delete_message.php', { method: 'POST', body: formData })
                    .then(res => res.json()).then(data => {
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