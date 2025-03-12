<?php
session_start();
include '../includes/header.php';
include '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    echo "<div class='alert alert-warning text-center'>
            Для оформления заказа необходимо <a href='/public/login.php'>войти в аккаунт</a>.
          </div>";
    include '../includes/footer.php';
    exit();
}

if (!isset($_SESSION["cart"]) || empty($_SESSION["cart"])) {
    echo "<div class='alert alert-warning text-center'>
            Ваша корзина пуста. <a href='/index.php'>Перейти к покупкам</a>.
          </div>";
    include '../includes/footer.php';
    exit();
}

$user_id = $_SESSION['user_id'];
$session_id = session_id();
$total_price = 0;

$cartItems = $_SESSION["cart"];
$placeholders = implode(',', array_fill(0, count($cartItems), '?'));
$stmt = $pdo->prepare("SELECT id, name, price, quantity FROM products WHERE id IN ($placeholders)");
$stmt->execute(array_keys($cartItems));
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $product) {
    $total_price += $product["price"] * $cartItems[$product["id"]];
}

$order_number = "ORD-" . strtoupper(uniqid());

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if (empty($full_name) || empty($phone) || empty($address)) {
        echo "<div class='alert alert-danger text-center'>Пожалуйста, заполните все поля.</div>";
    } else {
        try {
            $pdo->beginTransaction();

            foreach ($_SESSION['cart'] as $product_id => $cartQuantity) {
                $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product || $cartQuantity > $product['quantity']) {
                    throw new Exception("Ошибка: Недостаточно товара на складе для \"{$product['name']}\".");
                }
            }

            $stmt = $pdo->prepare("INSERT INTO orders (order_number, user_id, session_id, full_name, phone, address, note, total_price, status, created_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'В обработке', NOW())");
            $stmt->execute([$order_number, $user_id, $session_id, $full_name, $phone, $address, $note, $total_price]);            
            $order_id = $pdo->lastInsertId();

            $stmtOrderItems = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmtUpdateStock = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
            
            foreach ($products as $product) {
                $stmtOrderItems->execute([$order_id, $product["id"], $cartItems[$product["id"]], $product["price"]]);
                $stmtUpdateStock->execute([$cartItems[$product["id"]], $product["id"]]);
            }

            unset($_SESSION["cart"]);

            $pdo->commit();

            echo "<div class='alert alert-success text-center'>
                    <h4>Ваш заказ <b>$order_number</b> успешно оформлен!</h4>
                    <p>Оплата при получении.</p>
                    <a href='/index.php' class='btn btn-primary mt-3'>На главную</a>
                  </div>";

            include '../includes/footer.php';
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div class='alert alert-danger text-center'>{$e->getMessage()}</div>";
        }
    }
}
?>

<div class="container mt-4">
    <h2 class="text-center mb-4">Оформление заказа</h2>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">ФИО</label>
            <input type="text" name="full_name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Номер телефона</label>
            <input type="tel" name="phone" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Адрес доставки</label>
            <textarea name="address" class="form-control" required></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Примечание к заказу</label>
            <textarea name="note" class="form-control" rows="3"></textarea>
        </div>

        <div class="alert alert-info">
            Оплата производится курьеру при получении заказа.
        </div>

        <div class="mb-3">
            <h4>Итого: <?= number_format($total_price, 2, '.', ' ') ?> ₽</h4>
        </div>

        <button type="submit" class="btn btn-success w-100">Оформить заказ</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
