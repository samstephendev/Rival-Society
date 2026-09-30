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
<?php rs_critical_css(); ?>
<link rel="stylesheet" href="<?= asset_url('/assets/base.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/assets/style.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/cart/style.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body class="body">

<main class="checkout-section">
    <div class="checkout-card error" style="text-align: center;">
        <h1 style="color: var(--rs-failed);">Payment Failed</h1>
        <div class="alert alert-error" style="margin: 20px 0;">
            <p><?= $error ?></p>
        </div>

        <div style="margin-top: 25px; display: flex; flex-direction: column; gap: 12px;">
            <a href="<?= url('/cart/view.php') ?>" class="btn primary">
                <i class="fas fa-shopping-cart" style="margin-right: 8px;"></i> Return to Cart
            </a>
            <a href="<?= url('/page.php#store') ?>" class="btn secondary">
                <i class="fas fa-store" style="margin-right: 8px;"></i> Browse Store
            </a>
        </div>
    </div>
</main>

<footer class="site-footer">
  <p>&copy; <?= date('Y') ?> THE RIVAL SOCIETY. All rights reserved.</p>
</footer>

</body>
</html>
