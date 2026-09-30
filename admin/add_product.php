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
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Product | Rival Society</title>
<?php rs_critical_css(); ?>
<link rel="stylesheet" href="<?= asset_url('/assets/base.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/admin/style.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>

<main class="container">
    <h2>Add New Product</h2>

    <?php if ($message): ?>
        <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label for="prod-name">Product Name</label>
        <input id="prod-name" type="text" name="name" placeholder="e.g. Naruto Sage Mode Heavyweight Tee" required maxlength="255">

        <label for="prod-price">Price (₹ INR)</label>
        <input id="prod-price" type="number" step="0.01" min="0.01" name="price" placeholder="1499.00" required>

        <label for="prod-stock">Stock Quantity</label>
        <input id="prod-stock" type="number" name="stock" min="0" placeholder="25" required>

        <label for="prod-status">Catalog Status</label>
        <select id="prod-status" name="status">
            <option value="active">Active (Visible in Store)</option>
            <option value="inactive">Inactive (Hidden)</option>
        </select>

        <label for="prod-new">Show "NEW" Badge?</label>
        <select id="prod-new" name="is_new">
            <option value="1">Yes (Display NEW badge)</option>
            <option value="0">No</option>
        </select>

        <label for="prod-front">Front Image (JPG, PNG, WebP max 5MB)</label>
        <input id="prod-front" type="file" name="image_front" accept="image/jpeg,image/png,image/webp" required>

        <label for="prod-back">Back Image (JPG, PNG, WebP max 5MB)</label>
        <input id="prod-back" type="file" name="image_back" accept="image/jpeg,image/png,image/webp" required>

        <button type="submit" class="btn primary">
            <i class="fas fa-plus" style="margin-right: 8px;"></i> Add Product to Catalog
        </button>
    </form>

    <p style="margin-top: 24px;">
        <a href="<?= url('/admin/dashboard.php') ?>" class="back-link">← Back to Dashboard</a>
    </p>
</main>

</body>
</html>
