<?php
session_start();
include '../includes/header.php';
include '../config/config.php';

if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

$cartItems = $_SESSION["cart"];
$products = [];

if (!empty($cartItems)) {
    $placeholders = implode(',', array_fill(0, count($cartItems), '?'));
    $stmt = $pdo->prepare("SELECT id, name, price, quantity FROM products WHERE id IN ($placeholders)");
    $stmt->execute(array_keys($cartItems));
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int) $_POST['product_id'];
    $action = $_POST['action'] ?? '';

    $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    $actualStock = (int) $product['quantity'];

    if ($product) {
        if (!isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] = 0;
        }

        if ($action === 'increase' && $_SESSION['cart'][$product_id] < $actualStock) {
            $_SESSION['cart'][$product_id]++;
        } elseif ($action === 'decrease' && $_SESSION['cart'][$product_id] > 0) {
            $_SESSION['cart'][$product_id]--;
            if ($_SESSION['cart'][$product_id] == 0) {
                unset($_SESSION['cart'][$product_id]);
            }
        } elseif ($action === 'remove') {
            unset($_SESSION['cart'][$product_id]);
        }
    }
    header("Location: cart.php");
    exit();
}

$total = 0;
?>

<div class="container mt-4">
    <h2 class="text-center">Корзина</h2>

    <?php if (!empty($products)): ?>
        <table class="table table-bordered text-center">
            <thead class="table-dark">
                <tr>
                    <th>Название</th>
                    <th>Цена</th>
                    <th>Количество</th>
                    <th>Сумма</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <?php 
                        $productId = $product["id"];
                        $productName = htmlspecialchars($product["name"]);
                        $productPrice = (float) $product["price"];
                        $productQuantity = $_SESSION["cart"][$productId] ?? 0;
                        $sum = $productPrice * $productQuantity;
                        $total += $sum;
                    ?>
                    <tr>
                        <td><?= $productName ?></td>
                        <td><?= number_format($productPrice, 2, '.', ' ') ?> ₽</td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="product_id" value="<?= $productId ?>">
                                <button type="submit" name="action" value="decrease" class="btn btn-sm btn-outline-danger">−</button>
                                <span class="mx-2"><?= $productQuantity ?></span>
                                <button type="submit" name="action" value="increase" class="btn btn-sm btn-outline-success" <?= ($productQuantity >= $product["quantity"]) ? 'disabled' : '' ?>>+</button>
                            </form>
                        </td>
                        <td><?= number_format($sum, 2, '.', ' ') ?> ₽</td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="product_id" value="<?= $productId ?>">
                                <button type="submit" name="action" value="remove" class="btn btn-danger btn-sm">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="text-end">
            <h4><strong>Итого: <?= number_format($total, 2, '.', ' ') ?> ₽</strong></h4>
            <a href="checkout.php" class="btn btn-success">Оформить заказ</a>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center" role="alert">
            Ваша корзина пуста. <br><a href="../index.php" class="alert-link">Перейти к покупкам</a>.
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
