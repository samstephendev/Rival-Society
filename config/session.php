<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/* AUTO LOGIN */
if (!isset($_SESSION['cust_user_id']) && isset($_COOKIE['remember_token'])) {

    $stmt = $conn->prepare(
        "SELECT id, name FROM cust_user WHERE remember_token = ?"
    );

    if ($stmt) {
        $stmt->bind_param("s", $_COOKIE['remember_token']);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($user = $res->fetch_assoc()) {
            $_SESSION['cust_user_id']   = $user['id'];
            $_SESSION['cust_user_name'] = $user['name'];
        }
    }
}
