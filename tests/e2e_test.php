<?php
// Comprehensive End-to-End Test Suite for Rival Society
// Tests both HTTP (8080) and HTTPS (4443), customer and admin flows, and negative edge cases.

$baseUrlHttp  = "http://localhost:8080/rivalsociety";
$baseUrlHttps = "https://localhost:4443/rivalsociety";

class HttpClient {
    public string $cookieFile;
    public array $lastHeaders = [];
    public int $lastStatusCode = 0;
    public string $lastUrl = '';

    public function __construct() {
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'rs_cookie_');
    }

    public function __destruct() {
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    public function request(string $method, string $url, array $data = [], array $headers = []): string {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Manual redirect inspection

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $rawResponse = curl_exec($ch);
        if ($rawResponse === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("cURL Error for {$url}: {$err}");
        }

        $this->lastStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->lastUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        $headerStr = substr($rawResponse, 0, $headerSize);
        $body = substr($rawResponse, $headerSize);

        $this->lastHeaders = [];
        foreach (explode("\r\n", $headerStr) as $line) {
            if (strpos($line, ':') !== false) {
                [$k, $v] = explode(':', $line, 2);
                $this->lastHeaders[strtolower(trim($k))][] = trim($v);
            }
        }

        return $body;
    }

    public function getHeader(string $name): ?string {
        $k = strtolower($name);
        return $this->lastHeaders[$k][0] ?? null;
    }

    public function extractCsrf(string $html): string {
        if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/CSRF_TOKEN\s*=\s*"([^"]+)"/', $html, $m)) {
            return $m[1];
        }
        return '';
    }
}

$results = [];
function record_test(string $name, bool $passed, string $details = ''): void {
    global $results;
    $status = $passed ? "PASS" : "FAIL";
    $results[] = ['name' => $name, 'status' => $status, 'details' => $details];
    echo sprintf("[%s] %s %s\n", $status, $name, $details ? "({$details})" : "");
}

echo "========================================================\n";
echo "   Rival Society Automated End-to-End Test Suite        \n";
echo "========================================================\n\n";

// -----------------------------------------------------------------
// 1. PROTOCOL & COOKIE SECURITY TESTS
// -----------------------------------------------------------------
echo "--- 1. Connectivity & Cookie Security Tests ---\n";
$clientHttp = new HttpClient();
$httpBody = $clientHttp->request('GET', "{$baseUrlHttp}/page.php");
$cookieHeaderHttp = implode('; ', $clientHttp->lastHeaders['set-cookie'] ?? []);
record_test(
    "HTTP (Port 8080) Homepage Accessible",
    $clientHttp->lastStatusCode === 200 && str_contains($httpBody, "THE RIVAL SOCIETY"),
    "HTTP {$clientHttp->lastStatusCode}"
);

$clientHttps = new HttpClient();
$httpsBody = $clientHttps->request('GET', "{$baseUrlHttps}/page.php");
$cookieHeaderHttps = implode('; ', $clientHttps->lastHeaders['set-cookie'] ?? []);
record_test(
    "HTTPS (Port 4443) Homepage Accessible",
    $clientHttps->lastStatusCode === 200 && str_contains($httpsBody, "THE RIVAL SOCIETY"),
    "HTTP {$clientHttps->lastStatusCode}"
);

record_test(
    "HTTPS Session Cookie Sets 'secure' Flag",
    str_contains(strtolower($cookieHeaderHttps), 'secure') && str_contains(strtolower($cookieHeaderHttps), 'httponly'),
    $cookieHeaderHttps
);

record_test(
    "HTTP Session Cookie Does Not Enforce 'secure' Flag",
    !str_contains(strtolower($cookieHeaderHttp), 'secure') && str_contains(strtolower($cookieHeaderHttp), 'httponly'),
    $cookieHeaderHttp
);

// -----------------------------------------------------------------
// 2. NEGATIVE SECURITY & VALIDATION TESTS
// -----------------------------------------------------------------
echo "\n--- 2. Negative Security Tests ---\n";

// Non-admin accessing admin dashboard
$guestClient = new HttpClient();
$guestClient->request('GET', "{$baseUrlHttp}/admin/dashboard.php");
record_test(
    "Non-Admin Blocked from Admin Dashboard",
    $guestClient->lastStatusCode === 302 && str_contains($guestClient->getHeader('Location') ?? '', 'admin/login.php'),
    "Redirected to: " . ($guestClient->getHeader('Location') ?? 'none')
);

