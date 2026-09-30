<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | Rival Society</title>
<?php rs_critical_css(); ?>
<link rel="stylesheet" href="<?= asset_url('/assets/base.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/assets/account.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>

<main class="account-page-wrapper">
  <div class="account-card dashboard">
      <h2>Welcome, <?= htmlspecialchars($_SESSION['cust_user_name'] ?? 'Member', ENT_QUOTES, 'UTF-8') ?></h2>
      <h3><?= htmlspecialchars($_SESSION['cust_user_email'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>

      <div class="dashboard-actions">
        <a href="<?= url('/cart/view.php') ?>" class="dash-btn">
            <i class="fas fa-shopping-cart"></i> View Cart
        </a>

        <a href="<?= url('/page.php#store') ?>" class="dash-btn">
            <i class="fas fa-store"></i> Continue Shopping
        </a>

        <a href="<?= url('/account/orders.php') ?>" class="dash-btn">
            <i class="fas fa-box"></i> My Orders
        </a>

        <a href="<?= url('/account/logout.php') ?>" class="dash-btn logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </div>
  </div>
</main>

</body>
</html>
