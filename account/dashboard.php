<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/session.php';
if (!isset($_SESSION['cust_user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | Rival Society</title>
<link rel="stylesheet" href="/rivalsociety/assets/account.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="account-card dashboard">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['cust_user_name']) ?></h2>

    <a href="../cart/view.php" class="dash-btn">
        <i class="fas fa-shopping-cart"></i> View Cart
    </a>

    <a href="../page.php#store" class="dash-btn">
        <i class="fas fa-store"></i> Continue Shopping
    </a>

    <a href="orders.php" class="dash-btn">
        <i class="fas fa-box"></i> My Orders

    <a href="logout.php" class="dash-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

</body>
</html>
