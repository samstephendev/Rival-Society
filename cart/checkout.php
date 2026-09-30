<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header("Location: /rivalsociety/cart/view.php");
    exit;
}

$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}

$_SESSION['checkout_total'] = $total;
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Checkout | Rival Society</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/rivalsociety/assets/style.css">
<link rel="stylesheet" href="/rivalsociety/cart/style.css">
</head>

<body>

<section class="checkout-section">
<div class="checkout-card">

<h1>Checkout</h1>

<form action="/rivalsociety/payment/pay.php" method="POST">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

<h3>Billing Information</h3>

<input type="text" name="full_name" required placeholder="Full Name" class="field">
<input type="email" name="email" required placeholder="Email" class="field">
<input type="tel" name="phone" required placeholder="Phone" class="field">

<textarea name="address" required placeholder="Address" class="field"></textarea>

<div class="row">
    <input type="text" name="city" required placeholder="City" class="field">
    <input type="text" name="state" required placeholder="State" class="field">
</div>

<input type="text" name="pincode" required placeholder="Pincode" class="field">

<div class="checkout-summary">
    <div class="summary-row total">
        <span>Total</span>
        <span>₹<?= number_format($total, 2) ?></span>
    </div>
</div>

<button type="submit" class="btn primary checkout-btn">
Proceed to Payment
</button>
</form>

<a href="/rivalsociety/cart/view.php" class="btn secondary back-btn">
← Back to Cart
</a>

</div>
</section>

</body>
</html>
