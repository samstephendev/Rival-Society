<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify()) {
        $message = "Invalid CSRF token.";
        $messageType = "error";
    } else {
        $id = (int)($_POST["id"] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();

                $message = "Product #{$id} was successfully deleted.";
                $messageType = "success";
            } catch (mysqli_sql_exception $e) {
                error_log("Delete product error: " . $e->getMessage());
                $message = "Unable to delete product due to a database error.";
                $messageType = "error";
            }
        }
    }
}

$products = [];
try {
    $res = $conn->query("SELECT id, name, price, stock, status FROM products ORDER BY id DESC");
    while ($row = $res->fetch_assoc()) {
        $products[] = $row;
    }
} catch (mysqli_sql_exception $e) {
    error_log("Fetch products for deletion error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Products | Rival Society</title>
<?php rs_critical_css(); ?>
<link rel="stylesheet" href="<?= asset_url('/assets/base.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/admin/style.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>

<main class="container">
    <h2>Delete Catalog Products</h2>

    <p><a href="<?= url('/admin/dashboard.php') ?>" class="back-link">← Back to Dashboard</a></p>

    <?php if ($message): ?>
        <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <p style="color: var(--rs-text-muted);">No products currently in the database.</p>
    <?php endif; ?>

    <?php foreach ($products as $p): ?>
    <div class="product-row">
        <div>
            <strong><?= htmlspecialchars($p["name"], ENT_QUOTES, 'UTF-8') ?></strong>
            <span style="display: block; margin-top: 4px;">₹<?= number_format((float)$p["price"], 2) ?> &bull; Stock: <?= (int)$p["stock"] ?> units</span>
        </div>

        <form method="POST" style="margin: 0; display: inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$p["id"] ?>">
            <button type="submit" class="del-btn" onclick="return confirm('Are you sure you want to permanently delete this product?');" aria-label="Permanently delete <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-trash-can" style="margin-right: 6px;"></i> Delete Product
            </button>
        </form>
    </div>
    <?php endforeach; ?>

    <p style="margin-top: 24px;">
        <a href="<?= url('/admin/dashboard.php') ?>" class="back-link">← Back to Dashboard</a>
    </p>
</main>

</body>
</html>
