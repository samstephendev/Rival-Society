<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

/* BASIC SAFETY */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

/* 🔐 ALLOW ONLY @gmail.com */
if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
    die("Only @gmail.com email addresses are allowed.");
}

/* FETCH USER */
$stmt = $conn->prepare(
    "SELECT id, name, password FROM cust_user WHERE email = ? LIMIT 1"
);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {

    if (password_verify($password, $user['password'])) {

        /* LOGIN SESSION */
        $_SESSION['cust_user_id']   = $user['id'];
        $_SESSION['cust_user_name'] = $user['name'];

        /* REMEMBER ME */
        if ($remember) {
            $token = bin2hex(random_bytes(32));

            setcookie(
                "remember_token",
                $token,
                time() + (86400 * 30), // 30 days
                "/",
                "",
                false,
                true // HttpOnly
            );

            $update = $conn->prepare(
                "UPDATE cust_user SET remember_token = ? WHERE id = ?"
            );
            $update->bind_param("si", $token, $user['id']);
            $update->execute();
        }

        header("Location: dashboard.php");
        exit;
    }
}

/* FAILED LOGIN */
header("Location: login.php?error=invalid");
exit;
