# The Rival Society — Streetwear E-Commerce

A plain-PHP e-commerce site for streetwear, built on **mysqli**, **sessions**, and **Razorpay** for payments. No framework, no ORM.

---

## Requirements

| Software | Version |
|----------|---------|
| XAMPP (Apache + MySQL + PHP) | Apache 2.4+, MySQL 5.7+ / MariaDB 10.4+, PHP 8.1+ |
| Composer | Already vendored — only needed to update dependencies |
| Node.js | Optional — only used for JS syntax checks |

### Required PHP Extensions

`mysqli`, `curl`, `openssl`, `mbstring`, `fileinfo`, `session` (all enabled by default in XAMPP).

---

## Quick Start

### 1. Clone & place the project

```bash
git clone https://github.com/samstephendev/Rival-Society.git
```

Either symlink/copy to `C:\xampp\htdocs\rivalsociety` **or** add an Apache alias in `httpd.conf`:

```apache
Alias /rivalsociety "C:/Users/Sam Stephen/OneDrive/Documents/GitHub/Rival-Society"
<Directory "C:/Users/Sam Stephen/OneDrive/Documents/GitHub/Rival-Society">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

### 2. Configure

Copy the config template:

```bash
cp config/config.example.php config/config.local.php
```

Edit `config/config.local.php` with your local MySQL credentials:

```php
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,        // default XAMPP MySQL port
        'user' => 'root',
        'pass' => 'root',      // your MySQL root password
        'name' => 'shop_db',
    ],
    'app' => [
        'base_url' => '/rivalsociety',
    ],
];
```

### 3. Install the database

```bash
php database/install.php
```

This script is **idempotent** — safe to run multiple times. It will:

- Create the `shop_db` database if it doesn't exist
- Create all tables (`cust_user`, `users`, `products`, `orders`, `order_items`, `order_billing`)
- Seed 4 sample Naruto-series t-shirt products
- Create demo customer and admin accounts with runtime `password_hash()`
- Verify each account with `password_verify()` and print PASS/FAIL
- Print a table summary with row counts

### 4. Configure Razorpay (optional for local testing)

Edit `payment/key.env`:

```env
RAZORPAY_KEY_ID="rzp_test_XXXXXXXXXXXXXX"
RAZORPAY_KEY_SECRET="XXXXXXXXXXXXXXXXXXXXXX"
RAZORPAY_WEBHOOK_SECRET="XXXXXXXXXXXXXXXXXXXXXX"
```

Get test credentials from [Razorpay Dashboard → Settings → API Keys](https://dashboard.razorpay.com/app/keys).

> **Note:** If you leave the placeholder keys, the payment flow will use a mock order ID for local testing. Real Razorpay integration requires valid test/live keys.

### 5. Access the site

| URL | Description |
|-----|-------------|
| `http://localhost:8080/rivalsociety/page.php` | Homepage / Store |
| `https://localhost:4443/rivalsociety/page.php` | HTTPS (if configured) |
| `http://localhost:8080/rivalsociety/admin/` | Admin panel |

---

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Customer | `demo@gmail.com` | `demo` |
| Admin | `admin@gmail.com` | `demo` |

> **Important:** Customer registration enforces `@gmail.com` email addresses.

---

## Project Structure

```
Rival-Society/
├── page.php              # Homepage with store section
├── product.php           # Individual product detail page
├── account/              # Customer auth: login, register, dashboard, orders, logout
├── admin/                # Admin panel: login, dashboard, add/edit/delete products, change password
├── cart/                 # Shopping cart: add, view, update qty, remove, checkout
├── payment/              # Razorpay flow: pay, create_order, verify, success, failed, webhook
├── config/               # Session, DB connection, auth guards, Razorpay key loader
│   ├── config.example.php
│   ├── config.local.php  # (git-ignored)
│   ├── db.php
│   ├── session.php
│   ├── auth.php          # Customer auth guard
│   └── razorpay.php      # Key loader
├── database/             # Schema, seed data, CLI installer, admin creator
│   ├── schema.sql
│   ├── seed.sql
│   ├── install.php
│   └── create_admin.php
├── assets/               # CSS, JS, images
│   ├── style.css
│   ├── account.css
│   ├── script.js
│   └── images/
└── vendor/               # Composer dependencies (Razorpay SDK)
```

---

## Payment Flow

1. **Checkout** → `cart/checkout.php` verifies stock & prices from DB
2. **Pay** → `payment/pay.php` creates the order in a DB transaction with atomic stock decrement
3. **Razorpay** → `payment/create_order.php` creates a Razorpay order and opens the checkout modal
4. **Verify** → `payment/verify.php` validates the Razorpay signature server-side (JSON response)
5. **Success/Fail** → `payment/success.php` displays the real payment status from the DB
6. **Webhook** → `payment/webhook.php` handles async Razorpay notifications (marks orders paid/failed, restores stock on failure)

---

## Security Features

- CSRF tokens on every state-changing form
- Prepared statements for all database queries
- `htmlspecialchars()` on all output
- Session cookie: `httponly`, `samesite=Lax`, `secure` when HTTPS
- `session_regenerate_id(true)` on login
- SHA-256 hashed remember-me tokens
- Login rate limiting (5 attempts / 60 seconds)
- File upload validation (MIME type, size limit, random filenames)
- `.htaccess` blocks direct access to `.env`, `.sql`, and config files
- Admin pages use separate `admin/auth.php` (checks `admin_id` session)
- Cart prices always read from the database, never from POST

---

## Create Additional Admin Accounts

```bash
php database/create_admin.php admin@example.com yourpassword
```

Or run interactively (it will prompt):

```bash
php database/create_admin.php
```

---

## Author

**Sam Stephen** — [GitHub](https://github.com/samstephendev)

---

## License

This project is open-source and available under the MIT License.
