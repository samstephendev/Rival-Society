<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$api = new Api("RAZORPAY_KEY_ID", "RAZORPAY_KEY_SECRET");

$attributes = [
    'razorpay_order_id' => $_POST['razorpay_order_id'],
    'razorpay_payment_id' => $_POST['razorpay_payment_id'],
    'razorpay_signature' => $_POST['razorpay_signature']
];

try {
    $api->utility->verifyPaymentSignature($attributes);

    /* UPDATE ORDER */
    $stmt = $conn->prepare("
        UPDATE orders
        SET payment_status = 'paid',
            razorpay_payment_id = ?
        WHERE razorpay_order_id = ?
    ");
    $stmt->bind_param(
        "ss",
        $_POST['razorpay_payment_id'],
        $_POST['razorpay_order_id']
    );
    $stmt->execute();

} catch (SignatureVerificationError $e) {

    $stmt = $conn->prepare("
        UPDATE orders SET payment_status = 'failed'
        WHERE razorpay_order_id = ?
    ");
    $stmt->bind_param("s", $_POST['razorpay_order_id']);
    $stmt->execute();

    header("Location: /rivalsociety/payment/failed.php");
    exit;
}
