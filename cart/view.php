<?php
require_once __DIR__ . '/../config/session.php';
$cart = $_SESSION['cart'] ?? [];
echo "<pre>SESSION ID: " . session_id() . "</pre>";

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Your Cart | Rival Society</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="/rivalsociety/assets/style.css">
<link rel="stylesheet" href="/rivalsociety/cart/style.css">
</head>

<body>

<section class="cart-section">
<h1>Your Cart</h1>

<?php if (empty($cart)): ?>
    <p class="empty-cart">Your cart is empty.</p>
<?php else: ?>

<table class="cart-table">
<tr>
    <th>Product</th>
    <th>Size</th>
    <th>Qty</th>
    <th>Price</th>
    <th>Total</th>
</tr>

<?php $grandTotal = 0; ?>

<?php foreach ($cart as $key => $item): 
    $total = $item['price'] * $item['quantity'];
    $grandTotal += $total;
?>
<tr>
    <td>
        <img src="/rivalsociety/assets/images/<?= htmlspecialchars($item['image']) ?>" class="cart-img">
        <?= htmlspecialchars($item['name']) ?>
    </td>

    <td><?= htmlspecialchars($item['size']) ?></td>

    <td>
        <div class="qty-box">
            <button class="qty-btn minus" data-key="<?= $key ?>">−</button>
            <span class="qty-value"><?= $item['quantity'] ?></span>
            <button class="qty-btn plus" data-key="<?= $key ?>">+</button>
        </div>
    </td>

    <td>₹<?= number_format($item['price'], 2) ?></td>
    <td>₹<?= number_format($total, 2) ?></td>
</tr>
<?php endforeach; ?>

<tr class="cart-total">
    <td colspan="4"><strong>Grand Total</strong></td>
    <td><strong>₹<?= number_format($grandTotal, 2) ?></strong></td>
</tr>
</table>

<div class="cart-actions">
    <a href="/rivalsociety/page.php" class="btn">Continue Shopping</a>
    <a href="/rivalsociety/cart/checkout.php" class="btn primary">Checkout</a>
</div>

<?php endif; ?>
</section>

<script>
document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const key = btn.dataset.key;
        const action = btn.classList.contains('plus') ? 'plus' : 'minus';

        fetch('/rivalsociety/cart/update_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `key=${encodeURIComponent(key)}&action=${action}`
        }).then(() => location.reload());
    });
});
</script>

</body>
</html>
