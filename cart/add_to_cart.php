<?php
require_once __DIR__ . '/../config/session.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /rivalsociety/page.php");
    exit;
}

$product_id = (int)($_POST['product_id'] ?? 0);
$name       = trim($_POST['name'] ?? '');
$price      = (float)($_POST['price'] ?? 0);
$image      = trim($_POST['image'] ?? '');
$size       = trim($_POST['size'] ?? '');
$quantity   = max(1, (int)($_POST['quantity'] ?? 1));

if (!$product_id || !$size) {
    header("Location: /rivalsociety/page.php");
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cartKey = $product_id . '_' . $size;

if (isset($_SESSION['cart'][$cartKey])) {
    $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
} else {
    $_SESSION['cart'][$cartKey] = [
        'id'       => $product_id,
        'name'     => $name,
        'price'    => $price,
        'image'    => $image,
        'size'     => $size,
        'quantity' => $quantity
    ];
}

header("Location: /rivalsociety/cart/view.php");
exit;
