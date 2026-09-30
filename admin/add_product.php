<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html>
<head>
<title>Add Product</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h2>Add Product</h2>

<form method="POST" enctype="multipart/form-data">
    <input type="text" name="name" placeholder="Product Name" required>
    <input type="number" step="0.01" name="price" placeholder="Price" required>

    <input type="number" name="stock" placeholder="Stock Quantity" required>

    <label>Status</label>
    <select name="status">
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
    </select>

    <label>New Product?</label>
    <select name="is_new">
        <option value="1">Yes</option>
        <option value="0">No</option>
    </select>

    <label>Front Image</label>
    <input type="file" name="image_front" required>

    <label>Back Image</label>
    <input type="file" name="image_back" required>

    <button>Add Product</button>
</form>

<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name   = $_POST["name"];
    $price  = $_POST["price"];
    $stock  = $_POST["stock"];
    $status = $_POST["status"];
    $is_new = $_POST["is_new"];

    $front = time() . "_front_" . $_FILES["image_front"]["name"];
    $back  = time() . "_back_"  . $_FILES["image_back"]["name"];

    move_uploaded_file($_FILES["image_front"]["tmp_name"], "../assets/images/$front");
    move_uploaded_file($_FILES["image_back"]["tmp_name"],  "../assets/images/$back");

    $stmt = $conn->prepare(
        "INSERT INTO products 
        (name, price, image_front, image_back, stock, status, is_new)
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "sdssisi",
        $name,
        $price,
        $front,
        $back,
        $stock,
        $status,
        $is_new
    );

    $stmt->execute();

    echo "<p>Product added successfully</p>";
}
?>

</body>
</html>
