<?php
require_once __DIR__ . '/../config/session.php';

if (isset($_SESSION['cust_user_id'])) {
    $stmt = $conn->prepare(
        "UPDATE cust_user SET remember_token = NULL WHERE id = ?"
    );
    $stmt->bind_param("i", $_SESSION['cust_user_id']);
    $stmt->execute();
}

setcookie("remember_token", "", time() - 3600, "/");
session_destroy();

header("Location: ../account/login.php");
exit;
