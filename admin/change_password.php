<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify()) {
        $message = "Invalid CSRF token.";
        $messageType = "error";
    } else {
        $adminId     = (int)$_SESSION["admin_id"];
        $currentPass = $_POST["current_password"] ?? "";
        $newPass     = $_POST["new_password"] ?? "";
        $confirmPass = $_POST["confirm_password"] ?? "";

        if (strlen($newPass) < 4) {
            $message = "New password must be at least 4 characters long.";
            $messageType = "error";
        } elseif ($newPass !== $confirmPass) {
            $message = "New password and confirmation do not match.";
            $messageType = "error";
        } else {
            try {
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->bind_param("i", $adminId);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($row = $res->fetch_assoc()) {
                    if (password_verify($currentPass, $row["password"])) {
                        $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $update->bind_param("si", $newHash, $adminId);
                        $update->execute();

                        $message = "Password updated successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Current password is incorrect.";
                        $messageType = "error";
                    }
                } else {
                    $message = "Admin user not found.";
                    $messageType = "error";
                }
            } catch (mysqli_sql_exception $e) {
                error_log("Change password error: " . $e->getMessage());
                $message = "Database error occurred while updating password.";
                $messageType = "error";
            }
        }
    }
}
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
<title>Change Password | Rival Society</title>
</head>
<body>

<main class="container" style="max-width: 520px;">
    <h2>Change Admin Password</h2>

    <p><a href="<?= url('/admin/dashboard.php') ?>" class="back-link">← Back to Dashboard</a></p>

    <?php if ($message): ?>
        <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <?= csrf_field() ?>
        <label for="cur-pass">Current Password</label>
        <input id="cur-pass" type="password" name="current_password" placeholder="Enter current password" required autocomplete="current-password">

        <label for="new-pass">New Password (minimum 4 characters)</label>
        <input id="new-pass" type="password" name="new_password" placeholder="Enter new password" minlength="4" required autocomplete="new-password">

        <label for="conf-pass">Confirm New Password</label>
        <input id="conf-pass" type="password" name="confirm_password" placeholder="Confirm new password" minlength="4" required autocomplete="new-password">

        <button type="submit" class="btn primary">
            <i class="fas fa-key" style="margin-right: 8px;"></i> Update Admin Password
        </button>
    </form>
</main>

</body>
</html>
