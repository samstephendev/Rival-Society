<?php
// Database & Application Connection Configuration

$configFile = __DIR__ . '/config.local.php';
$config = [];
if (file_exists($configFile)) {
    $config = require $configFile;
}

$host = getenv('DB_HOST') ?: ($config['db']['host'] ?? '127.0.0.1');
$port = (int) (getenv('DB_PORT') ?: ($config['db']['port'] ?? 3306));
$user = getenv('DB_USER') ?: ($config['db']['user'] ?? 'root');
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($config['db']['pass'] ?? 'root');
$db   = getenv('DB_NAME') ?: ($config['db']['name'] ?? 'shop_db');
$baseUrl = getenv('BASE_URL') ?: ($config['app']['base_url'] ?? '/rivalsociety');

if (!defined('BASE_URL')) {
    define('BASE_URL', rtrim($baseUrl, '/'));
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        throw $e;
    }
    http_response_code(500);
    die("Database service temporarily unavailable. Please try again later.");
}