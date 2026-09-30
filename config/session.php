<?php
// Session configuration, security headers, cookie parameters, and CSRF protection

require_once __DIR__ . '/db.php';

// Helper to determine if current request is over HTTPS
function is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 4443) {
        return true;
    }
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    return false;
}

// Helper to generate full URLs using BASE_URL
function url(string $path = ''): string {
    $base = defined('BASE_URL') ? BASE_URL : '';
    if ($path === '' || $path === '/') {
        return $base === '' ? '/' : $base . '/';
    }
    return ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
}

/** Site-relative URL with filemtime cache-busting for local files. */
function asset_url(string $path): string {
    $path = ltrim($path, '/');
    $query = '';
    $qpos = strpos($path, '?');
    if ($qpos !== false) {
        $query = substr($path, $qpos + 1);
        $path = substr($path, 0, $qpos);
    }

    $fs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    $url = url('/' . $path);
    $params = [];
    if ($query !== '') {
        parse_str($query, $params);
    }
    if (is_file($fs)) {
        $params['v'] = (string) filemtime($fs);
    }
    if ($params) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

/** Minimal inline styles so a missing stylesheet never leaves a blank unstyled page. */
function rs_critical_css(): void {
    echo '<style id="rs-critical">html{color-scheme:dark}body{margin:0;background:#09090b;color:#fff;font-family:Montserrat,system-ui,sans-serif;line-height:1.6}img{max-width:100%;height:auto}</style>';
}

// Start session with secure cookie parameters
if (session_status() === PHP_SESSION_NONE) {
    $secureCookie = is_https();
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// CSRF Functions
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function csrf_verify(?string $token = null): bool {
    $submittedToken = $token ?? ($_POST['csrf_token'] ?? '');
    if (empty($submittedToken) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $submittedToken);
}

// Auto Login via SHA-256 Hashed Remember Token
if (!isset($_SESSION['cust_user_id']) && !empty($_COOKIE['remember_token'])) {
    $tokenHash = hash('sha256', $_COOKIE['remember_token']);
    try {
        $stmt = $conn->prepare("SELECT id, name FROM cust_user WHERE remember_token = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $tokenHash);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($user = $res->fetch_assoc()) {
                session_regenerate_id(true);
                $_SESSION['cust_user_id']   = (int)$user['id'];
                $_SESSION['cust_user_name'] = $user['name'];
            }
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Auto-login error: " . $e->getMessage());
    }
}
