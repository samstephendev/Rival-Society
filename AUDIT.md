# Comprehensive Codebase Audit: Rival Society

This audit covers every non-vendor PHP, JS, and SQL file in the "Rival Society" repository. The project is a plain PHP + mysqli e-commerce site with customer auth, cart, Razorpay payment processing, and an admin panel.

---

## 1. Database Entities & Query Inventory

The codebase references the following tables, columns, and SQL operations:

### Table: `cust_user`
* **Columns used:** `id`, `name`, `email`, `password`, `remember_token`
* **Queries:**
  * `account/register_process.php` (L8): `INSERT INTO cust_user (name, email, password) VALUES (?, ?, ?)`
  * `account/login_process.php` (L21): `SELECT id, name, password FROM cust_user WHERE email = ? LIMIT 1`
  * `account/login_process.php` (L51): `UPDATE cust_user SET remember_token = ? WHERE id = ?`
  * `account/logout.php` (L5): `UPDATE cust_user SET remember_token = NULL WHERE id = ?`
  * `config/session.php` (L11): `SELECT id, name FROM cust_user WHERE remember_token = ?`

### Table: `users` (Admin Accounts)
* **Columns used:** `id`, `email`, `password`
* **Queries:**
  * `admin/login_process.php` (L13): `SELECT id, password FROM users WHERE email = ?`
  * `admin/change_password.php` (L7): `UPDATE users SET password=? WHERE id=?`

### Table: `products`
* **Columns used:** `id`, `name`, `price`, `image_front`, `image_back`, `stock`, `status`, `is_new`, `created_at`
* **Queries:**
  * `page.php` (L95): `SELECT * FROM products WHERE status='active' ORDER BY created_at DESC`
  * `product.php` (L12): `SELECT * FROM products WHERE id = ? AND status = 'active'`
  * `admin/add_product.php` (L57): `INSERT INTO products (name, price, image_front, image_back, stock, status, is_new) VALUES (?, ?, ?, ?, ?, ?, ?)`
  * `admin/edit_product.php` (L8): `UPDATE products SET name=?, price=?, stock=?, status=?, is_new=? WHERE id=?`
  * `admin/edit_product.php` (L26): `SELECT * FROM products`
  * `admin/delete_product.php` (L6): `DELETE FROM products WHERE id=?`
  * `admin/delete_product.php` (L13): `SELECT * FROM products`

### Table: `orders`
* **Columns used:** `id`, `user_id`, `total`, `payment_status`, `razorpay_order_id`, `razorpay_payment_id`, `created_at`
* **Queries:**
  * `payment/pay.php` (L42): `INSERT INTO orders (user_id, total, payment_status) VALUES (?, ?, 'pending')`
  * `payment/create_order.php` (L17): `SELECT total FROM orders WHERE id = ?`
  * `payment/create_order.php` (L35): `UPDATE orders SET razorpay_order_id = ? WHERE id = ?`
  * `payment/verify.php` (L21): `UPDATE orders SET payment_status = 'paid', razorpay_payment_id = ? WHERE razorpay_order_id = ?`
  * `payment/verify.php` (L37): `UPDATE orders SET payment_status = 'failed' WHERE razorpay_order_id = ?`
  * `account/orders.php` (L9): `SELECT id, total, payment_status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC`

