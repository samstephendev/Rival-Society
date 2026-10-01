<?php
// CLI Database Installer for Rival Society
// Idempotent: Can be run multiple times safely.

if (php_sapi_name() !== 'cli') {
    die("This installer can only be run from the command line.\n");
}

echo "====================================================\n";
echo "   Rival Society: Database Installation & Seeding   \n";
echo "====================================================\n\n";

$configFile = __DIR__ . '/../config/config.local.php';
$config = [];
if (file_exists($configFile)) {
    $config = require $configFile;
}

$host = getenv('DB_HOST') ?: ($config['db']['host'] ?? '127.0.0.1');
$port = (int) (getenv('DB_PORT') ?: ($config['db']['port'] ?? 3306));
$user = getenv('DB_USER') ?: ($config['db']['user'] ?? 'root');
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($config['db']['pass'] ?? 'root');
$db   = getenv('DB_NAME') ?: ($config['db']['name'] ?? 'shop_db');

echo "[1/6] Connecting to MySQL server at {$host}:{$port} as user '{$user}'...\n";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try{
    $conn = mysqli_init();
    $useSsl = getenv('DB_SSL') === '1';
    if ($useSsl) {
        $conn->ssl_set(null, null, null, null, null);
    }
    $conn->real_connect($host, $user, $pass, null, $port, null, $useSsl ? MYSQLI_CLIENT_SSL : 0);
    $conn->set_charset("utf8mb4");
    echo " -> Connection established successsfully.\n";
} catch (mysqli_sql_exception $e) {
    die(" [FATAL ERROR] Could not connect to MySQL server: " . $e->getMessage() . "\n");
}

// 1. Create Database if not exists
echo "[2/6] Ensuring database '{$db}' exists...\n";
try {
    $conn->query("CREATE DATABASE IF NOT EXISTS `{$db}` DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci");
    $conn->select_db($db);
    echo "  -> Database '{$db}' is ready.\n";
} catch (mysqli_sql_exception $e) {
    die("  [FATAL ERROR] Failed to create or select database: " . $e->getMessage() . "\n");
}

// Helper to run multi-statement SQL files
function executeSqlFile(mysqli $conn, string $filepath, string $name): void {
    if (!file_exists($filepath)) {
        throw new RuntimeException("File not found: {$filepath}");
    }
    $sql = file_get_contents($filepath);
    
    // Remove comments
    $lines = explode("\n", $sql);
    $cleanLines = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
            continue;
        }
        $cleanLines[] = $line;
    }
    $cleanSql = implode("\n", $cleanLines);

    // Split queries by semicolon followed by newline or EOF
    $queries = array_filter(array_map('trim', preg_split('/;\s*[\r\n]+/', $cleanSql)));

    foreach ($queries as $query) {
        if ($query === '' || str_starts_with($query, 'USE ')) {
            continue;
        }
        try {
            $conn->query($query);
        } catch (mysqli_sql_exception $e) {
            echo "  [ERROR in {$name}] " . $e->getMessage() . "\nQuery: " . substr($query, 0, 100) . "...\n";
            throw $e;
        }
    }
}

