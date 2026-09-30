<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

if (isset($_GET["id"])) {
    $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
    $stmt->bind_param("i", $_GET["id"]);
    $stmt->execute();
    header("Location: delete_product.php");
    exit;
}

$products = $conn->query("SELECT * FROM products");
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style.css">
<title>Delete Products</title>
</head>
<body>

<h2>Delete Products</h2>

<?php while ($p = $products->fetch_assoc()): ?>
<p>
<?= htmlspecialchars($p["name"]) ?>
<a href="?id=<?= $p["id"] ?>" onclick="return confirm('Delete?')">Delete</a>
</p>
<?php endwhile; ?>

</body>
</html>
