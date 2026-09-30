<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/razorpay.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Razorpay\Api\Api;

$orderId = (int)($_SESSION['last_order_id'] ?? 0);
$userId  = (int)$_SESSION['cust_user_id'];

if ($orderId <= 0) {
    header("Location: " . url('/cart/view.php'));
    exit;
}

// Ensure order exists and belongs to the authenticated customer
$stmt = $conn->prepare("
    SELECT o.id, o.total, o.payment_status, o.razorpay_order_id,
           b.full_name, b.email, b.phone
    FROM orders o
    LEFT JOIN order_billing b ON b.order_id = o.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: " . url('/cart/view.php'));
    exit;
}

if ($order['payment_status'] === 'paid') {
    header("Location: " . url('/payment/success.php'));
    exit;
}

$keys = get_razorpay_config();
$keyId = $keys['key_id'];
$keySecret = $keys['key_secret'];

$amountInPaise = (int) round((float)$order['total'] * 100);
$razorpayOrderId = $order['razorpay_order_id'];

// If no Razorpay order exists yet, create one
if (empty($razorpayOrderId)) {
    try {
        $api = new Api($keyId, $keySecret);
        $rzpOrder = $api->order->create([
            'amount'   => $amountInPaise,
            'currency' => 'INR',
            'receipt'  => 'order_' . $orderId,
            'notes'    => [
                'user_id' => (string)$userId,
                'order_id' => (string)$orderId,
            ]
        ]);
        $razorpayOrderId = $rzpOrder['id'];

        $update = $conn->prepare("UPDATE orders SET razorpay_order_id = ? WHERE id = ?");
        $update->bind_param("si", $razorpayOrderId, $orderId);
        $update->execute();

    } catch (Exception $e) {
        error_log("Razorpay Order API creation error: " . $e->getMessage());
        // If keys are placeholder/dummy in local test, fallback gracefully with a simulated mock order ID
        if (str_starts_with($keyId, 'rzp_test_Your') || empty($keyId)) {
            $razorpayOrderId = 'order_mock_' . bin2hex(random_bytes(8));
            $update = $conn->prepare("UPDATE orders SET razorpay_order_id = ? WHERE id = ?");
            $update->bind_param("si", $razorpayOrderId, $orderId);
            $update->execute();
        } else {
            $_SESSION['checkout_error'] = "Payment gateway initialization error: " . $e->getMessage();
            header("Location: " . url('/cart/checkout.php'));
            exit;
        }
    }
}

$_SESSION['razorpay_order_id'] = $razorpayOrderId;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pay Now | Rival Society</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
<link rel="stylesheet" href="<?= url('/cart/style.css') ?>">
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body class="body">

<section class="checkout-section">
<div class="checkout-card" style="text-align: center;">
    <h1>Proceeding to Payment</h1>
    <p style="color: #aaa; margin: 15px 0;">Order #<?= htmlspecialchars((string)$orderId, ENT_QUOTES, 'UTF-8') ?></p>
    <div style="font-size: 24px; font-weight: bold; color: #fff; margin-bottom: 25px;">
        Total: ₹<?= number_format((float)$order['total'], 2) ?>
    </div>

    <button id="rzp-button" class="btn primary" style="width: 100%; padding: 14px; font-size: 16px;">
        Open Payment Gateway
    </button>

    <div style="margin-top: 20px;">
        <a href="<?= url('/cart/view.php') ?>" style="color: #888; text-decoration: none;">Cancel and Return to Cart</a>
    </div>
</div>
</section>

<script>
const VERIFY_URL = "<?= url('/payment/verify.php') ?>";
const SUCCESS_URL = "<?= url('/payment/success.php') ?>";
const FAILED_URL = "<?= url('/payment/failed.php') ?>";
const CSRF_TOKEN = "<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>";

const options = {
    "key": "<?= htmlspecialchars($keyId, ENT_QUOTES, 'UTF-8') ?>",
    "amount": "<?= $amountInPaise ?>",
    "currency": "INR",
    "name": "Rival Society",
    "description": "Order #<?= $orderId ?>",
    "order_id": "<?= htmlspecialchars($razorpayOrderId, ENT_QUOTES, 'UTF-8') ?>",
    "prefill": {
        "name": "<?= htmlspecialchars($order['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>",
        "email": "<?= htmlspecialchars($order['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>",
        "contact": "<?= htmlspecialchars($order['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
    },
    "handler": function (response) {
        fetch(VERIFY_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
                "razorpay_payment_id": response.razorpay_payment_id || "",
                "razorpay_order_id": response.razorpay_order_id || "",
                "razorpay_signature": response.razorpay_signature || "",
                "csrf_token": CSRF_TOKEN
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.success) {
                window.location.href = SUCCESS_URL;
            } else {
                window.location.href = FAILED_URL + "?error=" + encodeURIComponent(data.message || "Payment verification failed");
            }
        })
        .catch(err => {
            console.error("Verification error:", err);
            window.location.href = FAILED_URL + "?error=network_error";
        });
    },
    "modal": {
        "ondismiss": function() {
            console.log("Checkout modal dismissed by user.");
        }
    },
    "theme": {
        "color": "#000000"
    }
};

const rzp = new Razorpay(options);

document.getElementById('rzp-button').onclick = function(e){
    e.preventDefault();
    rzp.open();
};

// Automatically open modal on initial load
window.addEventListener('load', () => {
    rzp.open();
});
</script>

</body>
</html>