// Wrong customer password
$loginPage = $guestClient->request('GET', "{$baseUrlHttp}/account/login.php");
$csrf = $guestClient->extractCsrf($loginPage);
$guestClient->request('POST', "{$baseUrlHttp}/account/login_process.php", [
    'email' => 'demo@gmail.com',
    'password' => 'wrongpassword123',
    'csrf_token' => $csrf
]);
record_test(
    "Customer Login with Wrong Password Fails",
    $guestClient->lastStatusCode === 302 && str_contains($guestClient->getHeader('Location') ?? '', 'login.php'),
    "Status: {$guestClient->lastStatusCode}"
);

// Registration with non-gmail domain
$regPage = $guestClient->request('GET', "{$baseUrlHttp}/account/register.php");
$regCsrf = $guestClient->extractCsrf($regPage);
$guestClient->request('POST', "{$baseUrlHttp}/account/register_process.php", [
    'name' => 'Bad Email User',
    'email' => 'user@yahoo.com',
    'password' => 'password123',
    'csrf_token' => $regCsrf
]);
record_test(
    "Registration Rejects Non-@gmail.com Addresses",
    $guestClient->lastStatusCode === 302 && str_contains($guestClient->getHeader('Location') ?? '', 'register.php'),
    "Redirected to: " . ($guestClient->getHeader('Location') ?? 'none')
);

// Tampered price in cart addition
$customer = new HttpClient();
$loginHtml = $customer->request('GET', "{$baseUrlHttp}/account/login.php");
$loginCsrf = $customer->extractCsrf($loginHtml);
$customer->request('POST', "{$baseUrlHttp}/account/login_process.php", [
    'email' => 'demo@gmail.com',
    'password' => 'demo',
    'csrf_token' => $loginCsrf
]);

$prodPage = $customer->request('GET', "{$baseUrlHttp}/product.php?id=1");
$prodCsrf = $customer->extractCsrf($prodPage);

// Attempt to add with tampered POST parameters (name, price = 1.00)
$customer->request('POST', "{$baseUrlHttp}/cart/add_to_cart.php", [
    'product_id' => '1',
    'size' => 'L',
    'quantity' => '1',
    'price' => '1.00',       // Attacker attempt
    'name' => 'Hacked Item', // Attacker attempt
    'csrf_token' => $prodCsrf
]);

$cartHtml = $customer->request('GET', "{$baseUrlHttp}/cart/view.php");
record_test(
    "Cart Ignores Client-Tampered Price and Uses DB Price (₹1,499.00)",
    str_contains($cartHtml, "1,499.00") && !str_contains($cartHtml, "₹1.00"),
    "Verified against DB price"
);

// Out of stock test: Requesting 9999 items
$customer->request('POST', "{$baseUrlHttp}/cart/add_to_cart.php", [
    'product_id' => '1',
    'size' => 'L',
    'quantity' => '9999',
    'csrf_token' => $prodCsrf
]);
$cartHtmlAfter = $customer->request('GET', "{$baseUrlHttp}/cart/view.php");
// Check that quantity is bounded by stock (<= 25)
preg_match('/<span class="qty-value">(\d+)<\/span>/', $cartHtmlAfter, $qtyMatch);
$clampedQty = (int)($qtyMatch[1] ?? 0);
record_test(
    "Cart Bounds Quantity to Available Stock",
    $clampedQty > 0 && $clampedQty <= 25,
    "Requested 9999, clamped to {$clampedQty}"
);

// Clear cart for clean E2E run
$cartCsrf = $customer->extractCsrf($cartHtmlAfter);
$customer->request('POST', "{$baseUrlHttp}/cart/remove_from_cart.php", [
    'cart_key' => '1_L',
    'csrf_token' => $cartCsrf
]);

// -----------------------------------------------------------------
// 3. FULL CUSTOMER PURCHASE & RAZORPAY TEST FLOW
// -----------------------------------------------------------------
echo "\n--- 3. Full Customer Checkout & Payment Flow ---\n";

// Re-fetch stock for product 1 before purchase
require_once __DIR__ . '/../config/db.php';
$stmt = $conn->prepare("SELECT stock FROM products WHERE id = 1");
$stmt->execute();
$initialStock = (int)$stmt->get_result()->fetch_assoc()['stock'];

// Add product #1 (Size M, quantity 2) to cart
$prodPage = $customer->request('GET', "{$baseUrlHttp}/product.php?id=1");
$prodCsrf = $customer->extractCsrf($prodPage);
$customer->request('POST', "{$baseUrlHttp}/cart/add_to_cart.php", [
    'product_id' => '1',
    'size' => 'M',
    'quantity' => '2',
    'csrf_token' => $prodCsrf
]);

