<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . url('/page.php#store'));
    exit;
}

if (!isset($_SESSION['cust_user_id'])) {
    header("Location: " . url('/account/login.php'));
    exit;
}

if (!csrf_verify()) {
    header("Location: " . url('/page.php#store'));
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$size      = strtoupper(trim($_POST['size'] ?? ''));
$quantity  = max(1, (int)($_POST['quantity'] ?? 1));

$allowedSizes = ['S', 'M', 'L', 'XL', 'XXL'];
if (!$productId || !in_array($size, $allowedSizes, true)) {
    header("Location: " . url('/page.php#store'));
    exit;
}

try {
    // ALWAYS read product details and price directly from the database
    $stmt = $conn->prepare("SELECT id, name, price, image_front, stock, status FROM products WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product || (int)$product['stock'] <= 0) {
        header("Location: " . url('/page.php#store'));
        exit;
    }

    $availableStock = (int)$product['stock'];

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $cartKey = $productId . '_' . $size;
    $existingQty = isset($_SESSION['cart'][$cartKey]) ? (int)$_SESSION['cart'][$cartKey]['quantity'] : 0;
    $newQty = min($availableStock, $existingQty + $quantity);

    $_SESSION['cart'][$cartKey] = [
        'id'       => (int)$product['id'],
        'name'     => $product['name'],
        'price'    => (float)$product['price'],
        'image'    => $product['image_front'],
        'size'     => $size,
        'quantity' => $newQty
    ];

    header("Location: " . url('/cart/view.php'));
    exit;

} catch (mysqli_sql_exception $e) {
    error_log("Add to cart error: " . $e->getMessage());
    header("Location: " . url('/page.php#store'));
    exit;
}
