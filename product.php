<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';

if (!isset($_GET['id'])) {
    header("Location: " . url('/page.php#store'));
    exit;
}

$id = (int)$_GET['id'];
$product = null;

try {
    $stmt = $conn->prepare("SELECT id, name, price, image_front, image_back, stock, status FROM products WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
} catch (mysqli_sql_exception $e) {
    error_log("Product query error: " . $e->getMessage());
}

if (!$product) {
    http_response_code(404);
    die("Product unavailable. <a href='" . url('/page.php#store') . "'>Back to Store</a>");
}

$msgCode = $_GET['msg'] ?? '';
$serverMessages = [
    'size'  => ['text' => 'Please select a size', 'type' => 'error'],
    'stock' => ['text' => 'Selected product is currently out of stock', 'type' => 'error'],
    'qty'   => ['text' => 'Please choose a valid quantity', 'type' => 'error'],
    'csrf'  => ['text' => 'Session expired. Please try again', 'type' => 'error'],
    'added' => ['text' => 'Added to cart successfully', 'type' => 'success'],
];
$activeNotice = $serverMessages[$msgCode] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> | Rival Society</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php rs_critical_css(); ?>
<link rel="stylesheet" href="<?= asset_url('/assets/base.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/assets/style.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="<?= asset_url('/assets/toast.js') ?>"></script>
</head>
<body>

<section class="product-detail">
<div class="detail-container">

<!-- IMAGES -->
<div class="detail-images">
  <img src="<?= url('/assets/images/' . htmlspecialchars($product['image_front'], ENT_QUOTES, 'UTF-8')) ?>" class="main-img" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> - Front View" width="600" height="600">
  <img src="<?= url('/assets/images/' . htmlspecialchars($product['image_back'], ENT_QUOTES, 'UTF-8')) ?>" class="alt-img" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> - Back View" width="600" height="600">
</div>

<!-- INFO -->
<div class="detail-info">
    <h1><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="price">₹<?= number_format((float)$product['price'], 2) ?></p>

    <p class="stock <?= (int)$product['stock'] > 0 ? 'in' : 'out' ?>">
        <?= (int)$product['stock'] > 0 ? 'In Stock (' . (int)$product['stock'] . ' available)' : 'Out of Stock' ?>
    </p>

    <?php if (isset($_SESSION['cust_user_id'])): ?>
      <?php if ((int)$product['stock'] > 0): ?>
        <form action="<?= url('/cart/add_to_cart.php') ?>" method="POST" class="product-form" id="addToCartForm" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

            <!-- SIZE -->
            <label class="option-label">Select Size</label>
            <div class="size-buttons" role="radiogroup" aria-label="Select Apparel Size">
                <?php foreach (['S', 'M', 'L', 'XL', 'XXL'] as $size): ?>
                    <input type="radio" name="size" id="size-<?= $size ?>" value="<?= $size ?>" required>
                    <label for="size-<?= $size ?>"><?= $size ?></label>
                <?php endforeach; ?>
            </div>

            <!-- QUANTITY -->
            <label for="quantity">Quantity</label>
            <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= (int)$product['stock'] ?>" required>

            <!-- ADD -->
            <button type="submit" class="add-cart-btn btn primary">
                <i class="fas fa-cart-plus"></i> Add to Cart
            </button>
        </form>
      <?php else: ?>
        <div class="alert alert-error">
          <p>Currently out of stock. Please check back later!</p>
        </div>
      <?php endif; ?>
    <?php else: ?>
        <a href="<?= url('/account/login.php') ?>" class="add-cart-btn btn primary">
            <i class="fas fa-arrow-right-to-bracket"></i> Login to Add to Cart
        </a>
    <?php endif; ?>

    <p style="margin-top: 24px;">
        <a href="<?= url('/page.php#store') ?>" class="back-link">← Back to Store</a>
    </p>
</div>

</div>
</section>

<footer class="site-footer">
  <p>&copy; <?= date('Y') ?> THE RIVAL SOCIETY. All rights reserved.</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Show active server message if present in query string
    <?php if ($activeNotice): ?>
    if (window.showToast) {
        window.showToast(<?= json_encode($activeNotice['text'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, <?= json_encode($activeNotice['type']) ?>);
    }
    <?php endif; ?>

    const form = document.getElementById('addToCartForm');
    if (!form) return;

    const sizeGroup = form.querySelector('.size-buttons');
    const sizeRadios = form.querySelectorAll('input[name="size"]');

    sizeRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            if (sizeGroup) {
                sizeGroup.classList.remove('size-highlight');
            }
        });
    });

    form.addEventListener('submit', (e) => {
        let sizeSelected = false;
        sizeRadios.forEach(r => {
            if (r.checked) sizeSelected = true;
        });

        if (!sizeSelected) {
            e.preventDefault();
            if (window.showToast) {
                window.showToast("Please select a size", "error");
            }
            if (sizeGroup) {
                sizeGroup.classList.remove('size-highlight');
                // trigger reflow for re-animation
                void sizeGroup.offsetWidth;
                sizeGroup.classList.add('size-highlight');
            }
            const firstRadio = document.getElementById('size-S');
            if (firstRadio) {
                firstRadio.focus();
            }
            return false;
        }
    });
});
</script>

</body>
</html>
