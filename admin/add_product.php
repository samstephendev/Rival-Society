<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$message = "";
$messageType = "";

function process_uploaded_image(array $file, string $targetDir): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    // 5MB limit
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("File exceeds maximum allowed size of 5MB.");
    }

    // MIME verification
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowedMimes[$mime])) {
        throw new Exception("Invalid image type ({$mime}). Only JPG, PNG, and WebP are allowed.");
    }

    $ext = $allowedMimes[$mime];
    $randomName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $randomName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception("Failed to move uploaded file.");
    }

    return $randomName;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify()) {
        $message = "Invalid CSRF token.";
        $messageType = "error";
    } else {
        $name   = trim($_POST["name"] ?? "");
        $price  = (float)($_POST["price"] ?? 0);
        $stock  = max(0, (int)($_POST["stock"] ?? 0));
        $status = in_array($_POST["status"] ?? '', ['active', 'inactive'], true) ? $_POST["status"] : 'active';
        $is_new = !empty($_POST["is_new"]) ? 1 : 0;

        if ($name === '' || $price <= 0) {
            $message = "Please provide a valid product name and positive price.";
            $messageType = "error";
        } else {
            try {
                $targetDir = __DIR__ . '/../assets/images';
                $front = process_uploaded_image($_FILES["image_front"], $targetDir);
                $back  = process_uploaded_image($_FILES["image_back"], $targetDir);

                if (!$front || !$back) {
                    $message = "Both front and back images are required.";
                    $messageType = "error";
                } else {
                    $stmt = $conn->prepare(
                        "INSERT INTO products (name, price, image_front, image_back, stock, status, is_new)
                         VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param("sdssisi", $name, $price, $front, $back, $stock, $status, $is_new);
                    $stmt->execute();

                    $message = "Product '{$name}' added successfully!";
                    $messageType = "success";
                }
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
                $messageType = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Product | Rival Society</title>
<link rel="stylesheet" href="<?= url('/admin/style.css') ?>">
</head>
<body>

<div class="container">
    <h2>Add Product</h2>

    <?php if ($message): ?>
        <p style="color: <?= $messageType === 'success' ? '#4cff4c' : '#ff4c4c' ?>;">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="text" name="name" placeholder="Product Name" required maxlength="255">
        <input type="number" step="0.01" min="0.01" name="price" placeholder="Price (₹)" required>
        <input type="number" name="stock" min="0" placeholder="Stock Quantity" required>

        <label>Status</label>
        <select name="status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>

        <label>New Product Badge?</label>
        <select name="is_new">
            <option value="1">Yes (Show NEW badge)</option>
            <option value="0">No</option>
        </select>

        <label>Front Image (JPG, PNG, WebP max 5MB)</label>
        <input type="file" name="image_front" accept="image/jpeg,image/png,image/webp" required>

        <label>Back Image (JPG, PNG, WebP max 5MB)</label>
        <input type="file" name="image_back" accept="image/jpeg,image/png,image/webp" required>

        <button type="submit">Add Product</button>
    </form>

    <p style="margin-top: 20px;">
        <a href="<?= url('/admin/dashboard.php') ?>">← Back to Dashboard</a>
    </p>
</div>

</body>
</html>
