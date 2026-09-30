# Rival Society — Frontend & UI Audit

**Role:** Senior Frontend / UI Engineer  
**Date:** 2026-09-30  
**Stack:** Plain PHP, HTML5, CSS3, Vanilla JS, Font Awesome 6.5, Google Fonts Montserrat.

---

## 1. Inventory of Preserved Hooks (Hard Contract)

The following IDs, classes, form attributes, input names, data attributes, and PHP expressions are functional anchors required by backend scripts, sessions, and JavaScript handlers. They must **never** be renamed, removed, or disrupted:

| Page / Component | Preserved Functional Hooks (IDs, Classes, Names, Attributes) | Dependent Logic |
|------------------|-------------------------------------------------------------|-----------------|
| **`page.php`** | `#header`, `.collapsed`, `.expanded`, `.bar`, `.fixed`, `#nav-wrapper`, `.nav-item[data-target]`, `.active`, `#home`, `#store`, `#about`, `#Join`, `#contact`, `.product-card`, `.badge.new`, `.image-box`, `.img-1`, `.img-2`, `.price`, `.stock.in`, `.stock.out`, `.buy-btn`, `.floating-icons`, `.floating-actions`, `.float-btn` | `assets/script.js` (scroll, navbar sticky, active links, smooth scroll), dynamic catalog rendering. |
| **`product.php`** | `<form action="/rivalsociety/cart/add_to_cart.php" method="POST" class="product-form">`, hidden `name="csrf_token"`, hidden `name="product_id"`, radios `name="size"` with values `S,M,L,XL,XXL` and IDs `size-S...size-XXL`, `name="quantity" id="quantity"`, `.add-cart-btn`, `.stock.in`, `.stock.out` | `cart/add_to_cart.php` CSRF, product ID, size validation, and quantity bounds. |
| **`cart/view.php`** | `.cart-section`, `.empty-cart`, `.cart-table`, `.cart-img`, `.qty-box`, `.qty-btn.minus[data-key]`, `.qty-value`, `.qty-btn.plus[data-key]`, `<form action="/rivalsociety/cart/remove_from_cart.php" method="POST">`, hidden `csrf_token`, hidden `cart_key`, `.remove-btn`, `.cart-total`, `.cart-actions`, `.btn.primary`, `.btn` | Inline JS AJAX quantity updates (`cart/update_cart.php`), item removal (`cart/remove_from_cart.php`), checkout link. |
| **`cart/checkout.php`** | `<form action="/rivalsociety/payment/pay.php" method="POST">`, hidden `name="csrf_token"`, inputs with `name="full_name"`, `name="email"`, `name="phone"`, `name="address"`, `name="city"`, `name="state"`, `name="pincode"`, `.checkout-btn`, `.btn.primary` | `payment/pay.php` transactional checkout and billing record insertion. |
| **`payment/create_order.php`** | `#rzp-button.btn.primary`, `<script src="https://checkout.razorpay.com/v1/checkout.js">`, inline JS handlers sending `razorpay_payment_id`, `razorpay_order_id`, `razorpay_signature`, `csrf_token` to `payment/verify.php` | Razorpay Checkout SDK initialization and AJAX signature verification. |
| **`payment/success.php`** | `.success-section`, `.success-card`, `.status-paid`, `.status-pending`, `.status-failed`, `.order-id`, `.status-badge`, `.badge-paid`, `.badge-pending`, `.badge-failed`, `.success-actions`, `.primary`, `.secondary` | Real-time order payment status display from DB. |
| **`payment/failed.php`** | `.checkout-section`, `.checkout-card.error`, `.btn.primary`, `.btn.secondary` | Error message presentation and retry navigation. |
| **`account/login.php`** | `<form action="login_process.php" method="POST">`, hidden `name="csrf_token"`, `name="email"`, `name="password"`, `name="remember"`, `.account-btn`, `.account-card` | `account/login_process.php` authentication and rate limiting. |
| **`account/register.php`** | `<form action="register_process.php" method="POST">`, hidden `name="csrf_token"`, `name="name"`, `name="email" pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$"`, `name="password"`, `.account-btn`, `.account-card` | `account/register_process.php` customer creation. |
| **`account/dashboard.php`**| `.account-card.dashboard`, `.dash-btn` links | Customer portal navigation. |
| **`account/orders.php`** | `.orders-section`, `.order-card`, `.order-header`, `.order-id`, `.order-status`, `.status-pending`, `.status-paid`, `.status-failed`, `.order-items`, `.order-item`, `.order-total`, `.back-link` | Customer order history listing. |
| **`admin/login.php`** | `<form method="POST" action="login_process.php">`, hidden `name="csrf_token"`, `name="email"`, `name="password"`, button `type="submit"` | `admin/login_process.php` admin authentication. |
| **`admin/dashboard.php`** | Links to `add_product.php`, `edit_product.php`, `delete_product.php`, `change_password.php`, `logout.php` | Admin navigation. |
| **`admin/add_product.php`**| `<form method="POST" enctype="multipart/form-data">`, hidden `name="csrf_token"`, inputs `name="name"`, `name="price"`, `name="stock"`, `name="status"`, `name="is_new"`, file inputs `name="image_front"`, `name="image_back"` | Product creation and file upload processing. |
| **`admin/edit_product.php`**| `<form method="POST">`, hidden `name="csrf_token"`, hidden `name="id"`, inputs `name="name"`, `name="price"`, `name="stock"`, `name="status"`, `name="is_new"` | Product update handling. |
| **`admin/delete_product.php`**| `<form method="POST">`, hidden `name="csrf_token"`, hidden `name="id"`, button `.del-btn` | CSRF-protected product deletion. |
| **`admin/change_password.php`**| `<form method="POST">`, hidden `name="csrf_token"`, inputs `name="current_password"`, `name="new_password"`, `name="confirm_password"` | Password update handling. |

