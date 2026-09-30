<?php
require_once __DIR__ . '/../config/session.php';

unset($_SESSION["admin_id"], $_SESSION["admin_email"]);
session_regenerate_id(true);

header("Location: " . url('/admin/login.php'));
exit;
