<?php
session_start();
include 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int) $_POST['product_id'];
    $action = $_POST['action'] ?? '';

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (!isset($_SESSION['cart'][$product_id]) || !is_numeric($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] = 0;
    }

 
    $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentStock = (int) ($product['quantity'] ?? 0);

    if ($action === 'increase' && $currentStock > 0) {
        $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + 1;
        $pdo->prepare("UPDATE products SET quantity = quantity - 1 WHERE id = ?")->execute([$product_id]);
    } elseif ($action === 'decrease' && $_SESSION['cart'][$product_id] > 0) {
        $_SESSION['cart'][$product_id]--;


        $pdo->prepare("UPDATE products SET quantity = quantity + 1 WHERE id = ?")->execute([$product_id]);

        if ($_SESSION['cart'][$product_id] === 0) {
            unset($_SESSION['cart'][$product_id]);
            header("Location: index.php"); 
            exit();
        }
    } elseif ($action === 'remove') {

        $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?")
            ->execute([$_SESSION['cart'][$product_id], $product_id]);

        unset($_SESSION['cart'][$product_id]);
        header("Location: index.php"); 
        exit();
    }

    header("Location: cart.php"); 
    exit();
}
