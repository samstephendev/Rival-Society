<?php
require_once __DIR__ . '/../config/session.php';
if (isset($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style.css">
<title>Admin Login</title>
</head>
<body>

<h2>Admin Login</h2>

<form method="POST" action="login_process.php">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
</form>

</body>
</html>