// 2. Run schema.sql
echo "[3/6] Applying schema from database/schema.sql...\n";
try {
    executeSqlFile($conn, __DIR__ . '/schema.sql', 'schema.sql');
    
    // Idempotent column migrations for existing tables
    $colCheck = $conn->query("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = '{$db}' 
          AND TABLE_NAME = 'orders' 
          AND COLUMN_NAME = 'payment_method'
    ");
    if (!$colCheck || $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(20) NULL DEFAULT NULL AFTER razorpay_payment_id");
    }

    echo "  -> Schema created / verified successfully.\n";
} catch (Exception $e) {
    die("  [FATAL ERROR] Schema application failed: " . $e->getMessage() . "\n");
}

// 3. Run seed.sql
echo "[4/6] Seeding sample products from database/seed.sql...\n";
try {
    executeSqlFile($conn, __DIR__ . '/seed.sql', 'seed.sql');
    echo "  -> Seed products inserted / verified successfully.\n";
} catch (Exception $e) {
    die("  [FATAL ERROR] Seeding failed: " . $e->getMessage() . "\n");
}

// 4. Insert / Refresh Demo Accounts (Dynamic Runtime Hashing)
echo "[5/6] Creating / refreshing demo accounts (runtime password_hash)...\n";

// Demo Customer Account
$custName = "demo";
$custEmail = "demo@gmail.com";
$custPassPlain = "demo";
$custHash = password_hash($custPassPlain, PASSWORD_DEFAULT);

try {
    $custCheck = $conn->prepare("SELECT id, password FROM `cust_user` WHERE `email` = ?");
    $custCheck->bind_param("s", $custEmail);
    $custCheck->execute();
    $custRes = $custCheck->get_result();

    if ($row = $custRes->fetch_assoc()) {
        $update = $conn->prepare("UPDATE `cust_user` SET `name` = ?, `password` = ? WHERE `id` = ?");
        $update->bind_param("ssi", $custName, $custHash, $row['id']);
        $update->execute();
    } else {
        $insert = $conn->prepare("INSERT INTO `cust_user` (`name`, `email`, `password`) VALUES (?, ?, ?)");
        $insert->bind_param("sss", $custName, $custEmail, $custHash);
        $insert->execute();
    }

    // Verify
    $verifyCheck = $conn->prepare("SELECT `password` FROM `cust_user` WHERE `email` = ?");
    $verifyCheck->bind_param("s", $custEmail);
    $verifyCheck->execute();
    $dbHash = $verifyCheck->get_result()->fetch_assoc()['password'];
    $custPassResult = password_verify($custPassPlain, $dbHash) ? "PASS" : "FAIL";
    echo "  -> Customer [{$custEmail} / {$custPassPlain}]: {$custPassResult}\n";

} catch (mysqli_sql_exception $e) {
    die("  [FATAL ERROR] Customer setup failed: " . $e->getMessage() . "\n");
}

// Demo Admin Account (users table)
$adminEmail = "admin@gmail.com";
$adminPassPlain = "demo";
$adminHash = password_hash($adminPassPlain, PASSWORD_DEFAULT);

try {
    $adminCheck = $conn->prepare("SELECT id, password FROM `users` WHERE `email` = ?");
    $adminCheck->bind_param("s", $adminEmail);
    $adminCheck->execute();
    $adminRes = $adminCheck->get_result();

    if ($row = $adminRes->fetch_assoc()) {
        $update = $conn->prepare("UPDATE `users` SET `password` = ? WHERE `id` = ?");
        $update->bind_param("si", $adminHash, $row['id']);
        $update->execute();
    } else {
        $insert = $conn->prepare("INSERT INTO `users` (`email`, `password`) VALUES (?, ?)");
        $insert->bind_param("ss", $adminEmail, $adminHash);
        $insert->execute();
    }

    // Verify
    $verifyAdmin = $conn->prepare("SELECT `password` FROM `users` WHERE `email` = ?");
    $verifyAdmin->bind_param("s", $adminEmail);
    $verifyAdmin->execute();
    $dbAdminHash = $verifyAdmin->get_result()->fetch_assoc()['password'];
    $adminPassResult = password_verify($adminPassPlain, $dbAdminHash) ? "PASS" : "FAIL";
    echo "  -> Admin [{$adminEmail} / {$adminPassPlain}]: {$adminPassResult}\n";

} catch (mysqli_sql_exception $e) {
    die("  [FATAL ERROR] Admin setup failed: " . $e->getMessage() . "\n");
}

// 5. Print table list and row counts
echo "\n[6/6] Verifying database tables and row counts:\n";
echo "----------------------------------------------------\n";
echo sprintf("%-25s | %s\n", "Table Name", "Row Count");
echo "----------------------------------------------------\n";

$tablesRes = $conn->query("SHOW TABLES");
while ($tRow = $tablesRes->fetch_array()) {
    $tableName = $tRow[0];
    $countRes = $conn->query("SELECT COUNT(*) AS cnt FROM `{$tableName}`");
    $cnt = $countRes->fetch_assoc()['cnt'];
    echo sprintf("%-25s | %d\n", $tableName, $cnt);
}
echo "----------------------------------------------------\n";
echo "\n[SUCCESS] Installation completed successfully!\n";
