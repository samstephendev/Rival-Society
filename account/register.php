<?php
require_once __DIR__ . '/../config/session.php';?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register | Rival Society</title>
<link rel="stylesheet" href="/rivalsociety/assets/account.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="account-card">
    <h2>Create Account</h2>

    <form action="register_process.php" method="POST">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>

        <button class="account-btn">
            <i class="fas fa-user-plus"></i> Register
        </button>
    </form>

    <div class="account-links">
        <p>Already a member? <a href="login.php">Login</a></p>
    </div>
</div>

</body>
</html>
