<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new = password_hash($_POST["new_password"], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->bind_param("si", $new, $_SESSION["admin_id"]);
    $stmt->execute();
    echo "Password Updated";
}
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style.css">
<title>Change Password</title>
</head>
<body>

<h2>Change Password</h2>

<form method="POST">
    <input type="password" name="new_password" placeholder="New Password" required>
    <button>Change</button>
</form>

</body>
</html>
