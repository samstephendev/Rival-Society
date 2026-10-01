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
/**
 * Infer the URL prefix of this app from the current script.
 * /rivalsociety/page.php -> /rivalsociety
 * /page.php               -> '' (domain root)
 */
function detect_app_base_url(): string {
    if (PHP_SAPI === 'cli') {
        return '';
    }

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($scriptName === '' || $scriptName === '/') {
        return '';
    }

    $projectRoot = dirname(__DIR__);
    $rootReal = realpath($projectRoot);
    $rootReal = rtrim(str_replace('\\', '/', $rootReal !== false ? $rootReal : $projectRoot), '/');

    $scriptFile = (string) ($_SERVER['SCRIPT_FILENAME'] ?? '');
    $scriptReal = ($scriptFile !== '') ? realpath($scriptFile) : false;
    $scriptReal = str_replace('\\', '/', $scriptReal !== false ? $scriptReal : $scriptFile);

    if ($scriptReal !== '' && strncasecmp($scriptReal, $rootReal, strlen($rootReal)) === 0) {
        $inside = '/' . ltrim(str_replace('\\', '/', substr($scriptReal, strlen($rootReal))), '/');
        $len = strlen($inside);
        if ($len > 1 && strcasecmp(substr($scriptName, -$len), $inside) === 0) {
            return rtrim(substr($scriptName, 0, -$len), '/');
        }
    }

    foreach (['/admin/', '/account/', '/cart/', '/payment/', '/config/', '/database/', '/assets/', '/tests/'] as $marker) {
        $pos = stripos($scriptName, $marker);
        if ($pos !== false) {
            return rtrim(substr($scriptName, 0, $pos), '/');
        }
    }

    $dir = str_replace('\\', '/', dirname($scriptName));
    if ($dir === '/' || $dir === '\\' || $dir === '.') {
        return '';
    }
    return rtrim($dir, '/');
}

$envBase = getenv('BASE_URL');
$configBase = $config['app']['base_url'] ?? null;
if (is_string($envBase) && $envBase !== '') {
    $baseUrl = $envBase;
} elseif (is_string($configBase) && $configBase !== '') {
    $baseUrl = $configBase;
} else {
    $baseUrl = detect_app_base_url();
}

if (!defined('BASE_URL')) {
    define('BASE_URL', rtrim($baseUrl, '/'));
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_init();
    $useSsl = getenv('DB_SSL') == '1';
    if ($useSsl){
        $conn->ssl_set(null,null,null,null,null);
    }
    $conn->real_connect($host,$user,$pass,$db,$port,null,$useSsl ? MYSQLI_CLIENT_SSL : 0);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        throw $e;
    }
    http_response_code(500);
    die("Database service temporarily unavailable. Please try again later.");
}