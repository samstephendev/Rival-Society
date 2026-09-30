<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= url('/admin/style.css') ?>">
<title>Admin Dashboard | Rival Society</title>
</head>
<body>

<div class="container">
    <h1>Admin Dashboard</h1>
    <p style="color: #aaa;">Logged in as: <?= htmlspecialchars($_SESSION['admin_email'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></p>

    <ul>
        <li><a href="<?= url('/admin/add_product.php') ?>">Add Product</a></li>
        <li><a href="<?= url('/admin/edit_product.php') ?>">Edit Product</a></li>
        <li><a href="<?= url('/admin/delete_product.php') ?>">Delete Product</a></li>
        <li><a href="<?= url('/admin/change_password.php') ?>">Change Password</a></li>
        <li><a href="<?= url('/page.php#store') ?>" target="_blank">View Live Store</a></li>
        <li><a href="<?= url('/admin/logout.php') ?>">Logout</a></li>
    </ul>
</div>

</body>
</html>
