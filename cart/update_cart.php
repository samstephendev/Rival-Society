<?php
require_once __DIR__ . '/../config/session.php';

$key    = $_POST['key'] ?? '';
$action = $_POST['action'] ?? '';

if (!$key || !isset($_SESSION['cart'][$key])) {
    exit;
}

if ($action === 'plus') {
    $_SESSION['cart'][$key]['quantity']++;
}

if ($action === 'minus') {
    $_SESSION['cart'][$key]['quantity']--;
    if ($_SESSION['cart'][$key]['quantity'] <= 0) {
        unset($_SESSION['cart'][$key]);
    }
}
