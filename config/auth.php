<?php
require_once __DIR__ . '/session.php';

if (!isset($_SESSION['cust_user_id'])) {
    header("Location: /rivalsociety/account/login.php");
    exit;
}
