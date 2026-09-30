<?php
require_once __DIR__ . '/../config/session.php';

if (isset($_SESSION["admin_id"])) {
    header("Location: " . url('/admin/dashboard.php'));
    exit;
}

$error = $_SESSION['admin_login_error'] ?? null;
unset($_SESSION['admin_login_error']);
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
<script src="<?= asset_url('/assets/password-toggle.js') ?>" defer></script>
<title>Admin Login | Rival Society</title>
</head>
<body>

<main class="container" style="max-width: 440px;">
    <h2>Admin Login</h2>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login_process.php">
        <?= csrf_field() ?>
        <label for="admin-email">Admin Email</label>
        <input id="admin-email" type="email" name="email" placeholder="admin@gmail.com" required autocomplete="email">

        <label for="admin-pass">Password</label>
        <input id="admin-pass" type="password" name="password" placeholder="Admin password" required autocomplete="current-password">

        <button type="submit" class="btn primary">
            <i class="fas fa-lock" style="margin-right: 8px;"></i> Login to Admin
        </button>
    </form>

    <p style="text-align: center; margin-top: 24px;">
        <a href="<?= url('/page.php') ?>" class="back-link">← Return to Store</a>
    </p>
</main>

</body>
</html>
