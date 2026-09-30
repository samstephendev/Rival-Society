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
<?php rs_critical_css(); ?>
<link rel="stylesheet" href="<?= asset_url('/assets/base.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/assets/style.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/cart/style.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>

<main class="checkout-section">
<div class="checkout-card">

<h1>Checkout</h1>

<?php if (!empty($stockErrors)): ?>
    <div class="alert alert-error">
        <?php foreach ($stockErrors as $err): ?>
            <p><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$checkoutError = $_SESSION['checkout_error'] ?? null;
unset($_SESSION['checkout_error']);
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'csrf') {
        $checkoutError = 'Security session expired (CSRF). Please try again.';
    } elseif ($_GET['error'] === 'missing_fields') {
        $checkoutError = 'Please fill out all required fields before proceeding.';
    }
}
?>
<?php if ($checkoutError): ?>
    <div class="alert alert-error">
        <p><?= htmlspecialchars($checkoutError, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
<?php endif; ?>

<form action="<?= url('/payment/pay.php') ?>" method="POST" class="checkout-form">
<?= csrf_field() ?>

<h3 style="font-size: var(--font-base); text-transform: uppercase; letter-spacing: 0.08em; color: var(--rs-cyan); margin-bottom: var(--space-2);">
    Billing & Shipping Information
</h3>

<label for="full_name">Full Name</label>
<input id="full_name" type="text" name="full_name" required placeholder="Full Name" class="field" value="<?= htmlspecialchars($_SESSION['cust_user_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="name">

<label for="email">Email Address (@gmail.com)</label>
<input id="email" type="email" name="email" required placeholder="you@gmail.com" class="field" autocomplete="email">

<label for="phone">Phone Number (10 digits)</label>
<input id="phone" type="tel" name="phone" required placeholder="9876543210" pattern="^[0-9]{10}$" title="Enter a valid 10-digit phone number" class="field" inputmode="tel" autocomplete="tel">

<label for="address">Delivery Street Address</label>
<textarea id="address" name="address" required placeholder="House/Flat No., Street, Landmark" class="field" rows="3" autocomplete="street-address"></textarea>

<div class="row">
    <div>
        <label for="city">City</label>
        <input id="city" type="text" name="city" required placeholder="City" class="field" autocomplete="address-level2">
    </div>
    <div>
        <label for="state">State</label>
        <input id="state" type="text" name="state" required placeholder="State" class="field" autocomplete="address-level1">
    </div>
</div>

<label for="pincode">Pincode (6 digits)</label>
<input id="pincode" type="text" name="pincode" required placeholder="400001" pattern="^[0-9]{6}$" title="Enter a valid 6-digit postal code" class="field" inputmode="numeric" autocomplete="postal-code">

<div class="checkout-summary">
    <div class="summary-row total">
        <span>Total Payable:</span>
        <span style="color: var(--rs-cyan);">₹<?= number_format($verifiedTotal, 2) ?></span>
    </div>
</div>

<button type="submit" class="btn primary checkout-btn">
    Proceed to Razorpay Payment <i class="fas fa-lock" style="margin-left: 8px;"></i>
</button>
</form>

<a href="<?= url('/cart/view.php') ?>" class="btn secondary back-btn">
    ← Back to Cart
</a>

</div>
</main>

<footer class="site-footer">
  <p>&copy; <?= date('Y') ?> THE RIVAL SOCIETY. All rights reserved.</p>
</footer>

</body>
</html>
