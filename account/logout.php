<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['cust_user_id'])) {
    try {
        $stmt = $conn->prepare("UPDATE cust_user SET remember_token = NULL WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['cust_user_id']);
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        error_log("Logout error: " . $e->getMessage());
    }
}

setcookie(
    "remember_token",
    "",
    [
        'expires'  => time() - 3600,
        'path'     => '/',
        'domain'   => '',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax'
    ]
);

$_SESSION = [];
if (session_id() !== '') {
    session_destroy();
}

header("Location: " . url('/account/login.php'));
exit;
