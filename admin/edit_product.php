<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $stmt = $conn->prepare(
        "UPDATE products 
         SET name=?, price=?, stock=?, status=?, is_new=?
         WHERE id=?"
    );

    $stmt->bind_param(
        "sdisii",
        $_POST["name"],
        $_POST["price"],
        $_POST["stock"],
        $_POST["status"],
        $_POST["is_new"],
        $_POST["id"]
    );

    $stmt->execute();
}

$products = $conn->query("SELECT * FROM products");
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Products</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h2>Edit Products</h2>

<?php while ($p = $products->fetch_assoc()): ?>
<form method="POST">
    <input type="hidden" name="id" value="<?= $p['id'] ?>">

    <input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>">
    <input type="number" step="0.01" name="price" value="<?= $p['price'] ?>">
    <input type="number" name="stock" value="<?= $p['stock'] ?>">

    <select name="status">
        <option value="active" <?= $p['status']=="active"?"selected":"" ?>>Active</option>
        <option value="inactive" <?= $p['status']=="inactive"?"selected":"" ?>>Inactive</option>
    </select>

    <select name="is_new">
        <option value="1" <?= $p['is_new']?"selected":"" ?>>New</option>
        <option value="0" <?= !$p['is_new']?"selected":"" ?>>Old</option>
    </select>

    <button>Update</button>
</form>

<img src="../assets/images/<?= $p['image_front'] ?>" width="80">
<img src="../assets/images/<?= $p['image_back'] ?>" width="80">
<hr>
<?php endwhile; ?>

</body>
</html>
