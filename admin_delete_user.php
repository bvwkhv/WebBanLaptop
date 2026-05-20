<?php
require_once "auth_check.php";
require_once "database.php";

if (isset($_GET['id'])) {
    $db = new Database();
    $sql = "DELETE FROM users WHERE user_id = ?";
    $db->execute($sql, "i", [$_GET['id']]);
}
header("Location: admin_users.php");
exit();