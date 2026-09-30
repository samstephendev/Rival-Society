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
        $id     = (int)($_POST["id"] ?? 0);
        $name   = trim($_POST["name"] ?? "");
        $price  = (float)($_POST["price"] ?? 0);
        $stock  = max(0, (int)($_POST["stock"] ?? 0));
        $status = in_array($_POST["status"] ?? '', ['active', 'inactive'], true) ? $_POST["status"] : 'active';
        $is_new = !empty($_POST["is_new"]) ? 1 : 0;

        if ($id > 0 && $name !== '' && $price > 0) {
            try {
                $stmt = $conn->prepare(
                    "UPDATE products 
                     SET name = ?, price = ?, stock = ?, status = ?, is_new = ?
                     WHERE id = ?"
                );
                $stmt->bind_param("sdisii", $name, $price, $stock, $status, $is_new, $id);
                $stmt->execute();

                $message = "Product #{$id} updated successfully!";
                $messageType = "success";
            } catch (mysqli_sql_exception $e) {
                error_log("Update product error: " . $e->getMessage());
                $message = "Database error occurred while updating product.";
                $messageType = "error";
            }
        } else {
            $message = "Invalid input values for product update.";
            $messageType = "error";
        }
    }
}

$products = [];
try {
    $res = $conn->query("SELECT * FROM products ORDER BY id DESC");
    while ($row = $res->fetch_assoc()) {
        $products[] = $row;
    }
} catch (mysqli_sql_exception $e) {
    error_log("Fetch products error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Products | Rival Society</title>
<?php rs_critical_css(); ?>
<link rel="stylesheet" href="<?= asset_url('/assets/base.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/admin/style.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>

<main class="container">
    <h2>Edit Catalog Products</h2>

    <p><a href="<?= url('/admin/dashboard.php') ?>" class="back-link">← Back to Dashboard</a></p>

    <?php if ($message): ?>
        <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php foreach ($products as $p): ?>
    <form method="POST" class="product-row">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">

        <div class="form-field">
            <label for="edit_name_<?= (int)$p['id'] ?>" style="color: var(--rs-cyan);">Product ID #<?= (int)$p['id'] ?> — Name</label>
            <input id="edit_name_<?= (int)$p['id'] ?>" type="text" name="name" value="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="form-grid">
            <div class="form-field">
                <label for="edit_price_<?= (int)$p['id'] ?>">Price (₹ INR)</label>
                <input id="edit_price_<?= (int)$p['id'] ?>" type="number" step="0.01" min="0.01" name="price" value="<?= number_format((float)$p['price'], 2, '.', '') ?>" required>
            </div>
            
            <div class="form-field">
                <label for="edit_stock_<?= (int)$p['id'] ?>">Inventory Stock</label>
                <input id="edit_stock_<?= (int)$p['id'] ?>" type="number" name="stock" min="0" value="<?= (int)$p['stock'] ?>" required>
            </div>

            <div class="form-field">
                <label for="edit_status_<?= (int)$p['id'] ?>">Status</label>
                <select id="edit_status_<?= (int)$p['id'] ?>" name="status">
                    <option value="active" <?= $p['status'] === "active" ? "selected" : "" ?>>Active</option>
                    <option value="inactive" <?= $p['status'] === "inactive" ? "selected" : "" ?>>Inactive</option>
                </select>
            </div>

            <div class="form-field">
                <label for="edit_is_new_<?= (int)$p['id'] ?>">Badge</label>
                <select id="edit_is_new_<?= (int)$p['id'] ?>" name="is_new">
                    <option value="1" <?= !empty($p['is_new']) ? "selected" : "" ?>>New</option>
                    <option value="0" <?= empty($p['is_new']) ? "selected" : "" ?>>Standard</option>
                </select>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 16px; margin: 6px 0;">
            <span style="font-size: var(--font-xs); color: var(--rs-text-muted); font-weight: 700; text-transform: uppercase;">Previews:</span>
            <img src="<?= url('/assets/images/' . htmlspecialchars($p['image_front'], ENT_QUOTES, 'UTF-8')) ?>" width="72" height="72" alt="Front Preview" style="border-radius: var(--radius-md); object-fit: cover; border: 1px solid var(--rs-border);">
            <img src="<?= url('/assets/images/' . htmlspecialchars($p['image_back'], ENT_QUOTES, 'UTF-8')) ?>" width="72" height="72" alt="Back Preview" style="border-radius: var(--radius-md); object-fit: cover; border: 1px solid var(--rs-border);">
        </div>

        <button type="submit" class="btn primary">
            <i class="fas fa-check" style="margin-right: 8px;"></i> Update Product #<?= (int)$p['id'] ?>
        </button>
    </form>
    <?php endforeach; ?>

    <p style="margin-top: 24px;"><a href="<?= url('/admin/dashboard.php') ?>" class="back-link">← Back to Dashboard</a></p>
</main>

</body>
</html>