---

## 2. Page-by-Page Visual & Mobile Defect Analysis

### `page.php` (Storefront & Sections)
* **Problems:**
  * Uses deprecated HTML `<center>` tags which produce uneven horizontal alignment.
  * Mobile tap targets on `.nav-item` are inconsistent and the fixed `.bar` can wrap or cause horizontal overflow on 320px screens.
  * Floating action buttons (`.floating-icons`) can cover product cards or the store grid on small touch devices.
  * Hero image height calculation and collapse logic create jarring shifts on mobile browser address bar resize (`100vh` bug instead of `dvh`).
  * Product card image swap (`.img-1` / `.img-2`) relies purely on `:hover`, which does not trigger cleanly on touch devices.
  * Section titles lack consistent typography and responsive scale (`clamp()`).
  * Contact section is empty text with no structured contact cards or quick actions.
  * Missing global footer with copyright and store info.

### `product.php` (Product Detail)
* **Problems:**
  * Grid splits into two equal columns even on narrow screens unless forced by media query; images stack awkwardly with `<center>` tags.
  * Size selector radio buttons have small tap targets (< 44px) on mobile and lack clear focus-visible outlines.
  * Quantity input allows manual number entry without prominent stepper buttons on touch.
  * Out-of-stock and login states are plain inline text blocks without styled alert banners.
  * Image switching on touch requires scrolling past large static images.

### `cart/view.php` (Cart Table & Summary)
* **Problems:**
  * Uses standard HTML `<table>` which causes horizontal scrolling and overflow on mobile devices under 768px unless reformatted into mobile cards.
  * Quantity +/- buttons have touch targets under 44px (`width: 32px; height: 32px`).
  * "Remove" button is small red text without adequate touch padding.
  * Grand total and checkout actions are pushed to the bottom and require extensive scrolling if cart has multiple items.

### `cart/checkout.php` (Billing & Order Summary)
* **Problems:**
  * Form inputs lack explicit mobile input optimizations (`inputmode="numeric"` for pincode, `inputmode="tel"` for phone, `autocomplete` attributes).
  * Inputs on mobile devices under 480px drop below 16px font-size, which triggers automatic Safari/Chrome iOS zoom.
  * Summary total box is plain text without clear visual hierarchy.

### `account/login.php` & `account/register.php` (Auth Pages)
* **Problems:**
  * Hardcoded pink/neon colors (`#ff0055`, `#ff00ff`) clash with the streetwear aesthetic (black, white, neon red/cyan accents).
  * Inputs have inconsistent height across browsers and lack subtle focus ring styling.
  * Remember-me checkbox tap target is small.

### `account/orders.php` & `account/dashboard.php`
* **Problems:**
  * `account/orders.php` contains a large duplicated inline `<style>` block (~60 lines) rather than using an external stylesheet.
  * Order items list on mobile lacks clear visual separation between items, sizes, and quantities.
  * Status badges (`status-paid`, `status-pending`, `status-failed`) use inconsistent paddings.

### `payment/create_order.php`, `payment/success.php`, `payment/failed.php`
* **Problems:**
  * `success.php` contains a large inline `<style>` block (~75 lines).
  * `create_order.php` has a plain modal trigger button that lacks responsive card styling.
  * `failed.php` uses minimal uncentered layout.

### `admin/*.php` (Admin Pages)
* **Problems:**
  * `admin/style.css` has raw red text (`rgb(255, 0, 0)`) on dark gray background (`#353535e6`), violating WCAG AA accessibility contrast ratios.
  * Inputs and buttons lack responsive constraints and visual feedback.
  * Delete buttons lack clear destructive visual identity.
  * Mobile viewports on phone/tablet are unoptimized, causing squished forms.

---

## 3. Plan for Unified Design System (`assets/base.css`)

1. **Tokens:**
   * Surfaces: `--bg-primary: #0a0a0c`, `--bg-surface: #141417`, `--bg-elevated: #1e1e24`, `--border: #2a2a32`.
   * Brand Accents: `--accent-red: #ff3344`, `--accent-cyan: #00f0ff`, `--accent-yellow: #ffcc00`, `--text-primary: #f5f5f7`, `--text-secondary: #9a9aa8`.
   * Typography: Fluid headings via `clamp()`, Google Fonts Montserrat (`font-display: swap`).
   * Spacing & Sizing: Minimum tap target `44px`, form inputs `font-size: 16px` to prevent iOS zoom.
2. **Consolidation:**
   * Extract inline `<style>` blocks from `account/orders.php` and `payment/success.php` into modular CSS.
   * Load `assets/base.css` before page-specific styles on every page.
3. **Responsive Breakpoints:**
   * Mobile-first (`min-width: 480px`, `768px`, `1024px`, `1440px`).
   * Safe-area insets (`env(safe-area-inset-bottom)`).
