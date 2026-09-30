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
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Products | Rival Society</title>
<link rel="stylesheet" href="<?= url('/admin/style.css') ?>">
<style>
.product-row {
    background: #252525;
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.del-btn {
    background: #c92a2a;
    color: #fff;
    border: none;
    padding: 8px 14px;
    border-radius: 4px;
    cursor: pointer;
    width: auto;
    font-size: 13px;
    margin: 0;
}
.del-btn:hover {
    background: #a61e1e;
}
</style>
</head>
<body>

<div class="container">
    <h2>Delete Products</h2>

    <p><a href="<?= url('/admin/dashboard.php') ?>">← Back to Dashboard</a></p>

    <?php if ($message): ?>
        <p style="color: <?= $messageType === 'success' ? '#4cff4c' : '#ff4c4c' ?>;">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <p style="color: #aaa;">No products in database.</p>
    <?php endif; ?>

    <?php foreach ($products as $p): ?>
    <div class="product-row">
        <div>
            <strong style="color:#fff;"><?= htmlspecialchars($p["name"], ENT_QUOTES, 'UTF-8') ?></strong>
            <span style="color:#aaa; font-size:13px; margin-left: 10px;">(₹<?= number_format((float)$p["price"], 2) ?>, Stock: <?= (int)$p["stock"] ?>)</span>
        </div>

        <form method="POST" style="margin: 0; display: inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$p["id"] ?>">
            <button type="submit" class="del-btn" onclick="return confirm('Are you sure you want to permanently delete this product?');">
                Delete
            </button>
        </form>
    </div>
    <?php endforeach; ?>

    <p style="margin-top: 20px;">
        <a href="<?= url('/admin/dashboard.php') ?>">← Back to Dashboard</a>
    </p>
</div>

</body>
</html>