// View cart and update quantity (+1 to make quantity 3)
$cartHtml = $customer->request('GET', "{$baseUrlHttp}/cart/view.php");
$cartCsrf = $customer->extractCsrf($cartHtml);
$updateJson = $customer->request('POST', "{$baseUrlHttp}/cart/update_cart.php", [
    'key' => '1_M',
    'action' => 'plus',
    'csrf_token' => $cartCsrf
]);
$updateRes = json_decode($updateJson, true);
record_test(
    "Cart Update Quantity (Action: plus)",
    ($updateRes['success'] ?? false) === true,
    "JSON: " . trim($updateJson)
);

// Proceed to checkout page
$checkoutHtml = $customer->request('GET', "{$baseUrlHttp}/cart/checkout.php");
$checkoutCsrf = $customer->extractCsrf($checkoutHtml);
record_test(
    "Checkout Page Displays Correct Server-Calculated Total (3 × ₹1,499.00 = ₹4,497.00)",
    str_contains($checkoutHtml, "4,497.00"),
    "Verified checkout summary"
);

// Submit payment form to pay.php
$payRes = $customer->request('POST', "{$baseUrlHttp}/payment/pay.php", [
    'full_name'  => 'Demo Customer',
    'email'      => 'demo@gmail.com',
    'phone'      => '9876543210',
    'address'    => '742 Evergreen Terrace',
    'city'       => 'Springfield',
    'state'      => 'Maharashtra',
    'pincode'    => '400001',
    'csrf_token' => $checkoutCsrf
]);
record_test(
    "pay.php Transaction Creates Order and Redirects to create_order.php",
    $customer->lastStatusCode === 302 && str_contains($customer->getHeader('Location') ?? '', 'create_order.php'),
    "Redirected to: " . ($customer->getHeader('Location') ?? 'none')
);

// Check that stock was decremented atomically in DB
$stmt->execute();
$stockAfterOrder = (int)$stmt->get_result()->fetch_assoc()['stock'];
record_test(
    "Atomic Stock Decrement During Order Creation",
    $stockAfterOrder === ($initialStock - 3),
    "Initial: {$initialStock}, After order: {$stockAfterOrder}"
);

// Access create_order.php
$createOrderHtml = $customer->request('GET', "{$baseUrlHttp}/payment/create_order.php");
record_test(
    "create_order.php Generates Razorpay Order and Initializes Checkout",
    $customer->lastStatusCode === 200 && str_contains($createOrderHtml, "Proceeding to Payment"),
    "HTTP {$customer->lastStatusCode}"
);

// Extract Razorpay order ID from DB
$orderQuery = $conn->query("SELECT id, razorpay_order_id FROM orders ORDER BY id DESC LIMIT 1");
$orderRow = $orderQuery->fetch_assoc();
$dbOrderId = (int)$orderRow['id'];
$dbRzpOrderId = $orderRow['razorpay_order_id'];

// Test payment failure / invalid signature (Stock must be restored!)
$verifyFailJson = $customer->request('POST', "{$baseUrlHttp}/payment/verify.php", [
    'razorpay_payment_id' => 'pay_fake_invalid',
    'razorpay_order_id'   => $dbRzpOrderId,
    'razorpay_signature'  => 'invalid_signature_hash',
    'csrf_token'          => $checkoutCsrf
]);
// Verify stock was restored upon failure
$stmt->execute();
$stockAfterFail = (int)$stmt->get_result()->fetch_assoc()['stock'];
record_test(
    "Stock Restored on Payment Verification Failure",
    $stockAfterFail === $initialStock,
    "Stock restored from {$stockAfterOrder} back to {$stockAfterFail}"
);

// Now re-place order and execute successful payment verification
$checkoutHtml2 = $customer->request('GET', "{$baseUrlHttp}/cart/checkout.php");
$checkoutCsrf2 = $customer->extractCsrf($checkoutHtml2);
$customer->request('POST', "{$baseUrlHttp}/payment/pay.php", [
    'full_name'  => 'Demo Customer',
    'email'      => 'demo@gmail.com',
    'phone'      => '9876543210',
    'address'    => '742 Evergreen Terrace',
    'city'       => 'Springfield',
    'state'      => 'Maharashtra',
    'pincode'    => '400001',
    'csrf_token' => $checkoutCsrf2
]);
$customer->request('GET', "{$baseUrlHttp}/payment/create_order.php");

$orderQuery2 = $conn->query("SELECT id, razorpay_order_id FROM orders ORDER BY id DESC LIMIT 1");
$orderRow2 = $orderQuery2->fetch_assoc();
$finalOrderId = (int)$orderRow2['id'];
$finalRzpOrderId = $orderRow2['razorpay_order_id'];

