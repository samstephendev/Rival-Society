<?php
require_once __DIR__ . '/../config/session.php';
?>

<!DOCTYPE html>
<html>
<head>
<title>Payment Failed | Rival Society</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="/rivalsociety/cart/style.css">
</head>

<body class="body">

<section class="checkout-section">
    <div class="checkout-card error">
        <h1>Payment Failed ❌</h1>

        <p>Something went wrong. Please try again.</p>

        <a href="/rivalsociety/cart/view.php" class="btn primary">
            Return to Cart
        </a>
    </div>
</section>

</body>
</html>
