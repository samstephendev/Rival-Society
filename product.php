<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
echo "<pre>SESSION ID: " . session_id() . "</pre>";

if (!isset($_GET['id'])) {
    die("Product not found");
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("Product unavailable");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($product['name']) ?> | Rival Society</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="/rivalsociety/assets/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

<section class="product-detail">
<div class="detail-container">

<!-- IMAGES -->
<div class="detail-images"><center>
    <img src="/rivalsociety/assets/images/<?= htmlspecialchars($product['image_front']) ?>" class="main-img">
    <img src="/rivalsociety/assets/images/<?= htmlspecialchars($product['image_back']) ?>" class="alt-img">
</center>
</div>

<!-- INFO -->
<div class="detail-info">
    <h1><?= htmlspecialchars($product['name']) ?></h1>
    <p class="price">₹<?= number_format($product['price'], 2) ?></p>

    <p class="stock in">
        <?= $product['stock'] > 0 ? 'In Stock' : 'Out of Stock' ?>
    </p>

    <?php if (isset($_SESSION['cust_user_id'])): ?>

    <form action="/rivalsociety/cart/add_to_cart.php" method="POST" class="product-form">

        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
        <input type="hidden" name="name" value="<?= htmlspecialchars($product['name']) ?>">
        <input type="hidden" name="price" value="<?= $product['price'] ?>">
        <input type="hidden" name="image" value="<?= $product['image_front'] ?>">

        <!-- SIZE -->
        <label class="option-label">Size</label>
        <div class="size-buttons">
            <?php foreach (['S','M','L','XL','XXL'] as $size): ?>
                <input type="radio" name="size" id="size-<?= $size ?>" value="<?= $size ?>" required>
                <label for="size-<?= $size ?>"><?= $size ?></label>
            <?php endforeach; ?>
        </div>

        <!-- QUANTITY -->
        <label for="quantity">Quantity</label>
        <input type="number" name="quantity" id="quantity" value="1" min="1" required>

        <!-- ADD -->
        <button type="submit" class="add-cart-btn">
            <i class="fas fa-cart-plus"></i> Add to Cart
        </button>

    </form>

    <?php else: ?>
        <a href="/rivalsociety/account/login.php" class="add-cart-btn">
            Login to Add to Cart
        </a>
    <?php endif; ?>

</div>
</div>
</section>

</body>
</html>
