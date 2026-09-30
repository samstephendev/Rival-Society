<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!csrf_verify()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$key    = trim($_POST['key'] ?? '');
$action = trim($_POST['action'] ?? '');

if (!$key || !isset($_SESSION['cart'][$key])) {
    echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
    exit;
}

$productId = (int)$_SESSION['cart'][$key]['id'];

try {
    $stmt = $conn->prepare("SELECT stock, status FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product || $product['status'] !== 'active') {
        unset($_SESSION['cart'][$key]);
        echo json_encode(['success' => false, 'message' => 'Product is no longer available']);
        exit;
    }

    $availableStock = (int)$product['stock'];

    if ($action === 'plus') {
        if ($_SESSION['cart'][$key]['quantity'] >= $availableStock) {
            echo json_encode(['success' => false, 'message' => "Maximum available stock is {$availableStock}"]);
            exit;
        }
        $_SESSION['cart'][$key]['quantity']++;
    } elseif ($action === 'minus') {
        $_SESSION['cart'][$key]['quantity']--;
        if ($_SESSION['cart'][$key]['quantity'] <= 0) {
            unset($_SESSION['cart'][$key]);
        }
    }

    echo json_encode(['success' => true]);
    exit;

} catch (mysqli_sql_exception $e) {
    error_log("Update cart DB error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
