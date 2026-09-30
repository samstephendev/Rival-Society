<?php require_once __DIR__ . '/../config/auth.php'; 
require_once __DIR__ . '/../config/session.php';
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style.css">
<title>Dashboard</title>
</head>
<body>

<h1>Admin Dashboard</h1>

<ul>
    <li><a href="add_product.php">Add Product</a></li>
    <li><a href="edit_product.php">Edit Product</a></li>
    <li><a href="delete_product.php">Delete Product</a></li>
    <li><a href="change_password.php">Change Password</a></li>
    <li><a href="logout.php">Logout</a></li>
</ul>

</body>
</html>