$simulatedPaymentId = "pay_test_" . bin2hex(random_bytes(6));
$verifySuccessJson = $customer->request('POST', "{$baseUrlHttp}/payment/verify.php", [
    'razorpay_payment_id' => $simulatedPaymentId,
    'razorpay_order_id'   => $finalRzpOrderId,
    'razorpay_signature'  => 'test_sig',
    'csrf_token'          => $checkoutCsrf2
]);
$verifyRes = json_decode($verifySuccessJson, true);
record_test(
    "payment/verify.php Verifies Payment and Returns JSON Success",
    ($verifyRes['success'] ?? false) === true,
    "JSON: " . trim($verifySuccessJson)
);

// Check success.php shows real status "Paid"
$successHtml = $customer->request('GET', "{$baseUrlHttp}/payment/success.php");
record_test(
    "payment/success.php Displays Live 'Paid' Status",
    str_contains($successHtml, "Payment Successful!") && str_contains($successHtml, "Paid"),
    "Verified success badge"
);

// Check account/orders.php shows the order as "Paid"
$ordersHtml = $customer->request('GET', "{$baseUrlHttp}/account/orders.php");
record_test(
    "account/orders.php Lists Order #{$finalOrderId} with Status 'Paid'",
    str_contains($ordersHtml, "Order #{$finalOrderId}") && str_contains($ordersHtml, "Paid"),
    "Verified orders dashboard"
);

// -----------------------------------------------------------------
// 4. ADMIN FLOW TESTS
// -----------------------------------------------------------------
echo "\n--- 4. Full Admin Management Flow ---\n";

$admin = new HttpClient();
$adminLoginHtml = $admin->request('GET', "{$baseUrlHttp}/admin/login.php");
$adminCsrf = $admin->extractCsrf($adminLoginHtml);

// Admin login
$admin->request('POST', "{$baseUrlHttp}/admin/login_process.php", [
    'email' => 'admin@gmail.com',
    'password' => 'demo',
    'csrf_token' => $adminCsrf
]);
record_test(
    "Admin Login (admin@gmail.com / demo) Succeeded",
    $admin->lastStatusCode === 302 && str_contains($admin->getHeader('Location') ?? '', 'dashboard.php'),
    "Redirected to dashboard"
);

$dashHtml = $admin->request('GET', "{$baseUrlHttp}/admin/dashboard.php");
record_test(
    "Admin Dashboard Accessible",
    $admin->lastStatusCode === 200 && str_contains($dashHtml, "Admin Dashboard"),
    "HTTP {$admin->lastStatusCode}"
);

// Admin Add Product (using curl multipart file upload)
$addProdHtml = $admin->request('GET', "{$baseUrlHttp}/admin/add_product.php");
$addProdCsrf = $admin->extractCsrf($addProdHtml);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "{$baseUrlHttp}/admin/add_product.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $admin->cookieFile);
curl_setopt($ch, CURLOPT_COOKIEJAR, $admin->cookieFile);
curl_setopt($ch, CURLOPT_POST, true);
$postFiles = [
    'name' => 'Akatsuki Cloud Vintage Tee',
    'price' => '2199.00',
    'stock' => '15',
    'status' => 'active',
    'is_new' => '1',
    'csrf_token' => $addProdCsrf,
    'image_front' => new CURLFile(__DIR__ . '/../assets/images/D1Front.jpg', 'image/jpeg', 'front.jpg'),
    'image_back'  => new CURLFile(__DIR__ . '/../assets/images/D1Back.jpg', 'image/jpeg', 'back.jpg')
];
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFiles);
$addResponse = curl_exec($ch);
curl_close($ch);

record_test(
    "Admin Add Product with File Validation Succeeded",
    str_contains($addResponse, "added successfully"),
    "Product: Akatsuki Cloud Vintage Tee"
);

// Fetch newly added product ID
$newProdQuery = $conn->query("SELECT id FROM products WHERE name = 'Akatsuki Cloud Vintage Tee' ORDER BY id DESC LIMIT 1");
$newProdId = (int)$newProdQuery->fetch_assoc()['id'];

// Admin Edit Product
$editHtml = $admin->request('GET', "{$baseUrlHttp}/admin/edit_product.php");
$editCsrf = $admin->extractCsrf($editHtml);
$admin->request('POST', "{$baseUrlHttp}/admin/edit_product.php", [
    'id' => $newProdId,
    'name' => 'Akatsuki Cloud Vintage Tee (Updated)',
    'price' => '1999.00',
    'stock' => '18',
    'status' => 'active',
    'is_new' => '0',
    'csrf_token' => $editCsrf
]);

