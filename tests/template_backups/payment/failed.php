<?php
require_once __DIR__ . '/../config/session.php';
$error = htmlspecialchars($_GET['error'] ?? 'Payment was cancelled or could not be verified.', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Failed | Rival Society</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
<link rel="stylesheet" href="<?= url('/cart/style.css') ?>">
</head>
<body class="body">

<section class="checkout-section">
    <div class="checkout-card error" style="text-align: center;">
        <h1 style="color: #ff4c4c;">Payment Failed ❌</h1>
        <p style="color: #aaa; margin: 15px 0;"><?= $error ?></p>

        <div style="margin-top: 25px;">
            <a href="<?= url('/cart/view.php') ?>" class="btn primary" style="display: block; margin-bottom: 10px;">
                Return to Cart
            </a>
            <a href="<?= url('/page.php#store') ?>" class="btn secondary" style="display: block;">
                Browse Store
            </a>
        </div>
    </div>
</section>

</body>
</html>
