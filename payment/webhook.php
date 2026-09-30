<?php
// Razorpay Webhook Endpoint
// Handles asynchronous payment updates from Razorpay servers

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/razorpay.php';

$payload = file_get_contents('php://input');
$receivedSignature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

if (empty($payload)) {
    http_response_code(400);
    die("Empty payload");
}

$keys = get_razorpay_config();
$secret = !empty($keys['webhook_secret']) ? $keys['webhook_secret'] : $keys['key_secret'];

// Verify webhook signature if secret is configured
if (!empty($secret)) {
    $expectedSignature = hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($expectedSignature, $receivedSignature)) {
        http_response_code(400);
        die("Invalid signature");
    }
}

$data = json_decode($payload, true);
if (!$data || !isset($data['event'])) {
    http_response_code(400);
    die("Invalid JSON");
}

$event = $data['event'];

try {
    if ($event === 'order.paid' || $event === 'payment.captured') {
        $paymentEntity = $data['payload']['payment']['entity'] ?? [];
        $razorpayOrderId = $paymentEntity['order_id'] ?? ($data['payload']['order']['entity']['id'] ?? '');
        $razorpayPaymentId = $paymentEntity['id'] ?? '';

        if (!empty($razorpayOrderId)) {
            $stmt = $conn->prepare("SELECT id, payment_status FROM orders WHERE razorpay_order_id = ?");
            $stmt->bind_param("s", $razorpayOrderId);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if ($order && $order['payment_status'] !== 'paid') {
                $update = $conn->prepare("UPDATE orders SET payment_status = 'paid', razorpay_payment_id = ? WHERE id = ?");
                $update->bind_param("si", $razorpayPaymentId, $order['id']);
                $update->execute();
            }
        }
    } elseif ($event === 'payment.failed') {
        $paymentEntity = $data['payload']['payment']['entity'] ?? [];
        $razorpayOrderId = $paymentEntity['order_id'] ?? '';

        if (!empty($razorpayOrderId)) {
            $stmt = $conn->prepare("SELECT id, payment_status FROM orders WHERE razorpay_order_id = ?");
            $stmt->bind_param("s", $razorpayOrderId);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if ($order && $order['payment_status'] === 'pending') {
                $orderId = (int)$order['id'];
                $update = $conn->prepare("UPDATE orders SET payment_status = 'failed' WHERE id = ?");
                $update->bind_param("i", $orderId);
                $update->execute();

                // Restore stock
                $itemsStmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $itemsStmt->bind_param("i", $orderId);
                $itemsStmt->execute();
                $itemsRes = $itemsStmt->get_result();

                while ($item = $itemsRes->fetch_assoc()) {
                    if (!empty($item['product_id'])) {
                        $restore = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                        $restore->bind_param("ii", $item['quantity'], $item['product_id']);
                        $restore->execute();
                    }
                }
            }
        }
    }

    http_response_code(200);
    echo "Webhook processed successfully";

} catch (mysqli_sql_exception $e) {
    error_log("Webhook database error: " . $e->getMessage());
    http_response_code(500);
    echo "Internal server error";
}