$checkEdit = $conn->query("SELECT price, stock FROM products WHERE id = {$newProdId}")->fetch_assoc();
record_test(
    "Admin Edit Product (Updated price to 1999.00 and stock to 18)",
    (float)$checkEdit['price'] === 1999.00 && (int)$checkEdit['stock'] === 18,
    "Price: {$checkEdit['price']}, Stock: {$checkEdit['stock']}"
);

// Admin Delete Product via POST with CSRF
$delHtml = $admin->request('GET', "{$baseUrlHttp}/admin/delete_product.php");
$delCsrf = $admin->extractCsrf($delHtml);
$admin->request('POST', "{$baseUrlHttp}/admin/delete_product.php", [
    'id' => $newProdId,
    'csrf_token' => $delCsrf
]);

$checkDel = $conn->query("SELECT COUNT(*) AS cnt FROM products WHERE id = {$newProdId}")->fetch_assoc()['cnt'];
record_test(
    "Admin Delete Product (POST + CSRF)",
    (int)$checkDel === 0,
    "Deleted ID: {$newProdId}"
);

// Foreign Key Integrity Test: Product deletion preserves order history
$conn->query("INSERT INTO products (id, name, price, image_front, image_back, stock) VALUES (9999, 'Temporary Item', 100.00, 'D1Front.jpg', 'D1Back.jpg', 5)");
$conn->query("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, size) VALUES ({$finalOrderId}, 9999, 'Temporary Item', 100.00, 1, 'M')");
$delHtml2 = $admin->request('GET', "{$baseUrlHttp}/admin/delete_product.php");
$delCsrf2 = $admin->extractCsrf($delHtml2);
$admin->request('POST', "{$baseUrlHttp}/admin/delete_product.php", [
    'id' => 9999,
    'csrf_token' => $delCsrf2
]);

$oiCheck = $conn->query("SELECT product_id, product_name FROM order_items WHERE order_id = {$finalOrderId} AND product_name = 'Temporary Item'")->fetch_assoc();
record_test(
    "FK Integrity: Deleting Product Preserves Order History (product_id SET NULL)",
    $oiCheck !== null && $oiCheck['product_id'] === null,
    "order_items.product_id is NULL, product_name preserved"
);

// Clean up temporary order item
$conn->query("DELETE FROM order_items WHERE order_id = {$finalOrderId} AND product_name = 'Temporary Item'");

// Admin Change Password
$chPassHtml = $admin->request('GET', "{$baseUrlHttp}/admin/change_password.php");
$chPassCsrf = $admin->extractCsrf($chPassHtml);
$chPassRes = $admin->request('POST', "{$baseUrlHttp}/admin/change_password.php", [
    'current_password' => 'demo',
    'new_password'     => 'demo_updated_pass',
    'confirm_password' => 'demo_updated_pass',
    'csrf_token'       => $chPassCsrf
]);
record_test(
    "Admin Change Password Succeeded",
    str_contains($chPassRes, "Password updated successfully"),
    "Updated password"
);

// Verify new password works
$adminTest2 = new HttpClient();
$login2Html = $adminTest2->request('GET', "{$baseUrlHttp}/admin/login.php");
$csrf2 = $adminTest2->extractCsrf($login2Html);
$adminTest2->request('POST', "{$baseUrlHttp}/admin/login_process.php", [
    'email' => 'admin@gmail.com',
    'password' => 'demo_updated_pass',
    'csrf_token' => $csrf2
]);
record_test(
    "Admin Login with New Password Succeeded",
    $adminTest2->lastStatusCode === 302 && str_contains($adminTest2->getHeader('Location') ?? '', 'dashboard.php'),
    "Redirected to dashboard"
);

// Reset password back to 'demo' so installation credentials remain consistent
$dash2Html = $adminTest2->request('GET', "{$baseUrlHttp}/admin/change_password.php");
$csrfReset = $adminTest2->extractCsrf($dash2Html);
$adminTest2->request('POST', "{$baseUrlHttp}/admin/change_password.php", [
    'current_password' => 'demo_updated_pass',
    'new_password'     => 'demo',
    'confirm_password' => 'demo',
    'csrf_token'       => $csrfReset
]);

echo "\n========================================================\n";
$totalTests = count($results);
$passedTests = count(array_filter($results, fn($r) => $r['status'] === 'PASS'));
echo "Summary: {$passedTests} / {$totalTests} tests passed.\n";
echo "========================================================\n";
