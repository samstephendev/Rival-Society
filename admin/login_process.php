<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

if (!csrf_verify()) {
    $_SESSION['admin_login_error'] = "Invalid session token. Please try again.";
    header("Location: login.php");
    exit;
}

// Rate limiting (max 5 failed attempts per 60 seconds)
$attempts = $_SESSION['admin_login_attempts'] ?? 0;
$lastTime = $_SESSION['admin_login_last_time'] ?? 0;
if ($attempts >= 5 && (time() - $lastTime) < 60) {
    $wait = 60 - (time() - $lastTime);
    $_SESSION['admin_login_error'] = "Too many failed attempts. Please wait {$wait} seconds.";
    header("Location: login.php");
    exit;
}

$email    = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

try {
    $stmt = $conn->prepare("SELECT id, email, password FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user["password"])) {
            unset($_SESSION['admin_login_attempts'], $_SESSION['admin_login_last_time']);
            session_regenerate_id(true);
            $_SESSION["admin_id"]    = (int)$user["id"];
            $_SESSION["admin_email"] = $user["email"];

            header("Location: " . url('/admin/dashboard.php'));
            exit;
        }
    }

    $_SESSION['admin_login_attempts'] = $attempts + 1;
    $_SESSION['admin_login_last_time'] = time();
    $_SESSION['admin_login_error'] = "Invalid admin email or password.";
    header("Location: login.php");
    exit;

} catch (mysqli_sql_exception $e) {
    error_log("Admin login error: " . $e->getMessage());
    $_SESSION['admin_login_error'] = "System error during authentication.";
    header("Location: login.php");
    exit;
}
