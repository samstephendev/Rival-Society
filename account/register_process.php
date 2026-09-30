<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit;
}

if (!csrf_verify()) {
    $_SESSION['register_error'] = "Invalid session token. Please try again.";
    header("Location: register.php");
    exit;
}

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($name === '' || $email === '' || $password === '') {
    $_SESSION['register_error'] = "Please fill in all required fields.";
    header("Location: register.php");
    exit;
}

if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
    $_SESSION['register_error'] = "Only @gmail.com email addresses are allowed.";
    header("Location: register.php");
    exit;
}

if (strlen($password) < 4) {
    $_SESSION['register_error'] = "Password must be at least 4 characters.";
    header("Location: register.php");
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $conn->prepare("INSERT INTO cust_user (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hashedPassword);
    $stmt->execute();

    session_regenerate_id(true);
    $_SESSION['cust_user_id']   = (int)$stmt->insert_id;
    $_SESSION['cust_user_name'] = $name;

    header("Location: " . url('/account/dashboard.php'));
    exit;
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        $_SESSION['register_error'] = "This email is already registered. Please login.";
    } else {
        error_log("Registration error: " . $e->getMessage());
        $_SESSION['register_error'] = "Registration failed due to a system error. Please try again.";
    }
    header("Location: register.php");
    exit;
}
