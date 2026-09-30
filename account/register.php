<?php
require_once __DIR__ . '/../config/session.php';

if (isset($_SESSION['cust_user_id'])) {
    header("Location: " . url('/account/dashboard.php'));
    exit;
}

$error = $_SESSION['register_error'] ?? null;
unset($_SESSION['register_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register | Rival Society</title>
<link rel="stylesheet" href="<?= url('/assets/account.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="account-card">
    <h2>Create Account</h2>

    <?php if ($error): ?>
        <div style="background: #4a1515; color: #ff6b6b; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; text-align: center;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form action="register_process.php" method="POST">
        <?= csrf_field() ?>
        <input type="text" name="name" placeholder="Full Name" required maxlength="100">
        <input
            type="email"
            name="email"
            placeholder="Email Address (@gmail.com)"
            required
            pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$"
            title="Only @gmail.com emails are allowed"
        >
        <input type="password" name="password" placeholder="Password" required minlength="4">

        <button class="account-btn" type="submit">
            <i class="fas fa-user-plus"></i> Register
        </button>
    </form>

    <div class="account-links">
        <p>Already a member? <a href="login.php">Login</a></p>
    </div>
</div>

</body>
</html>
