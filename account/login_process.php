<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

if (!csrf_verify()) {
    $_SESSION['login_error'] = "Invalid session token. Please try again.";
    header("Location: login.php");
    exit;
}

// Rate limiting (max 5 failed attempts per 60 seconds)
$attempts = $_SESSION['cust_login_attempts'] ?? 0;
$lastAttemptTime = $_SESSION['cust_login_last_time'] ?? 0;
if ($attempts >= 5 && (time() - $lastAttemptTime) < 60) {
    $waitSecs = 60 - (time() - $lastAttemptTime);
    $_SESSION['login_error'] = "Too many failed attempts. Please wait {$waitSecs} seconds.";
    header("Location: login.php");
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = !empty($_POST['remember']);

if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
    $_SESSION['login_error'] = "Only @gmail.com email addresses are allowed.";
    header("Location: login.php");
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id, name, password FROM cust_user WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            // Login successful: reset rate limiter
            unset($_SESSION['cust_login_attempts'], $_SESSION['cust_login_last_time']);

            session_regenerate_id(true);
            $_SESSION['cust_user_id']   = (int)$user['id'];
            $_SESSION['cust_user_name'] = $user['name'];

            if ($remember) {
                $rawToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $rawToken);

                $update = $conn->prepare("UPDATE cust_user SET remember_token = ? WHERE id = ?");
                $update->bind_param("si", $tokenHash, $user['id']);
                $update->execute();

                setcookie(
                    "remember_token",
                    $rawToken,
                    [
                        'expires'  => time() + (86400 * 30),
                        'path'     => '/',
                        'domain'   => '',
                        'secure'   => is_https(),
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]
                );
            }

            header("Location: " . url('/account/dashboard.php'));
            exit;
        }
    }

    // Login failed
    $_SESSION['cust_login_attempts'] = $attempts + 1;
    $_SESSION['cust_login_last_time'] = time();
    $_SESSION['login_error'] = "Invalid email or password.";
    header("Location: login.php");
    exit;

} catch (mysqli_sql_exception $e) {
    error_log("Login error: " . $e->getMessage());
    $_SESSION['login_error'] = "A system error occurred. Please try again later.";
    header("Location: login.php");
    exit;
}
