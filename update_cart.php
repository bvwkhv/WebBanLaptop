<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $qty = intval($_POST['qty']);

    if (isset($_SESSION['cart'][$id]) && $qty > 0) {
        $_SESSION['cart'][$id]['qty'] = $qty;
    }
}
header("Location: view_cart.php");
exit();