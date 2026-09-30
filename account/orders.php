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
<?php rs_critical_css(); ?>
<link rel="stylesheet" href="<?= asset_url('/assets/base.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/assets/style.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>

<main class="orders-section">
<h1>My Orders</h1>

<?php if (empty($orders)): ?>
    <div class="empty-cart">
      <p>You haven’t placed any orders yet.</p>
      <a href="<?= url('/page.php#store') ?>" class="btn primary">Start Shopping</a>
    </div>
<?php endif; ?>

<?php foreach ($orders as $order): ?>
<article class="order-card">
    <div class="order-header">
        <div class="order-id">
            Order #<?= htmlspecialchars((string)$order['id'], ENT_QUOTES, 'UTF-8') ?><br>
            <small><?= htmlspecialchars(date("d M Y, h:i A", strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8') ?></small>
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
</article>
<?php endforeach; ?>

<a href="<?= url('/account/dashboard.php') ?>" class="back-link">
    ← Back to Dashboard
</a>

</main>

<footer class="site-footer">
  <p>&copy; <?= date('Y') ?> THE RIVAL SOCIETY. All rights reserved.</p>
</footer>

</body>
</html>