### Table: `order_items`
* **Columns used:** `id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `size`
* **Queries:**
  * `payment/pay.php` (L85): `INSERT INTO order_items (order_id, product_name, price, quantity, size) VALUES (?, ?, ?, ?, ?)` *(Note: `product_id` missing from insert)*
  * `account/orders.php` (L115): `SELECT product_name, quantity, size, price FROM order_items WHERE order_id = ?`

### Table: `order_billing`
* **Columns used:** `id`, `order_id`, `full_name`, `email`, `phone`, `address`, `city`, `state`, `pincode`
* **Queries:**
  * `payment/pay.php` (L61): `INSERT INTO order_billing (order_id, full_name, email, phone, address, city, state, pincode) VALUES (?, ?, ?, ?, ?, ?, ?, ?)`

### Table: `cart` (Erroneous / Inconsistent)
* **Columns used:** `id`
* **Queries:**
  * `cart/remove_from_cart.php` (L7): `DELETE FROM cart WHERE id=?` *(Broken: session cart is used throughout the app, no database cart table exists).*

---

## 2. Findings Grouped by Category

### A. Broken (Crashes or Wrong Behavior)

1. **`assets/script.js` (Lines 67–91): JavaScript ReferenceError / Out of Scope**
   * **Problem:** `navItems`, `header`, and `bar` are defined inside the `DOMContentLoaded` callback (lines 8–11). The smooth-scroll loop `navItems.forEach(...)` at line 67 is placed outside `DOMContentLoaded`, causing `Uncaught ReferenceError: navItems is not defined` on page load, breaking all navigation click events.
   * **Fix:** Move the navigation smooth-scroll event handler inside the `DOMContentLoaded` block.

2. **`account/logout.php` (Lines 4–10): Missing Database Connection Include**
   * **Problem:** Calls `$conn->prepare(...)` to clear `remember_token`, but `config/db.php` is never required. `$conn` is undefined, throwing `Fatal error: Uncaught Error: Call to a member function prepare() on null`.
   * **Fix:** Add `require_once __DIR__ . '/../config/db.php';` before database operations.

3. **`admin/dashboard.php`, `admin/add_product.php`, `admin/edit_product.php`, `admin/delete_product.php`, `admin/change_password.php` (Line 1 or 2): Wrong Authentication Guard**
   * **Problem:** These admin pages require `config/auth.php`, which inspects `$_SESSION['cust_user_id']` (customer login) and redirects unauthenticated users to `/rivalsociety/account/login.php`. A logged-in customer can access admin pages, while an admin logged in via `admin/login.php` (setting `$_SESSION['admin_id']`) is blocked and redirected to customer login.
   * **Fix:** Require `admin/auth.php` (checking `$_SESSION['admin_id']`) across all admin scripts.

4. **`admin/change_password.php` (Lines 7–9): Undefined Parameter Binding**
   * **Problem:** After requiring `config/auth.php`, line 8 binds `$_SESSION["admin_id"]`. If accessed by a customer, `$_SESSION["admin_id"]` is null, causing an invalid query.
   * **Fix:** Use `admin/auth.php` so `$_SESSION["admin_id"]` is guaranteed, and verify current password before updating.

5. **`payment/pay.php` (Lines 114–118): Premature Cart Clearing & Bypass of Payment Gateway**
   * **Problem:** `pay.php` unsets `$_SESSION['cart']` immediately after creating an order with status `pending`, then redirects directly to `payment/success.php`, bypassing `create_order.php` and Razorpay checkout altogether.
   * **Fix:** In `pay.php`, create the order, order_items (including `product_id`), order_billing, and decrement stock atomically in a transaction, set `$_SESSION['last_order_id'] = $orderId`, do NOT clear cart yet, and redirect to `create_order.php`.

6. **`payment/create_order.php` (Lines 26, 55): Hardcoded Environment and Dummy Key Strings**
   * **Problem:** Line 26 attempts to read `$_ENV['RAZORPAY_KEY_ID']` (empty without a loader), while line 55 hardcodes `"key": "RAZORPAY_KEY_ID"` in JavaScript options. The Razorpay checkout modal fails to initialize.
   * **Fix:** Load credentials via a key loader from `payment/key.env`, pass the real `keyId` to the frontend options, and keep `keySecret` server-side only.

7. **`payment/create_order.php` (Lines 29, 56): Floating Point Paise Calculation**
   * **Problem:** `$order['total'] * 100` produces a float (e.g. `1999.0`), which Razorpay rejects or treats incorrectly.
   * **Fix:** Use `(int) round($order['total'] * 100)` for integer paise.

8. **`payment/verify.php` (Lines 9, 43): Hardcoded Dummy Keys and Client-Side Fetch Redirection Failure**
   * **Problem:** Line 9 creates Api with string literals `"RAZORPAY_KEY_ID"`. Line 43 issues `header("Location: failed.php")` upon verification failure. Because `verify.php` is called via `fetch()` in `create_order.php`, the fetch follows the redirect and resolves normally, causing `create_order.php`'s `.then()` to redirect the browser to `success.php` regardless of failure.
   * **Fix:** Load keys from `payment/key.env`. Return JSON responses (`{"success": true}` or `{"success": false, "message": "..."}`). In `create_order.php`, inspect the JSON response and redirect to `success.php` on success or `failed.php` on failure.

9. **`payment/success.php` (Line 111): Hardcoded "Pending" Status**
   * **Problem:** Displays `"Your payment status is currently Pending."` statically, ignoring the actual payment status in the database.
   * **Fix:** Query `orders` table by `id = $orderId AND user_id = $userId` and display the real status (`paid`, `pending`, or `failed`).

10. **`cart/remove_from_cart.php` (Lines 5–9): Querying Non-Existent `cart` Table**
    * **Problem:** Executes `DELETE FROM cart WHERE id=?`. Cart items are stored in `$_SESSION['cart']`, not MySQL. This query causes an SQL error or does nothing to remove the session cart item.
    * **Fix:** Rewrite `remove_from_cart.php` to accept a cart item key (e.g. `product_id_size`), validate CSRF, unset `$_SESSION['cart'][$key]`, and redirect back to `cart/view.php`.

11. **`account/register.php` vs `account/login.php`: Mismatched Email Validation Rules**
    * **Problem:** `login.php` enforces `@gmail.com` via HTML pattern and `login_process.php` enforces `/^[a-zA-Z0-9._%+-]+@gmail\.com$/`. However, `register.php` allows any email format, allowing users to register accounts they cannot log into.
    * **Fix:** Enforce the same `@gmail.com` pattern and server-side validation in `register.php` and `register_process.php`.

12. **`account/register_process.php` (Lines 8–18): Uncaught Duplicate Key Exception on PHP 8.1+**
    * **Problem:** Duplicate email triggers `mysqli_sql_exception`. Without try/catch, the script crashes with a 500 error and leaks database error details.
    * **Fix:** Wrap in try/catch for `mysqli_sql_exception`, check for duplicate key (error code 1062), and display a user-friendly error.

13. **`account/dashboard.php` (Line 32): Unclosed `<a>` Tag**
    * **Problem:** Line 32 opens `<a href="orders.php" class="dash-btn">` without closing it before the next link, causing malformed HTML layout.
    * **Fix:** Add closing `</a>` tag.

14. **`page.php` (Lines 14–15): Broken `<link>` Tags**
    * **Problem:** `<link rel="stylesheet" href="cdnjs.cloudflare.com">` and `<link rel="stylesheet" href="fonts.googleapis.com">` are missing protocols and file paths, triggering 404 network errors.
    * **Fix:** Remove the broken links (valid Font Awesome and Google Fonts links already exist on lines 16–17).

15. **Hardcoded Port 3307 & Root Credentials in `config/db.php` (Lines 2–6)**
    * **Problem:** Hardcodes `port = 3307` and `pass = "root"`. The current environment runs MySQL on `127.0.0.1:3306`.
    * **Fix:** Make `config/db.php` read database configuration from `config/config.local.php` (fallback to environment variables or defaults).

16. **Hardcoded `/rivalsociety` URL Paths Throughout the Application**
    * **Problem:** Absolute paths like `/rivalsociety/assets/...` and `/rivalsociety/cart/...` are hardcoded across 15+ files, breaking portability across virtual hosts or subdirectories.
    * **Fix:** Define a central `BASE_URL` constant in configuration and prefix all web assets, endpoints, and redirects with `BASE_URL`.

---

### B. Security Vulnerabilities

1. **`admin/add_product.php` (Lines 51–56): Unrestricted File Upload (Remote Code Execution)**
   * **Problem:** Uploaded file names are concatenated without extension verification (`$_FILES["image_front"]["name"]`). An attacker could upload a `.php` file into `assets/images/` and execute arbitrary server code. Additionally, there are no file size checks or MIME type checks.
   * **Fix:** Validate MIME type using `finfo` / `mime_content_type` (allowing only image/jpeg, image/png, image/webp), enforce max file size (e.g. 5MB), generate cryptographically random file names (e.g. `bin2hex(random_bytes(16)) . '.' . $ext`), and sanitize extensions.

2. **`admin/delete_product.php` (Lines 5–11): State Modification via GET (CSRF via GET)**
   * **Problem:** Deletes products based on `$_GET['id']` without authentication method checks or CSRF protection. An attacker can trick an admin into clicking `<img src="/rivalsociety/admin/delete_product.php?id=1">` to wipe products.
   * **Fix:** Restrict deletion to POST requests with a valid CSRF token.

3. **Missing CSRF Protection on State-Changing Actions**
   * **Problem:** The following forms lack CSRF tokens:
     * `account/login.php`
     * `account/register.php`
     * `cart/add_to_cart.php`
     * `cart/update_cart.php`
     * `cart/remove_from_cart.php`
     * `admin/login.php`
     * `admin/add_product.php`
     * `admin/edit_product.php`
     * `admin/delete_product.php`
     * `admin/change_password.php`
   * **Fix:** Implement robust CSRF token generation in session and require/verify `csrf_token` on all POST handlers.

4. **`account/login_process.php` & `config/session.php`: Plaintext / Raw Remember-Me Token in Database**
   * **Problem:** `login_process.php` stores the raw 32-byte hex token directly in `cust_user.remember_token`, and `config/session.php` matches it directly. If the database is dumped, all remember tokens can be used immediately to hijack sessions.
   * **Fix:** Hash the token with `hash('sha256', $token)` before storing in the database. When verifying the cookie, compute `hash('sha256', $_COOKIE['remember_token'])` and look up by hash.

5. **Insecure Session Cookie & Missing Cookie Flags**
   * **Problem:** `config/session.php` starts sessions without configuring `session_set_cookie_params()`. `remember_token` cookies have `secure = false` hardcoded.
   * **Fix:** Configure `session_set_cookie_params` with `httponly = true`, `samesite = 'Lax'`, and set `secure = true` dynamically when HTTPS is active (`$_SERVER['HTTPS']` or server port 4443).

6. **Missing Session Regeneration on Login**
   * **Problem:** `account/login_process.php` and `admin/login_process.php` do not call `session_regenerate_id(true)` upon successful authentication, leaving users vulnerable to session fixation attacks.
   * **Fix:** Call `session_regenerate_id(true)` upon customer and admin login.

7. **No Login Rate Limiting / Brute-Force Throttling**
   * **Problem:** Neither `account/login_process.php` nor `admin/login_process.php` implements rate limiting or delay, enabling brute-force password guessing.
   * **Fix:** Implement session/IP attempt throttling with progressive sleep or temporary lockouts.

8. **`page.php` (Line 4), `product.php` (Line 4), `cart/view.php` (Line 4): Session ID / Debug Leaks**
   * **Problem:** `echo "<pre>SESSION ID: " . session_id() . "</pre>";` prints active session identifiers into HTML, exposing session tokens to shoulder surfers and screen captures.
   * **Fix:** Remove all session debug output.

9. **`payment/pay.php` (Line 123): Information Leakage in Exception Handling**
   * **Problem:** `die("PAYMENT ERROR: " . $e->getMessage());` leaks internal SQL / server errors to visitors.
   * **Fix:** Log detailed errors server-side and display a generic user-friendly error message.

10. **`payment/verify.php` & `payment/create_order.php`: Missing User Ownership Verification**
    * **Problem:** `create_order.php` and `verify.php` only query by order ID or Razorpay order ID without checking `user_id = $_SESSION['cust_user_id']`. A malicious user could tamper with order parameters or verify payments for other users' orders.
    * **Fix:** Ensure all order operations strictly check ownership against the authenticated customer's session ID.

11. **XSS Exposure in Admin and Customer Views**
    * **Problem:** Several values are printed unescaped:
      * `admin/edit_product.php` (L59–60): `$p['image_front']`, `$p['image_back']`
      * `account/orders.php` (L129): `$item['size']`, `$item['quantity']`
    * **Fix:** Wrap all dynamic values in `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.

