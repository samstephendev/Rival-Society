<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

$userId = (int)$_SESSION['cust_user_id'];
$orders = [];

try {
    $orderStmt = $conn->prepare("
        SELECT id, total, payment_status, created_at
        FROM orders
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $orderStmt->bind_param("i", $userId);
    $orderStmt->execute();
    $ordersResult = $orderStmt->get_result();
    while ($row = $ordersResult->fetch_assoc()) {
        $orders[] = $row;
    }
} catch (mysqli_sql_exception $e) {
    error_log("Orders query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Orders | Rival Society</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
<style>
.orders-section {
    max-width: 900px;
    margin: 40px auto;
    padding: 20px;
}
.orders-section h1 {
    margin-bottom: 20px;
    color: #fff;
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
    color: #fff;
}
.order-status {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 13px;
    text-transform: uppercase;
    font-weight: 600;
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
    color: #fff;
    font-size: 16px;
    text-align: right;
}
.back-link {
    display: inline-block;
    margin-top: 20px;
    color: #aaa;
    text-decoration: none;
    font-weight: 600;
}
.back-link:hover {
    color: #fff;
    text-decoration: underline;
}
</style>
</head>
<body>

<section class="orders-section">
<h1>My Orders</h1>

<?php if (empty($orders)): ?>
    <p style="color: #aaa;">You haven’t placed any orders yet.</p>
<?php endif; ?>

<?php foreach ($orders as $order): ?>
<div class="order-card">
    <div class="order-header">
        <div class="order-id">
            Order #<?= htmlspecialchars((string)$order['id'], ENT_QUOTES, 'UTF-8') ?><br>
            <small style="color: #888;"><?= htmlspecialchars(date("d M Y, h:i A", strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8') ?></small>
        </div>

        <div class="order-status status-<?= htmlspecialchars($order['payment_status'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars(ucfirst($order['payment_status']), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>

    <div class="order-items">
        <?php
        try {
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
                    <?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?>
                    (<?= htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8') ?> × <?= (int)$item['quantity'] ?>)
                </span>
                <span>₹<?= number_format((float)$item['price'], 2) ?></span>
            </div>
            <?php
            endwhile;
        } catch (mysqli_sql_exception $e) {
            echo "<p style='color:red;'>Could not load items.</p>";
        }
        ?>
    </div>

    <div class="order-total">
        Total: ₹<?= number_format((float)$order['total'], 2) ?>
    </div>
</div>
<?php endforeach; ?>

<a href="<?= url('/account/dashboard.php') ?>" class="back-link">
    ← Back to Dashboard
</a>

</section>

</body>
</html>
