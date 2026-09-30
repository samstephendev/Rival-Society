<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

/* ===============================
   BASIC GUARDS
================================ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /rivalsociety/");
    exit;
}

if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    die("Invalid CSRF token");
}

$cart  = $_SESSION['cart'] ?? [];
$total = $_SESSION['checkout_total'] ?? 0;

if (empty($cart) || $total <= 0) {
    die("Cart empty or total invalid");
}

$userId = (int) $_SESSION['cust_user_id'];

/* ===============================
   START TRANSACTION (CRITICAL)
================================ */
$conn->begin_transaction();

try {

    /* ===============================
       CREATE ORDER
    ================================ */
    $orderStmt = $conn->prepare(
        "INSERT INTO orders (user_id, total, payment_status)
         VALUES (?, ?, 'pending')"
    );
    if (!$orderStmt) {
        throw new Exception($conn->error);
    }

    $orderStmt->bind_param("id", $userId, $total);
    $orderStmt->execute();

    $orderId = $orderStmt->insert_id;
    if ($orderId <= 0) {
        throw new Exception("Order not created");
    }

    /* ===============================
       BILLING DETAILS
    ================================ */
    $billStmt = $conn->prepare(
        "INSERT INTO order_billing
        (order_id, full_name, email, phone, address, city, state, pincode)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$billStmt) {
        throw new Exception($conn->error);
    }

    $billStmt->bind_param(
        "isssssss",
        $orderId,
        $_POST['full_name'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['address'],
        $_POST['city'],
        $_POST['state'],
        $_POST['pincode']
    );
    $billStmt->execute();

    /* ===============================
       ORDER ITEMS
    ================================ */
    $itemStmt = $conn->prepare(
        "INSERT INTO order_items
        (order_id, product_name, price, quantity, size)
        VALUES (?, ?, ?, ?, ?)"
    );
    if (!$itemStmt) {
        throw new Exception($conn->error);
    }

    foreach ($cart as $item) {
        $itemStmt->bind_param(
            "isdis",
            $orderId,
            $item['name'],
            $item['price'],
            $item['quantity'],
            $item['size']
        );
        $itemStmt->execute();
    }

    /* ===============================
       COMMIT
    ================================ */
    $conn->commit();

    /* ===============================
       CLEAN SESSION
    ================================ */
    unset($_SESSION['cart'], $_SESSION['checkout_total'], $_SESSION['csrf_token']);
    $_SESSION['last_order_id'] = $orderId;

    header("Location: /rivalsociety/payment/success.php");
    exit;

} catch (Exception $e) {

    $conn->rollback();
    die("PAYMENT ERROR: " . $e->getMessage());
}
