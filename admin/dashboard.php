<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php rs_critical_css(); ?>
<link rel="stylesheet" href="<?= asset_url('/assets/base.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/admin/style.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<title>Admin Dashboard | Rival Society</title>
</head>
<body>

<main class="container">
    <h1>Admin Dashboard</h1>
    <p style="color: var(--rs-text-secondary);">Logged in as: <strong><?= htmlspecialchars($_SESSION['admin_email'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></strong></p>

    <ul>
        <li><a href="<?= url('/admin/add_product.php') ?>"><i class="fas fa-plus" style="margin-right: 8px;"></i> Add Product</a></li>
        <li><a href="<?= url('/admin/edit_product.php') ?>"><i class="fas fa-pen-to-square" style="margin-right: 8px;"></i> Edit Products</a></li>
        <li><a href="<?= url('/admin/delete_product.php') ?>"><i class="fas fa-trash" style="margin-right: 8px;"></i> Delete Products</a></li>
        <li><a href="<?= url('/admin/change_password.php') ?>"><i class="fas fa-key" style="margin-right: 8px;"></i> Change Password</a></li>
        <li><a href="<?= url('/page.php#store') ?>" target="_blank" rel="noopener"><i class="fas fa-store" style="margin-right: 8px;"></i> View Live Store</a></li>
        <li><a href="<?= url('/admin/logout.php') ?>"><i class="fas fa-sign-out-alt" style="margin-right: 8px;"></i> Logout</a></li>
    </ul>
</main>

</body>
</html>
