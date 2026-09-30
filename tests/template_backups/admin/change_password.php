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
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= url('/admin/style.css') ?>">
<title>Change Password | Rival Society</title>
</head>
<body>

<div class="container">
    <h2>Change Admin Password</h2>

    <p><a href="<?= url('/admin/dashboard.php') ?>">← Back to Dashboard</a></p>

    <?php if ($message): ?>
        <p style="color: <?= $messageType === 'success' ? '#4cff4c' : '#ff4c4c' ?>;">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <?= csrf_field() ?>
        <input type="password" name="current_password" placeholder="Current Password" required>
        <input type="password" name="new_password" placeholder="New Password (min 4 characters)" minlength="4" required>
        <input type="password" name="confirm_password" placeholder="Confirm New Password" minlength="4" required>
        <button type="submit">Update Password</button>
    </form>
</div>

</body>
</html>
