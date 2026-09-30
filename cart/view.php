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
<?php rs_critical_css(); ?>
<link rel="stylesheet" href="<?= asset_url('/assets/base.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/assets/style.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/cart/style.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="<?= asset_url('/assets/toast.js') ?>"></script>
</head>
<body>

<main class="cart-section">
<h1>Your Shopping Cart</h1>

<?php if (empty($cart)): ?>
    <div class="empty-cart">
        <p>Your shopping cart is currently empty.</p>
        <div class="cart-actions" style="margin-top: 24px; justify-content: center;">
            <a href="<?= url('/page.php#store') ?>" class="btn primary">Explore The Store</a>
        </div>
    </div>
<?php else: ?>

<table class="cart-table" aria-label="Shopping Cart Items">
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
        <a href="<?= url('/product.php?id=' . (int)$item['id']) ?>" class="cart-product-link" style="display: inline-flex; align-items: center; gap: 14px; text-decoration: none; color: inherit;" aria-label="View <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> details">
            <img src="<?= url('/assets/images/' . htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8')) ?>" class="cart-img" alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" width="72" height="72">
            <strong><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></strong>
        </a>
    </td>

    <td><?= htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8') ?></td>

    <td>
        <div class="qty-box" role="group" aria-label="Adjust Item Quantity">
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
            <button type="submit" class="remove-btn" onclick="return confirm('Remove this item?');" aria-label="Remove item from cart">
                <i class="fas fa-trash-can"></i> Remove
            </button>
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
    <a href="<?= url('/page.php#store') ?>" class="btn secondary">
        <i class="fas fa-arrow-left"></i> Continue Shopping
    </a>
    <a href="<?= url('/cart/checkout.php') ?>" class="btn primary">
        Proceed to Checkout <i class="fas fa-arrow-right"></i>
    </a>
</div>

<?php endif; ?>
</main>

<footer class="site-footer">
  <p>&copy; <?= date('Y') ?> THE RIVAL SOCIETY. All rights reserved.</p>
</footer>

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

<?php if (($_GET['msg'] ?? '') === 'added'): ?>
document.addEventListener('DOMContentLoaded', () => {
    if (window.showToast) {
        window.showToast('Item added to cart successfully', 'success');
    }
});
<?php endif; ?>
</script>

</body>
</html>