12. **`payment/key.env`: Exposure Risk**
    * **Problem:** `payment/key.env` is in a web-accessible directory without `.htaccess` protection and could be served directly if requested via browser.
    * **Fix:** Add `.htaccess` denying web access to `*.env`, ensure `key.env` is in `.gitignore`, and use a secure PHP loader.

---

### C. Data Integrity

1. **`product.php` & `cart/add_to_cart.php`: Price Tampering from Client Request**
   * **Problem:** `product.php` sends product name, price, and image as hidden POST fields (`name`, `price`, `image`). An attacker can intercept the POST request and alter `price` to `1.00`.
   * **Fix:** `add_to_cart.php` must ONLY accept `product_id`, `size`, and `quantity`. Name, price, and image must be queried directly from the `products` table in the database.

2. **Cart Totals Not Verified Against Database During Checkout**
   * **Problem:** `cart/checkout.php` and `payment/pay.php` compute totals based entirely on session data without re-verifying whether prices or stock changed since items were added.
   * **Fix:** Server-side re-query of all cart items from the database during checkout to compute the true total and ensure prices are authentic.

3. **`payment/pay.php`: Missing Atomic Stock Decrement & Concurrency Safety**
   * **Problem:** Orders are created without checking or decrementing stock. If two users buy the last item simultaneously, both orders succeed, causing overselling.
   * **Fix:** Decrement stock inside the database transaction using an atomic query:
     `UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?`
     If affected rows == 0, rollback the transaction and fail gracefully.

