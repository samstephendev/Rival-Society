<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$cart = $_SESSION['cart'] ?? [];
$validatedCart = [];
$grandTotal = 0.0;

// Re-verify all cart items against the database to ensure accurate prices and stock
if (!empty($cart)) {
    foreach ($cart as $key => $item) {
        $productId = (int)($item['id'] ?? 0);
        $size = htmlspecialchars($item['size'] ?? '', ENT_QUOTES, 'UTF-8');
        $qty = max(1, (int)($item['quantity'] ?? 1));

        try {
            $stmt = $conn->prepare("SELECT id, name, price, image_front, stock, status FROM products WHERE id = ?");
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $dbProduct = $stmt->get_result()->fetch_assoc();

            if ($dbProduct && $dbProduct['status'] === 'active' && (int)$dbProduct['stock'] > 0) {
                $stock = (int)$dbProduct['stock'];
                $actualQty = min($qty, $stock);
                $unitPrice = (float)$dbProduct['price'];

                $validatedCart[$key] = [
                    'id'       => $productId,
                    'name'     => $dbProduct['name'],
                    'price'    => $unitPrice,
                    'image'    => $dbProduct['image_front'],
                    'size'     => $size,
                    'quantity' => $actualQty,
                    'stock'    => $stock
                ];
                $grandTotal += $unitPrice * $actualQty;
            }
        } catch (mysqli_sql_exception $e) {
            error_log("Cart validation error: " . $e->getMessage());
        }
    }
    $_SESSION['cart'] = $validatedCart;
    $cart = $validatedCart;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Your Cart | Rival Society</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
<link rel="stylesheet" href="<?= url('/cart/style.css') ?>">
<style>
.remove-btn {
    background: transparent;
    border: none;
    color: #ff4c4c;
    cursor: pointer;
    font-size: 13px;
    margin-left: 10px;
    padding: 0;
}
.remove-btn:hover {
    text-decoration: underline;
}
</style>
</head>
<body>

<section class="cart-section">
<h1>Your Cart</h1>

<?php if (empty($cart)): ?>
    <p class="empty-cart">Your cart is empty.</p>
    <div class="cart-actions" style="margin-top: 20px;">
        <a href="<?= url('/page.php#store') ?>" class="btn">Browse Store</a>
    </div>
<?php else: ?>

<table class="cart-table">
<thead>
<tr>
    <th>Product</th>
    <th>Size</th>
    <th>Qty</th>
    <th>Price</th>
    <th>Total</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($cart as $key => $item): 
    $total = (float)$item['price'] * (int)$item['quantity'];
?>
<tr>
    <td>
        <img src="<?= url('/assets/images/' . htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8')) ?>" class="cart-img" alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>
    </td>

    <td><?= htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8') ?></td>

    <td>
        <div class="qty-box">
            <button type="button" class="qty-btn minus" data-key="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" aria-label="Decrease quantity">−</button>
            <span class="qty-value"><?= (int)$item['quantity'] ?></span>
            <button type="button" class="qty-btn plus" data-key="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" aria-label="Increase quantity">+</button>
        </div>
    </td>

    <td>₹<?= number_format((float)$item['price'], 2) ?></td>
    <td>₹<?= number_format($total, 2) ?></td>
    <td>
        <form action="<?= url('/cart/remove_from_cart.php') ?>" method="POST" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="cart_key" value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="remove-btn" onclick="return confirm('Remove this item?');">Remove</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>

<tr class="cart-total">
    <td colspan="4"><strong>Grand Total</strong></td>
    <td colspan="2"><strong>₹<?= number_format($grandTotal, 2) ?></strong></td>
</tr>
</tbody>
</table>

<div class="cart-actions">
    <a href="<?= url('/page.php#store') ?>" class="btn">Continue Shopping</a>
    <a href="<?= url('/cart/checkout.php') ?>" class="btn primary">Checkout</a>
</div>

<?php endif; ?>
</section>

<script>
const CSRF_TOKEN = "<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>";
const UPDATE_URL = "<?= url('/cart/update_cart.php') ?>";

document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const key = btn.dataset.key;
        const action = btn.classList.contains('plus') ? 'plus' : 'minus';

        fetch(UPDATE_URL, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `key=${encodeURIComponent(key)}&action=${action}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.success) {
                location.reload();
            } else if (data && data.message) {
                alert(data.message);
            } else {
                location.reload();
            }
        })
        .catch(() => location.reload());
    });
});
</script>

</body>
</html>
