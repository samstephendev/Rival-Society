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
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= url('/admin/style.css') ?>">
<title>Admin Login | Rival Society</title>
</head>
<body>

<div class="container">
    <h2>Admin Login</h2>

    <?php if ($error): ?>
        <p style="color: #ff4c4c;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="POST" action="login_process.php">
        <?= csrf_field() ?>
        <input type="email" name="email" placeholder="Admin Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
</div>

</body>
</html>
