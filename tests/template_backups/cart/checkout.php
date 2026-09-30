<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header("Location: " . url('/cart/view.php'));
    exit;
}

// Recompute total strictly from the database
$verifiedTotal = 0.0;
$checkoutCart = [];
$stockErrors = [];

foreach ($cart as $key => $item) {
    $productId = (int)($item['id'] ?? 0);
    $qty = max(1, (int)($item['quantity'] ?? 1));

    try {
        $stmt = $conn->prepare("SELECT id, name, price, stock, status FROM products WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $dbProduct = $stmt->get_result()->fetch_assoc();

        if (!$dbProduct || $dbProduct['status'] !== 'active') {
            $stockErrors[] = "Item '" . htmlspecialchars($item['name'] ?? 'Product', ENT_QUOTES, 'UTF-8') . "' is no longer available.";
            unset($_SESSION['cart'][$key]);
            continue;
        }

        if ((int)$dbProduct['stock'] < $qty) {
            $stockErrors[] = "Insufficient stock for '" . htmlspecialchars($dbProduct['name'], ENT_QUOTES, 'UTF-8') . "'. Only {$dbProduct['stock']} remaining.";
            $_SESSION['cart'][$key]['quantity'] = (int)$dbProduct['stock'];
            $qty = (int)$dbProduct['stock'];
            if ($qty <= 0) {
                unset($_SESSION['cart'][$key]);
                continue;
            }
        }

        $price = (float)$dbProduct['price'];
        $verifiedTotal += $price * $qty;
        $checkoutCart[$key] = $item;
        $checkoutCart[$key]['price'] = $price;
        $checkoutCart[$key]['name'] = $dbProduct['name'];

    } catch (mysqli_sql_exception $e) {
        error_log("Checkout error: " . $e->getMessage());
    }
}

if (empty($_SESSION['cart'])) {
    header("Location: " . url('/cart/view.php'));
    exit;
}

$_SESSION['checkout_total'] = $verifiedTotal;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Checkout | Rival Society</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
<link rel="stylesheet" href="<?= url('/cart/style.css') ?>">
</head>
<body>

<section class="checkout-section">
<div class="checkout-card">

<h1>Checkout</h1>

<?php if (!empty($stockErrors)): ?>
    <div style="background: #4a1515; color: #ff6b6b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
        <?php foreach ($stockErrors as $err): ?>
            <p style="margin: 4px 0;"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form action="<?= url('/payment/pay.php') ?>" method="POST">
<?= csrf_field() ?>

<h3>Billing Information</h3>

<input type="text" name="full_name" required placeholder="Full Name" class="field" value="<?= htmlspecialchars($_SESSION['cust_user_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
<input type="email" name="email" required placeholder="Email (@gmail.com)" class="field">
<input type="tel" name="phone" required placeholder="Phone Number (10 digits)" pattern="^[0-9]{10}$" title="Enter a valid 10-digit phone number" class="field">

<textarea name="address" required placeholder="Delivery Street Address" class="field" rows="3"></textarea>

<div class="row">
    <input type="text" name="city" required placeholder="City" class="field">
    <input type="text" name="state" required placeholder="State" class="field">
</div>

<input type="text" name="pincode" required placeholder="Pincode (6 digits)" pattern="^[0-9]{6}$" title="Enter a valid 6-digit postal code" class="field">

<div class="checkout-summary">
    <div class="summary-row total">
        <span>Total Payable</span>
        <span>₹<?= number_format($verifiedTotal, 2) ?></span>
    </div>
</div>

<button type="submit" class="btn primary checkout-btn">
    Proceed to Payment
</button>
</form>

<a href="<?= url('/cart/view.php') ?>" class="btn secondary back-btn">
    ← Back to Cart
</a>

</div>
</section>

</body>
</html>
