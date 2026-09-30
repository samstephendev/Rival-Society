<?php
require_once __DIR__ . '/../config/session.php';
header("Location: " . url('/admin/dashboard.php'));
exit();
