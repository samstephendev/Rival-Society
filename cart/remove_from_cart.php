<?php
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . url('/cart/view.php'));
    exit;
}

if (!csrf_verify()) {
    header("Location: " . url('/cart/view.php'));
    exit;
}

$cartKey = trim($_POST['cart_key'] ?? '');

if ($cartKey !== '' && isset($_SESSION['cart'][$cartKey])) {
    unset($_SESSION['cart'][$cartKey]);
}

header("Location: " . url('/cart/view.php'));
exit;
