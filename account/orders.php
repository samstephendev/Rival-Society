<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

$userId = $_SESSION['cust_user_id'];

/* FETCH ORDERS */
$orderStmt = $conn->prepare("
    SELECT id, total, payment_status, created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$orderStmt->bind_param("i", $userId);
$orderStmt->execute();
$orders = $orderStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Orders | Rival Society</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="/rivalsociety/assets/style.css">

<style>
.orders-section {
    max-width: 900px;
    margin: 40px auto;
    padding: 20px;
}

.orders-section h1 {
    margin-bottom: 20px;
}

.order-card {
    background: #151515;
    border: 1px solid #333;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.order-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.order-id {
    font-weight: 600;
}

.order-status {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 13px;
    text-transform: uppercase;
}

.status-pending { background: #2a2a2a; color: #f5c542; }
.status-paid { background: #163; color: #4cff4c; }
.status-failed { background: #400; color: #ff4c4c; }

.order-items {
    border-top: 1px solid #333;
    padding-top: 10px;
}

.order-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 14px;
    color: #ccc;
}

.order-total {
    margin-top: 10px;
    font-weight: 700;
}
</style>
</head>

<body>

<section class="orders-section">
<h1>My Orders</h1>

<?php if ($orders->num_rows === 0): ?>
    <p>You haven’t placed any orders yet.</p>
<?php endif; ?>

<?php while ($order = $orders->fetch_assoc()): ?>

<div class="order-card">

    <div class="order-header">
        <div class="order-id">
            Order #<?= $order['id'] ?><br>
            <small><?= date("d M Y", strtotime($order['created_at'])) ?></small>
        </div>

        <div class="order-status status-<?= $order['payment_status'] ?>">
            <?= ucfirst($order['payment_status']) ?>
        </div>
    </div>

    <div class="order-items">
        <?php
        $itemsStmt = $conn->prepare("
            SELECT product_name, quantity, size, price
            FROM order_items
            WHERE order_id = ?
        ");
        $itemsStmt->bind_param("i", $order['id']);
        $itemsStmt->execute();
        $items = $itemsStmt->get_result();

        while ($item = $items->fetch_assoc()):
        ?>
        <div class="order-item">
            <span>
                <?= htmlspecialchars($item['product_name']) ?>
                (<?= $item['size'] ?> × <?= $item['quantity'] ?>)
            </span>
            <span>₹<?= number_format($item['price'], 0) ?></span>
        </div>
        <?php endwhile; ?>
    </div>

    <div class="order-total">
        Total: ₹<?= number_format($order['total'], 2) ?>
    </div>

</div>

<?php endwhile; ?>

<a href="/rivalsociety/account/dashboard.php" class="btn secondary">
← Back to Dashboard
</a>

</section>

</body>
</html>