4. **No Stock Restoration on Payment Failure**
   * **Problem:** If an order fails payment, any decremented stock is permanently lost if not restored.
   * **Fix:** When payment verification fails or a webhook reports payment failure, execute:
     `UPDATE products SET stock = stock + ? WHERE id = ?`
     for each item in the order.

5. **`payment/pay.php` (Lines 85–104): Missing `product_id` in `order_items`**
   * **Problem:** `pay.php` inserts into `order_items` without `product_id`. The database relationship between order items and products is lost, preventing stock restoration and sales tracking.
   * **Fix:** Include `product_id` in `INSERT INTO order_items (order_id, product_id, product_name, price, quantity, size) VALUES (?, ?, ?, ?, ?, ?)`.

6. **Missing Foreign Keys and Deletion Behavior on Products**
   * **Problem:** If an admin deletes a product, any related `order_items` without a proper FK or ON DELETE rule would either prevent deletion or cause cascading deletions of customer order histories.
   * **Fix:** Define FK `order_items.product_id` referencing `products(id)` with `ON DELETE SET NULL`, preserving order history while allowing product retirement.

---

### D. Dead Code & Code Cleanup

1. **`test.php`**
   * **Problem:** Contains debug code `echo "TEST START<br>"; die ("STOP");`.
   * **Fix:** Delete `test.php`.

