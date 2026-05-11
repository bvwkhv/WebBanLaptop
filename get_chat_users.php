<?php
require_once "database.php";
session_start();

// 1. khởi tạo đối tượng
$db = new database(); 

// 2. lấy danh sách khách hàng và tin nhắn cuối cùng
$sql = "SELECT u.user_id, u.username, MAX(m.created_at) as last_time,
        (SELECT COUNT(*) FROM messages WHERE sender_id = u.user_id AND is_read = 0) as unread
        FROM users u 
        JOIN messages m ON u.user_id = m.sender_id OR u.user_id = m.receiver_id
        WHERE u.role = 'user'
        GROUP BY u.user_id 
        ORDER BY last_time DESC LIMIT 50";

$users = $db->select($sql);

if (empty($users)) {
    echo "<div class='p-3 text-muted'>Chưa có hội thoại nào.</div>";
} else {
    foreach($users as $u) {
        $unreadBadge = ($u['unread'] > 0) ? "<span class='unread-badge'>{$u['unread']}</span>" : "";
        $activeClass = ($u['unread'] > 0) ? 'fw-bold text-primary' : '';
        $time = $u['last_time'] ? date('H:i d/m', strtotime($u['last_time'])) : '';

        echo "<div class='user-item p-3 border-bottom' onclick='openChat({$u['user_id']}, \"{$u['username']}\")'>
                <div class='d-flex justify-content-between align-items-center'>
                    <span class='$activeClass'><i class='fa-solid fa-circle-user me-2'></i>{$u['username']}</span>
                    $unreadBadge
                </div>
                <small class='text-muted' style='font-size:10px'>$time</small>
              </div>";
    }
}