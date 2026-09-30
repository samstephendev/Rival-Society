<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . url('/cart/checkout.php'));
    exit;
}

if (!csrf_verify()) {
    header("Location: " . url('/cart/checkout.php?error=csrf'));
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header("Location: " . url('/cart/view.php'));
    exit;
}

$userId   = (int)$_SESSION['cust_user_id'];
$fullName = trim($_POST['full_name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$address  = trim($_POST['address'] ?? '');
$city     = trim($_POST['city'] ?? '');
$state    = trim($_POST['state'] ?? '');
$pincode  = trim($_POST['pincode'] ?? '');

if ($fullName === '' || $email === '' || $phone === '' || $address === '' || $city === '' || $state === '' || $pincode === '') {
    header("Location: " . url('/cart/checkout.php?error=missing_fields'));
    exit;
}

$conn->begin_transaction();

try {
    $calculatedTotal = 0.0;
    $itemsToInsert = [];

    // Verify each item, recompute live total from DB, and perform atomic stock decrement
    foreach ($cart as $item) {
        $productId = (int)($item['id'] ?? 0);
        $qty       = max(1, (int)($item['quantity'] ?? 1));
        $size      = htmlspecialchars($item['size'] ?? 'M', ENT_QUOTES, 'UTF-8');

        // Fetch live product data
        $pStmt = $conn->prepare("SELECT id, name, price, stock, status FROM products WHERE id = ? FOR UPDATE");
        $pStmt->bind_param("i", $productId);
        $pStmt->execute();
        $product = $pStmt->get_result()->fetch_assoc();

        if (!$product || $product['status'] !== 'active') {
            throw new Exception("One or more items in your cart are no longer available.");
        }

        // Atomic stock decrement: UPDATE ... WHERE stock >= ?
        $decStmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $decStmt->bind_param("iii", $qty, $productId, $qty);
        $decStmt->execute();

        if ($decStmt->affected_rows === 0) {
            throw new Exception("Product '" . $product['name'] . "' ran out of stock. Please adjust your cart.");
        }

        $unitPrice = (float)$product['price'];
        $calculatedTotal += $unitPrice * $qty;

        $itemsToInsert[] = [
            'product_id'   => $productId,
            'product_name' => $product['name'],
            'price'        => $unitPrice,
            'quantity'     => $qty,
            'size'         => $size
        ];
    }

    if ($calculatedTotal <= 0 || empty($itemsToInsert)) {
        throw new Exception("Invalid order total.");
    }

    // 1. Create Order
    $orderStmt = $conn->prepare(
        "INSERT INTO orders (user_id, total, payment_status) VALUES (?, ?, 'pending')"
    );
    $orderStmt->bind_param("id", $userId, $calculatedTotal);
    $orderStmt->execute();
    $orderId = (int)$orderStmt->insert_id;

    if ($orderId <= 0) {
        throw new Exception("Failed to generate order record.");
    }

    // 2. Insert Billing Record
    $billStmt = $conn->prepare(
        "INSERT INTO order_billing (order_id, full_name, email, phone, address, city, state, pincode)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $billStmt->bind_param("isssssss", $orderId, $fullName, $email, $phone, $address, $city, $state, $pincode);
    $billStmt->execute();

    // 3. Insert Order Items (with product_id FK)
    $itemStmt = $conn->prepare(
        "INSERT INTO order_items (order_id, product_id, product_name, price, quantity, size)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    foreach ($itemsToInsert as $it) {
        $itemStmt->bind_param(
            "iisdis",
            $orderId,
            $it['product_id'],
            $it['product_name'],
            $it['price'],
            $it['quantity'],
            $it['size']
        );
        $itemStmt->execute();
    }

    $conn->commit();

    // Store current order in session
    $_SESSION['last_order_id'] = $orderId;
    $_SESSION['checkout_total'] = $calculatedTotal;

    // Proceed to create Razorpay Order
    header("Location: " . url('/payment/create_order.php'));
    exit;

} catch (Exception $e) {
    $conn->rollback();
    error_log("Order creation failed: " . $e->getMessage());
    $_SESSION['checkout_error'] = $e->getMessage();
    header("Location: " . url('/cart/checkout.php'));
    exit;
}
