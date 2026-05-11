<?php
require_once "database.php";
session_start();
$db = new database();

$target_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 4; // Mặc định admin là 4
$current_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if ($target_id > 0 && $current_id > 0) {
    // Đánh dấu đã đọc
    $db->execute("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?", 'ii', [$target_id, $current_id]);

    $sql = "SELECT * FROM messages 
            WHERE (sender_id = ? AND receiver_id = ?) 
            OR (sender_id = ? AND receiver_id = ?) 
            ORDER BY created_at ASC";

    $messages = $db->select($sql, 'iiii', [$target_id, $current_id, $current_id, $target_id]);

    if (!empty($messages)) {
        foreach ($messages as $m) {
    $is_me = ($m['sender_id'] == $current_id);
    $wrapper_class = $is_me ? 'me' : 'other';
    $bubble_class = $is_me ? 'msg-user' : 'msg-admin';

    // Container bọc cả tin nhắn và nút gỡ
    echo "<div class='message-wrapper $wrapper_class' style='display: flex; flex-direction: column; margin-bottom: 12px; " . ($is_me ? "align-items: flex-end;" : "align-items: flex-start;") . "'>";
    
    // Khung chứa bong bóng và nút options (để nút luôn nằm cạnh bong bóng)
    echo "  <div class='msg-content-container' style='display: flex; align-items: center; gap: 8px; max-width: 85%; position: relative;'>";
    
    // Nếu là tin của mình, hiện nút gỡ bên TRÁI tin nhắn
    if ($is_me) {
        echo "<div class='msg-options' style='position: relative; order: 1;'>
                <i class='fa-solid fa-ellipsis-vertical' style='cursor: pointer; color: #ccc; padding: 5px;' onclick='toggleActionMenu(event, " . $m['message_id'] . ")'></i>
                <div class='action-menu' id='menu-" . $m['message_id'] . "' style='display: none; position: absolute; background: white; border: 1px solid #ddd; box-shadow: 0 2px 8px rgba(0,0,0,0.15); border-radius: 6px; z-index: 100; min-width: 50px; bottom: 100%; left: 50%; transform: translateX(-50%);'>
                    <div class='menu-item' onclick='confirmDelete(" . $m['message_id'] . ")' style='padding: 6px 10px; color: #ff4d4d; font-size: 11px; font-weight: bold; cursor: pointer; text-align: center;'>Gỡ</div>
                </div>
              </div>";
    }

    // Bong bóng tin nhắn
    $bg_color = $is_me ? "#007bff" : "#f1f0f0";
    $text_color = $is_me ? "white" : "black";
    $border_radius = $is_me ? "18px 18px 2px 18px" : "18px 18px 18px 2px";
    
    echo "    <div class='$bubble_class' style='background: $bg_color; color: $text_color; padding: 10px 14px; border-radius: $border_radius; order: 2; flex: 1; min-width: 0; word-wrap: break-word;'>";
    echo "      <div class='msg-text' style='font-size: 14px; line-height: 1.4;'>" . htmlspecialchars($m['message_text']) . "</div>";
    echo "      <small class='msg-time' style='font-size: 9px; display: block; margin-top: 4px; opacity: 0.7; text-align: right;'>" . date('H:i', strtotime($m['created_at'])) . "</small>";
    echo "    </div>";
    
    echo "  </div>"; // Đóng msg-content-container
    echo "</div>"; // Đóng message-wrapper
}
    } else {
        echo "<div class='text-center text-muted mt-4' style='font-size: 13px;'>Bắt đầu trò chuyện với Admin</div>";
    }
}