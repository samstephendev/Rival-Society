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
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Products | Rival Society</title>
<link rel="stylesheet" href="<?= url('/admin/style.css') ?>">
</head>
<body>

<div class="container">
    <h2>Edit Products</h2>

    <p><a href="<?= url('/admin/dashboard.php') ?>">← Back to Dashboard</a></p>

    <?php if ($message): ?>
        <p style="color: <?= $messageType === 'success' ? '#4cff4c' : '#ff4c4c' ?>;">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php foreach ($products as $p): ?>
    <form method="POST" style="background: #252525; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">

        <label style="color:#aaa; font-size:12px;">Product ID #<?= (int)$p['id'] ?></label>
        <input type="text" name="name" value="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" required>
        
        <label style="color:#aaa; font-size:12px;">Price (₹)</label>
        <input type="number" step="0.01" min="0.01" name="price" value="<?= number_format((float)$p['price'], 2, '.', '') ?>" required>
        
        <label style="color:#aaa; font-size:12px;">Stock</label>
        <input type="number" name="stock" min="0" value="<?= (int)$p['stock'] ?>" required>

        <label style="color:#aaa; font-size:12px;">Status</label>
        <select name="status">
            <option value="active" <?= $p['status'] === "active" ? "selected" : "" ?>>Active</option>
            <option value="inactive" <?= $p['status'] === "inactive" ? "selected" : "" ?>>Inactive</option>
        </select>

        <label style="color:#aaa; font-size:12px;">Badge</label>
        <select name="is_new">
            <option value="1" <?= !empty($p['is_new']) ? "selected" : "" ?>>New</option>
            <option value="0" <?= empty($p['is_new']) ? "selected" : "" ?>>Standard</option>
        </select>

        <div style="margin: 10px 0;">
            <img src="<?= url('/assets/images/' . htmlspecialchars($p['image_front'], ENT_QUOTES, 'UTF-8')) ?>" width="70" alt="Front" style="border-radius:4px; margin-right:8px;">
            <img src="<?= url('/assets/images/' . htmlspecialchars($p['image_back'], ENT_QUOTES, 'UTF-8')) ?>" width="70" alt="Back" style="border-radius:4px;">
        </div>

        <button type="submit">Update Product</button>
    </form>
    <?php endforeach; ?>

    <p><a href="<?= url('/admin/dashboard.php') ?>">← Back to Dashboard</a></p>
</div>

</body>
</html>
