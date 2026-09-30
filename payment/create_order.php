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
$useLocalMock = empty($keyId) || empty($keySecret) || str_starts_with($keyId, 'rzp_test_Your') || str_contains($keyId, 'XXXXXXXX') || str_contains($keyId, 'YOUR_');

$amountInPaise = (int) round((float)$order['total'] * 100);
$razorpayOrderId = $order['razorpay_order_id'];

// In local development, use a mock order when the Razorpay credentials are missing or placeholder values.
if ($useLocalMock && (empty($razorpayOrderId) || !str_starts_with($razorpayOrderId, 'order_mock_'))) {
    $razorpayOrderId = 'order_mock_' . bin2hex(random_bytes(8));
    $update = $conn->prepare("UPDATE orders SET razorpay_order_id = ? WHERE id = ?");
    $update->bind_param("si", $razorpayOrderId, $orderId);
    $update->execute();
} elseif (empty($razorpayOrderId) || (!str_starts_with($keyId, 'rzp_test_Your') && str_starts_with($razorpayOrderId, 'order_mock_'))) {
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
        if (str_starts_with($keyId, 'rzp_test_Your') || empty($keyId) || empty($keySecret)) {
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
<?php rs_critical_css(); ?>
<link rel="stylesheet" href="<?= asset_url('/assets/base.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/assets/style.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/cart/style.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body class="body">

<main class="checkout-section">
<div class="checkout-card" style="text-align: center;">
    <h1>Proceeding to Payment</h1>
    <p style="color: var(--rs-text-muted); margin: 15px 0;">Order #<?= htmlspecialchars((string)$orderId, ENT_QUOTES, 'UTF-8') ?></p>
    <div style="font-size: var(--font-2xl); font-weight: 800; color: #ffffff; margin-bottom: 25px;">
        Total: <span style="color: var(--rs-cyan);">₹<?= number_format((float)$order['total'], 2) ?></span>
    </div>

    <button id="rzp-button" class="btn primary checkout-btn">
        <i class="fas fa-lock" style="margin-right: 8px;"></i> Open Payment Gateway
    </button>

    <div style="margin-top: 24px;">
        <a href="<?= url('/cart/view.php') ?>" class="back-link">
            Cancel and Return to Cart
        </a>
    </div>
</div>
</main>

<script>
const VERIFY_URL = "<?= url('/payment/verify.php') ?>";
const SUCCESS_URL = "<?= url('/payment/success.php') ?>";
const FAILED_URL = "<?= url('/payment/failed.php') ?>";
const CSRF_TOKEN = "<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>";
const LOCAL_MOCK = <?= json_encode((bool) $useLocalMock, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

const triggerLocalMockPayment = () => {
    const formData = new URLSearchParams({
        razorpay_payment_id: 'pay_test_' + Date.now(),
        razorpay_order_id: '<?= htmlspecialchars($razorpayOrderId, ENT_QUOTES, 'UTF-8') ?>',
        razorpay_signature: 'test_sig',
        csrf_token: CSRF_TOKEN
    });

    fetch(VERIFY_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.success) {
            window.location.href = SUCCESS_URL;
        } else {
            window.location.href = FAILED_URL + '?error=' + encodeURIComponent(data.message || 'Payment failed');
        }
    })
    .catch(err => {
        console.error('Local mock verification error:', err);
        window.location.href = FAILED_URL + '?error=mock_payment_error';
    });
};

if (!LOCAL_MOCK) {
    const options = {
        "key": "<?= htmlspecialchars($keyId, ENT_QUOTES, 'UTF-8') ?>",
        "amount": "<?= $amountInPaise ?>",
        "currency": "INR",
        "name": "Rival Society",
        "description": "Order #<?= $orderId ?>",
        "order_id": "<?= htmlspecialchars($razorpayOrderId, ENT_QUOTES, 'UTF-8') ?>",
        "method": {
            "upi": true,
            "card": true,
            "netbanking": true,
            "wallet": true,
            "emi": false
        },
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

    rzp.on('payment.failed', function (response){
        console.error("Payment failed:", response.error);
        const reason = response.error && response.error.description ? response.error.description : "Payment failed";
        window.location.href = FAILED_URL + "?error=" + encodeURIComponent(reason);
    });

    document.getElementById('rzp-button').onclick = function(e){
        e.preventDefault();
        rzp.open();
    };

    window.addEventListener('load', () => {
        try {
            rzp.open();
        } catch (e) {
            console.warn("Auto modal open deferred to user click.", e);
        }
    });
} else {
    document.getElementById('rzp-button').onclick = function(e){
        e.preventDefault();
        triggerLocalMockPayment();
    };

    window.addEventListener('load', () => {
        triggerLocalMockPayment();
    });
}
</script>

</body>
</html>
