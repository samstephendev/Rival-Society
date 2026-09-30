<?php
// CLI Script: Create or update an admin user in `users` table
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once __DIR__ . '/../config/db.php';

echo "=== Rival Society: Create Admin Account ===\n";

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;

if (!$email) {
    echo "Enter Admin Email: ";
    $email = trim(fgets(STDIN));
}

if (!$password) {
    echo "Enter Admin Password: ";
    $password = trim(fgets(STDIN));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Error: Invalid email format.\n");
}

if (strlen($password) < 4) {
    die("Error: Password must be at least 4 characters.\n");
}

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if user already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $res = $checkStmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->bind_param("si", $hash, $row['id']);
        $updateStmt->execute();
        echo "[SUCCESS] Updated existing admin user: {$email}\n";
    } else {
        $insertStmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $insertStmt->bind_param("ss", $email, $hash);
        $insertStmt->execute();
        echo "[SUCCESS] Created new admin user: {$email} (ID: {$insertStmt->insert_id})\n";
    }
} catch (mysqli_sql_exception $e) {
    die("[FAIL] Database error: " . $e->getMessage() . "\n");
}
