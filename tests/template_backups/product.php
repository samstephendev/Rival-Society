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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> | Rival Society</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<section class="product-detail">
<div class="detail-container">

<!-- IMAGES -->
<div class="detail-images">
  <center>
    <img src="<?= url('/assets/images/' . htmlspecialchars($product['image_front'], ENT_QUOTES, 'UTF-8')) ?>" class="main-img" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
    <img src="<?= url('/assets/images/' . htmlspecialchars($product['image_back'], ENT_QUOTES, 'UTF-8')) ?>" class="alt-img" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
  </center>
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
        <form action="<?= url('/cart/add_to_cart.php') ?>" method="POST" class="product-form">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

            <!-- SIZE -->
            <label class="option-label">Size</label>
            <div class="size-buttons">
                <?php foreach (['S', 'M', 'L', 'XL', 'XXL'] as $size): ?>
                    <input type="radio" name="size" id="size-<?= $size ?>" value="<?= $size ?>" required>
                    <label for="size-<?= $size ?>"><?= $size ?></label>
                <?php endforeach; ?>
            </div>

            <!-- QUANTITY -->
            <label for="quantity">Quantity</label>
            <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= (int)$product['stock'] ?>" required>

            <!-- ADD -->
            <button type="submit" class="add-cart-btn">
                <i class="fas fa-cart-plus"></i> Add to Cart
            </button>
        </form>
      <?php else: ?>
        <p style="color: #ff6b6b; font-weight: 600;">Currently out of stock. Please check back later!</p>
      <?php endif; ?>
    <?php else: ?>
        <a href="<?= url('/account/login.php') ?>" class="add-cart-btn" style="display: inline-block; text-align: center; text-decoration: none;">
            Login to Add to Cart
        </a>
    <?php endif; ?>

    <p style="margin-top: 20px;">
        <a href="<?= url('/page.php#store') ?>" style="color: #aaa; text-decoration: none;">← Back to Store</a>
    </p>
</div>

</div>
</section>

</body>
</html>
