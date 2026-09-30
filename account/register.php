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
<title>Create Account | Rival Society</title>
<link rel="stylesheet" href="<?= url('/assets/base.css') ?>">
<link rel="stylesheet" href="<?= url('/assets/account.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>

<main class="account-page-wrapper">
  <div class="account-card">
      <h2>Create Account</h2>

      <?php if ($error): ?>
          <div class="alert alert-error">
              <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
          </div>
      <?php endif; ?>

      <form action="register_process.php" method="POST">
          <?= csrf_field() ?>
          <label for="reg-name">Full Name</label>
          <input id="reg-name" type="text" name="name" placeholder="John Doe" required maxlength="100" autocomplete="name">

          <label for="reg-email">Email Address</label>
          <input
              id="reg-email"
              type="email"
              name="email"
              placeholder="you@gmail.com"
              required
              pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$"
              title="Only @gmail.com emails are allowed"
              autocomplete="email"
          >

          <label for="reg-password">Password (minimum 4 characters)</label>
          <input id="reg-password" type="password" name="password" placeholder="Create a secure password" required minlength="4" autocomplete="new-password">

          <button class="account-btn btn primary" type="submit">
              <i class="fas fa-user-plus"></i> Register
          </button>
      </form>

      <div class="account-links">
          <p>Already a member? <a href="login.php">Login here</a></p>
          <p style="margin-top: 12px;"><a href="<?= url('/page.php') ?>">← Return to Store</a></p>
      </div>
  </div>
</main>

</body>
</html>
