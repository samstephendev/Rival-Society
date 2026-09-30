<?php require_once __DIR__ . '/../config/session.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Rival Society</title>
<link rel="stylesheet" href="/rivalsociety/assets/account.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="account-card">
    <h2>Member Login</h2>

    <form action="login_process.php" method="POST">
        <input
    type="email"
    name="email"
    placeholder="Email Address"
    required
    pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$"
    title="Only @gmail.com emails are allowed"
>

        <input type="password" name="password" placeholder="Password" required>
        <label class="remember">
    <div class="remember-row">
    <label class="remember-label">
        <input type="checkbox" name="remember">
        <span class="rem-me">Remember me</span>
    </label>
</div>


        <button class="account-btn">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
    </form>

    <div class="account-links">
        <p>New here? <a href="register.php">Create account</a></p>
    </div>
</div>

</body>
</html>
        