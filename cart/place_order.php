<?php
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit;
}

/* For now, just clear cart */
unset($_SESSION['cart']);

echo "<h2>Order Placed Successfully!</h2>";
echo "<p>Payment integration coming soon.</p>";
