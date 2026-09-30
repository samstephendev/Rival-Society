<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$cartId = (int)$_POST['cart_id'];

$q = $conn->prepare("DELETE FROM cart WHERE id=?");
$q->bind_param("i", $cartId);
$q->execute();

header("Location: view.php");
exit;
