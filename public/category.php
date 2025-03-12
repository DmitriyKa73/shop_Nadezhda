<head>
  <link rel="stylesheet" href="../config/style.css">
</head>
<?php
session_start();
require_once '../config/config.php';
require_once '../includes/header.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$category_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($category_id <= 0) {
    die("<div class='container mt-4'><div class='alert alert-danger text-center'>Ошибка: некорректный идентификатор категории.</div></div>");
}

$stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    die("<div class='container mt-4'><div class='alert alert-danger text-center'>Ошибка: категория не найдена.</div></div>");
}

$stmt = $pdo->prepare("SELECT id, name, price, quantity, image FROM products WHERE category_id = ?");
$stmt->execute([$category_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int) $_POST['product_id'];
    $action = $_POST['action'] ?? '';

    $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $currentStock = (int) $product['quantity'];

        if (!isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] = 0;
        }

        if ($action === 'increase' && $currentStock > 0) {
            $_SESSION['cart'][$product_id]++;
            $pdo->prepare("UPDATE products SET quantity = quantity - 1 WHERE id = ?")->execute([$product_id]);
            $_SESSION['notification'] = "✅ Товар добавлен в корзину!";
        } elseif ($action === 'decrease' && $_SESSION['cart'][$product_id] > 0) {
            $_SESSION['cart'][$product_id]--;

            if ($_SESSION['cart'][$product_id] == 0) {
                unset($_SESSION['cart'][$product_id]);
            }

            $pdo->prepare("UPDATE products SET quantity = quantity + 1 WHERE id = ?")->execute([$product_id]);
            $_SESSION['notification'] = "❌ Товар удален из корзины!";
        }
    }

    header("Location: category.php?id=$category_id");
    exit();
}

$notification = $_SESSION['notification'] ?? '';
unset($_SESSION['notification']); 
?>

<div class="container mt-4">
    <h2 class="text-center mb-4">Категория: <?= htmlspecialchars($category['name']) ?></h2>

    <div id="notification-container"></div>

    <div class="row">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <?php
                    $productId = (int) $product['id'];
                    $productName = htmlspecialchars($product['name']);
                    $productPrice = number_format((float) $product['price'], 2, '.', ' ');
                    $productQuantity = (int) $product['quantity'];
                    $cartQuantity = $_SESSION['cart'][$productId] ?? 0;
                    $isOutOfStock = $productQuantity <= 0;
                ?>
                <div class="col-md-3 mb-4 d-flex align-items-stretch">
                    <div class="card shadow-sm text-center">
                        <img src="/uploads/<?= htmlspecialchars($product['image'] ?? 'no-image.png') ?>" 
                             class="card-img-top product-img" 
                             alt="<?= $productName ?>">

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= $productName ?></h5>
                            <p class="card-text"><strong><?= $productPrice ?> ₽</strong></p>
                            <p class="text-muted">Осталось: <?= $productQuantity ?> шт.</p>

                            <form method="POST" class="mt-auto">
                                <input type="hidden" name="product_id" value="<?= $productId ?>">
                                <?php if ($cartQuantity > 0): ?>
                                    <div class="d-flex justify-content-center align-items-center">
                                        <button type="submit" name="action" value="decrease" class="btn btn-outline-danger btn-sm">−</button>
                                        <span class="mx-2"><?= $cartQuantity ?></span>
                                        <button type="submit" name="action" value="increase" class="btn btn-outline-success btn-sm" <?= $isOutOfStock ? 'disabled' : '' ?>>+</button>
                                    </div>
                                <?php else: ?>
                                    <button type="submit" name="action" value="increase" class="btn btn-primary" <?= $isOutOfStock ? 'disabled' : '' ?>>
                                        <?= $isOutOfStock ? 'Нет в наличии' : 'Добавить в корзину' ?>
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center">В этой категории пока нет товаров.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    function showNotification(message) {
        let container = document.getElementById('notification-container');
        let notification = document.createElement('div');
        notification.classList.add('notification');
        notification.innerText = message;
        container.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('hide');
            setTimeout(() => notification.remove(), 500);
        }, 2000);
    }

    <?php if (!empty($notification)): ?>
        showNotification("<?= $notification ?>");
    <?php endif; ?>
</script>

<?php require_once '../includes/footer.php'; ?>
