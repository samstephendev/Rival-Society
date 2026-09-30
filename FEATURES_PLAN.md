# FEATURES_PLAN.md — Rival Society Feature Implementation Plan

## Overview
This document outlines the implementation plan for the 6 requested features for Rival Society without breaking existing functionality or violating the strict git/code contracts.

---

## Existing Hooks & Hard Contract to Preserve
- **Classes & IDs**: `#header`, `.bar`, `.nav-item[data-target]`, `#nav-wrapper`, `#home`, `#store`, `#about`, `#Join`, `#contact`, `.collapsed`, `.fixed`, `.active`, `.expanded`, `.qty-btn`, `.plus`, `.minus`, `[data-key]`, `.qty-value`, `.product-card`, `.badge.new`, `.stock.in`, `.stock.out`, `.status-pending`, `.status-paid`, `.status-failed`
- **Form Actions & Input Names**:
  - `account/login.php`: `name="email"`, `name="password"`, `name="remember"`
  - `account/register.php`: `name="name"`, `name="email"`, `name="password"`
  - `product.php`: `name="product_id"`, `name="size"` (values: S, M, L, XL, XXL), `name="quantity"`
  - `cart/checkout.php`: `name="full_name"`, `name="email"`, `name="phone"`, `name="address"`, `name="city"`, `name="state"`, `name="pincode"`
  - `admin/login.php`: `name="email"`, `name="password"`
  - `admin/change_password.php`: `name="current_password"`, `name="new_password"`, `name="confirm_password"`
  - `admin/edit_product.php`: `name="id"`, `name="name"`, `name="price"`, `name="stock"`, `name="status"`, `name="is_new"`
  - Hidden inputs: `csrf_token`, `product_id`, `key`, `cart_id`, `id`
- **Security**: Prices derived from database, server-side totals recalculation, CSRF tokens, atomic stock decrement, signature verification, session order authorization.
- **Git Rule**: Read-only git during development; no commit until explicitly commanded.

---

## Feature 1: Notification When Adding Without a Size

### Description
Provide a toast/notice system with accessible `aria-live="polite"` region, ~4s auto-dismiss, close button, stacking, mobile responsiveness, and zero layout shift. If user submits `product.php` without choosing a size, prevent form submission, display "Please select a size", highlight the size radio group, and move focus to the first size option. If submitted directly to `cart/add_to_cart.php` without size, redirect back with `?msg=size` and render the toast.

### Files to Touch
1. `assets/toast.js` (NEW): Standalone toast manager (`window.showToast(message, type, duration)`).
2. `assets/base.css`: Add styles for `#toast-container`, `.toast`, `.toast-error`, `.toast-success`, `.toast-close`, plus animation and `.size-group-highlight` (with `@media (prefers-reduced-motion)` safe fallback).
3. `product.php`:
   - Include `assets/toast.js`.
   - Add client-side validation on form submit: if no size radio is checked, block submission, invoke `showToast('Please select a size', 'error')`, add highlight class to `.size-buttons`, focus `#size-S`. Clear highlight when any size is clicked.
   - Read `$_GET['msg']` against a safe whitelist (`size`, `stock`, `qty`, `login`, `csrf`) and trigger `showToast(...)`.
4. `cart/add_to_cart.php`:
   - On missing/invalid size, redirect to `product.php?id=<id>&msg=size`.
   - On out-of-stock, redirect to `product.php?id=<id>&msg=stock`.
   - On invalid quantity, redirect to `product.php?id=<id>&msg=qty`.
   - On success, redirect to `cart/view.php?msg=added`.
5. `cart/view.php`:
   - Include `assets/toast.js` and show success toast if `?msg=added`.

### Hooks Preserved
- `name="size"` radio buttons with values `S, M, L, XL, XXL`.
- Form action `cart/add_to_cart.php`, method `POST`, `csrf_field()`.

---

## Feature 2: Google Pay via UPI in Razorpay Checkout

### Description
Configure Razorpay Checkout to display Google Pay / UPI prominently using supported Razorpay standard checkout configurations (`config.display.blocks`). Display "Test mode: use success@razorpay" notice on `cart/checkout.php` when the active key begins with `rzp_test_`.

### Files to Touch
1. `payment/create_order.php`:
   - Configure Razorpay Checkout `options.config.display` blocks with UPI preferred.
   - Support `pref` parameter from checkout.
2. `cart/checkout.php`:
   - If `str_starts_with($keyId, 'rzp_test_')`, show a helper badge: "Test mode: use success@razorpay for UPI payments".

### Hooks Preserved
- Razorpay Checkout script and standard response handler.
- Payment verification endpoint `payment/verify.php`.

---

## Feature 3: Clickable Product Photos & Names Open Product Details

### Description
In `page.php` store grid, wrap product image and title in an `<a>` linking to `product.php?id=<id>`. Keep the front/back hover swap and make touch tap open details smoothly. In `cart/view.php`, link cart item image and product name to `product.php?id=<id>`.

### Files to Touch
1. `page.php`:
   - Wrap `.image-box` in `<a href="<?= url('/product.php?id=' . (int)$row['id']) ?>" class="product-img-link" aria-label="View details for <?= htmlspecialchars(...) ?>"><div class="image-box">...</div></a>`.
   - Wrap product `<h3>` in `<a href="<?= url('/product.php?id=' . (int)$row['id']) ?>" class="product-title-link">...</a>`.
   - Keep desktop hover swap via CSS (`.img-1` and `.img-2`) intact.