2. **`assets/cart.js`**
   * **Problem:** Unused standalone script that references non-existent `/rivalsociety/cart/add.php`.
   * **Fix:** Delete `assets/cart.js`.

3. **`cart/place_order.php`**
   * **Problem:** Old placeholder script ("Payment integration coming soon") completely bypassed by the Razorpay payment flow.
   * **Fix:** Remove or redirect to `cart/checkout.php`.

4. **`data.sql`**
   * **Problem:** 0-byte empty file.
   * **Fix:** Replace or remove in favor of structured `database/schema.sql` and `database/seed.sql`.

5. **`README`**
   * **Problem:** Generic template about "Expandable Header Scroll Website" referencing non-existent files.
   * **Fix:** Rewrite README to describe Rival Society, environment setup, database installation, admin creation, Razorpay configuration, and testing.

---

## 3. Ambiguities & Design Decisions

* **Column Types & Sizes:**
  * `products.price`: `DECIMAL(10,2)` (safest for currency).
  * `orders.total`: `DECIMAL(10,2)`.
  * `order_items.price`: `DECIMAL(10,2)`.
  * `cust_user.remember_token`: `CHAR(64)` for SHA-256 hash.
  * `order_items.product_id`: `INT NULL` with `ON DELETE SET NULL`.
* **Cart Storage:** Session-based storage (`$_SESSION['cart']`) is maintained as the primary design. `cart/remove_from_cart.php` is aligned to manipulate session keys rather than a nonexistent DB table.
* **Razorpay Webhook:** Added `payment/webhook.php` to handle asynchronous `payment.captured` and `payment.failed` events with `X-Razorpay-Signature` validation.
