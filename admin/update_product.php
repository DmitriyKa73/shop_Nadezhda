<?php
require '../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $category_id = $_POST['category_id'];

    $stmt = $conn->prepare("UPDATE products SET name=?, price=?, quantity=?, category_id=? WHERE id=?");
    $stmt->bind_param("sdiii", $name, $price, $quantity, $category_id, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_products.php");
    exit();
}
?>
