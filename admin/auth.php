<?php
require_once __DIR__ . '/../config/session.php';

if (!isset($_SESSION["admin_id"])) {
    header("Location: " . url('/admin/login.php'));
    exit;
}
