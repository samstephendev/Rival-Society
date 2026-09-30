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
<link rel="stylesheet" href="<?= url('/assets/base.css') ?>">
<link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>

<main class="success-section">
    <div class="success-card status-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($status === 'paid'): ?>
            <h1>Payment Successful!</h1>
            <p style="color: var(--rs-text-secondary); margin-top: 8px;">Thank you for your purchase with Rival Society.</p>
        <?php elseif ($status === 'pending'): ?>
            <h1>Order Pending</h1>
            <p style="color: var(--rs-text-secondary); margin-top: 8px;">Your payment is being processed.</p>
        <?php else: ?>
            <h1>Payment Incomplete</h1>
            <p style="color: var(--rs-text-secondary); margin-top: 8px;">The payment was not completed or failed verification.</p>
        <?php endif; ?>

        <div class="order-id">
            Order ID: <strong>#<?= htmlspecialchars((string)$order['id'], ENT_QUOTES, 'UTF-8') ?></strong><br>
            Total Amount: <strong>₹<?= number_format((float)$order['total'], 2) ?></strong>
        </div>

        <p style="margin: 16px 0;">
            Status: <span class="status-badge badge-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?></span>
        </p>

        <?php if (!empty($order['razorpay_payment_id'])): ?>
            <p style="color: var(--rs-text-dim); font-size: 13px; margin-top: 6px;">Payment ID: <?= htmlspecialchars($order['razorpay_payment_id'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <div class="success-actions">
            <a href="<?= url('/account/orders.php') ?>" class="btn primary">
                <i class="fas fa-box" style="margin-right: 8px;"></i> View My Orders
            </a>
            <a href="<?= url('/page.php#store') ?>" class="btn secondary">
                <i class="fas fa-store" style="margin-right: 8px;"></i> Continue Shopping
            </a>
        </div>
    </div>
</main>

<footer class="site-footer">
  <p>&copy; <?= date('Y') ?> THE RIVAL SOCIETY. All rights reserved.</p>
</footer>

</body>
</html>