2. `cart/view.php`:
   - In `<td>` for product: wrap `<img>` and `<strong>` in an anchor `<a href="<?= url('/product.php?id=' . (int)$item['id']) ?>" class="cart-product-link">`.
3. `assets/style.css`:
   - Style `.product-img-link` and `.product-title-link` with `cursor: pointer; text-decoration: none; color: inherit; display: block;`.

### Hooks Preserved
- `.product-card`, `.image-box`, `.img-1`, `.img-2`, `.buy-btn`.
- Cart table structure and `.qty-btn`, `[data-key]`, `.qty-value`.

---

## Feature 4: Remember Billing Details (Cache) on Checkout

### Description
1. Server-side prefill: on `cart/checkout.php`, query the user's most recent `order_billing` row (`orders JOIN order_billing WHERE orders.user_id = ? ORDER BY orders.id DESC LIMIT 1`). If none, use session account name & email.
2. Client-side draft: write non-sensitive fields to `sessionStorage` on `input` events; restore on reload or validation redirect.
3. Add "Clear saved details" button to reset form and draft.
4. Add visible inline validation errors for 10-digit Indian phone (`^[6-9]\d{9}$` or `^\d{10}$`) and 6-digit postal code (`^\d{6}$`). Display server errors (`$_SESSION['checkout_error']` and `$_GET['error']`).

### Files to Touch
1. `cart/checkout.php`:
   - Fetch last billing data from DB for authenticated user.
   - Display `$_SESSION['checkout_error']` if set (and unset it).
   - Add "Clear saved details" button.
   - Add inline error containers for phone and pincode.
   - Add script for `sessionStorage` draft persistence and validation feedback.
2. `payment/pay.php`:
   - Clear checkout sessionStorage signal upon successful order creation.

### Hooks Preserved
- All billing field names: `full_name`, `email`, `phone`, `address`, `city`, `state`, `pincode`.
- Form action `payment/pay.php`, CSRF token verification.

---

## Feature 5: Show/Hide Password

### Description
Add an eye toggle to every password input on:
- `account/login.php`
- `account/register.php`
- `admin/login.php`
- `admin/change_password.php`
Implemented as progressive enhancement in `assets/password-toggle.js` + CSS. Injects an accessible `<button type="button">` with inline SVG icons (eye & eye-slash), `aria-label`, and `aria-pressed`. Automatically reverts input to `type="password"` on form submit and `pageshow`.

### Files to Touch
1. `assets/password-toggle.js` (NEW):
   - Finds `input[type="password"]` and wraps in `.password-field-wrapper`.
   - Injects toggle button with accessible SVG icons and click/keyboard support.
   - Resets to `password` on form submit and `pageshow`.
2. `assets/base.css`:
   - Add styles for `.password-field-wrapper`, `.password-toggle-btn`.
3. `account/login.php`, `account/register.php`, `admin/login.php`, `admin/change_password.php`:
   - Include `<script src="<?= url('/assets/password-toggle.js') ?>" defer></script>`.

### Hooks Preserved
- Input IDs and names: `login-password`, `reg-password`, `admin-pass`, `cur-pass`, `new-pass`, `conf-pass`.
- Autocomplete and required attributes.

---

## Feature 6: Payment Options & Method Recording

### Description
1. In `cart/checkout.php`, add a "Payment Method" selection UI with options:
   - `all` (All payment methods - Default)
   - `upi` (Google Pay / UPI)
   - `card` (Credit / Debit Cards)
   - `netbanking` (Netbanking)
   - `wallet` (Wallets)
2. Submit as `payment_pref` to `payment/pay.php` (validated against whitelist).
3. Store in session: `$_SESSION['payment_pref']`.
4. In `payment/create_order.php`, configure Razorpay Checkout `config.display` blocks and preferred sequence according to `$_SESSION['payment_pref']`.
5. Database Migration:
   - Add nullable column `payment_method VARCHAR(20) NULL DEFAULT NULL` to `orders` table.
   - Update `database/schema.sql`, `database/install.php`.
6. In `payment/verify.php`:
   - After signature verification, query Razorpay API `$api->payment->fetch($razorpayPaymentId)`.
   - Verify `order_id` and `amount` match.
   - Extract `method` (`upi`, `card`, `netbanking`, `wallet`).
   - Store in `orders.payment_method`.
   - If API fetch fails, still mark order paid and log error without secrets.
7. In `payment/success.php` and `account/orders.php`:
   - Display formatted payment method badge (e.g. "Google Pay / UPI", "Card", "Netbanking").

### Files to Touch
1. `database/schema.sql` & `database/install.php`: Add `payment_method` column.
2. `cart/checkout.php`: Add payment options radio group.
3. `payment/pay.php`: Validate and store `payment_pref`.
4. `payment/create_order.php`: Pass preference into Razorpay Checkout display options.
5. `payment/verify.php`: Fetch payment method from Razorpay API and update `orders`.
6. `payment/success.php`: Display payment method.
7. `account/orders.php`: Display payment method.

---

## Admin Layout Fix (User-Reported Defect)
In `admin/style.css`, prevent `<form class="product-row">` on `admin/edit_product.php` from collapsing into a single horizontal row on wide screens. Ensure all inputs (Product Name, Price, Stock, Status, Badge) have full width and visibility.
