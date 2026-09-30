<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO cust_user (name, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $password);

if ($stmt->execute()) {
    $_SESSION['cust_user_id'] = $stmt->insert_id;
    $_SESSION['cust_user_name'] = $name;
    header("Location: ../account/dashboard.php");
    exit;
} else {
    echo "Email already registered.";
}
