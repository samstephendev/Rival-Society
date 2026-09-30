<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

$orderId = (int)($_SESSION['last_order_id'] ?? 0);
$userId  = (int)$_SESSION['cust_user_id'];

if ($orderId <= 0) {
    header("Location: " . url('/page.php#store'));
    exit;
}

$order = null;
try {
    $stmt = $conn->prepare("SELECT id, total, payment_status, razorpay_payment_id, created_at FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
} catch (mysqli_sql_exception $e) {
    error_log("Success page query error: " . $e->getMessage());
}

if (!$order) {
    header("Location: " . url('/account/orders.php'));
    exit;
}

$status = $order['payment_status'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Status | Rival Society</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
<style>
.success-section {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0e0e0e;
    padding: 20px;
}
.success-card {
    background: #151515;
    border: 1px solid #333;
    border-radius: 14px;
    padding: 40px;
    max-width: 440px;
    width: 100%;
    text-align: center;
    box-shadow: 0 0 30px rgba(0,0,0,0.6);
}
.success-card h1 {
    margin-bottom: 10px;
    font-size: 26px;
}
.status-paid h1 { color: #4cff4c; }
.status-pending h1 { color: #f5c542; }
.status-failed h1 { color: #ff4c4c; }
.order-id {
    background: #000;
    border: 1px solid #333;
    padding: 12px;
    border-radius: 8px;
    margin: 20px 0;
    color: #fff;
    font-weight: 600;
}
.status-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
}
.badge-paid { background: #163; color: #4cff4c; }
.badge-pending { background: #332b00; color: #f5c542; }
.badge-failed { background: #400; color: #ff4c4c; }
.success-actions {
    display: flex;
    gap: 12px;
    flex-direction: column;
    margin-top: 25px;
}
.success-actions a {
    text-decoration: none;
    padding: 12px;
    border-radius: 8px;
    font-weight: 600;
    transition: 0.3s;
}
.success-actions .primary {
    background: #fff;
    color: #000;
}
.success-actions .primary:hover {
    background: #eaeaea;
}
.success-actions .secondary {
    background: transparent;
    border: 1px solid #444;
    color: #aaa;
}
.success-actions .secondary:hover {
    border-color: #666;
    color: #fff;
}
</style>
</head>
<body>

<section class="success-section">
    <div class="success-card status-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($status === 'paid'): ?>
            <h1>Payment Successful!</h1>
            <p style="color:#aaa;">Thank you for your purchase with Rival Society.</p>
        <?php elseif ($status === 'pending'): ?>
            <h1>Order Pending</h1>
            <p style="color:#aaa;">Your payment is being processed.</p>
        <?php else: ?>
            <h1>Payment Incomplete</h1>
            <p style="color:#aaa;">The payment was not completed or failed verification.</p>
        <?php endif; ?>

        <div class="order-id">
            Order ID: <strong>#<?= htmlspecialchars((string)$order['id'], ENT_QUOTES, 'UTF-8') ?></strong><br>
            Total: <strong>₹<?= number_format((float)$order['total'], 2) ?></strong>
        </div>

        <p>Status: <span class="status-badge badge-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?></span></p>

        <?php if (!empty($order['razorpay_payment_id'])): ?>
            <p style="color:#777; font-size: 12px; margin-top: 5px;">Payment ID: <?= htmlspecialchars($order['razorpay_payment_id'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <div class="success-actions">
            <a href="<?= url('/account/orders.php') ?>" class="primary">
                View My Orders
            </a>
            <a href="<?= url('/page.php#store') ?>" class="secondary">
                Continue Shopping
            </a>
        </div>
    </div>
</section>

</body>
</html>
