<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

$orderId = $_SESSION['last_order_id'] ?? null;

if (!$orderId) {
    header("Location: /rivalsociety/page.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Successful | Rival Society</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="/rivalsociety/assets/style.css">

<style>
.success-section {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0e0e0e;
}

.success-card {
    background: #151515;
    border-radius: 14px;
    padding: 40px;
    max-width: 420px;
    width: 100%;
    text-align: center;
    box-shadow: 0 0 30px rgba(0,0,0,0.6);
}

.success-card h1 {
    color: #4cff4c;
    margin-bottom: 10px;
    font-size: 26px;
}

.success-card p {
    color: #bbb;
    font-size: 15px;
    margin-bottom: 20px;
}

.order-id {
    background: #000;
    border: 1px solid #333;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 25px;
    color: #fff;
    font-weight: 600;
}

.success-actions {
    display: flex;
    gap: 12px;
    flex-direction: column;
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
    <div class="success-card">

        <h1>Order Placed Successfully</h1>
        <p>Thank you for shopping with Rival Society.</p>

        <div class="order-id">
            Order ID: <strong>#<?= htmlspecialchars($orderId) ?></strong>
        </div>

        <p>Your payment status is currently <strong>Pending</strong>.</p>

        <div class="success-actions">
            <a href="/rivalsociety/account/dashboard.php" class="primary">
                View Orders
            </a>
            <a href="/rivalsociety/page.php" class="secondary">
                Continue Shopping
            </a>
        </div>

    </div>
</section>

</body>
</html>
