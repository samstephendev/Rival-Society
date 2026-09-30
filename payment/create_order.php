<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Razorpay\Api\Api;

$orderId = $_SESSION['last_order_id'] ?? null;

if (!$orderId) {
    header("Location: /rivalsociety/cart/view.php");
    exit;
}

/* FETCH ORDER */
$stmt = $conn->prepare("SELECT total FROM orders WHERE id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found");
}

$api = new Api($_ENV['RAZORPAY_KEY_ID'], $_ENV['RAZORPAY_KEY_SECRET']);

$razorpayOrder = $api->order->create([
    'amount' => $order['total'] * 100, // in paise
    'currency' => 'INR',
    'receipt' => 'order_' . $orderId
]);

/* SAVE RAZORPAY ORDER ID */
$update = $conn->prepare("
    UPDATE orders SET razorpay_order_id = ?
    WHERE id = ?
");
$update->bind_param("si", $razorpayOrder['id'], $orderId);
$update->execute();

$_SESSION['razorpay_order_id'] = $razorpayOrder['id'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Pay Now</title>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>

<script>
var options = {
    "key": "RAZORPAY_KEY_ID",
    "amount": "<?= $order['total'] * 100 ?>",
    "currency": "INR",
    "name": "Rival Society",
    "description": "Order #<?= $orderId ?>",
    "order_id": "<?= $razorpayOrder['id'] ?>",
    "handler": function (response){
        fetch("/rivalsociety/payment/verify.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body:
              "razorpay_payment_id=" + response.razorpay_payment_id +
              "&razorpay_order_id=" + response.razorpay_order_id +
              "&razorpay_signature=" + response.razorpay_signature
        }).then(() => {
            window.location.href = "/rivalsociety/payment/success.php";
        });
    },
    "theme": {
        "color": "#000"
    }
};

var rzp = new Razorpay(options);
rzp.open();
</script>

</body>
</html>
