<?php
session_start();
include 'includes/header.php';
include 'config/config.php'; 

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$notification = '';

$searchQuery = $_GET['search'] ?? '';
$searchSQL = "";
$searchParams = [];

if (!empty($searchQuery)) {
    $searchSQL = "WHERE p.name LIKE ? OR c.name LIKE ?";
    $searchParams[] = "%" . $searchQuery . "%";
    $searchParams[] = "%" . $searchQuery . "%";
}

$stmt = $pdo->prepare("SELECT p.id, p.name, p.price, p.quantity, p.image, c.name as category 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     $searchSQL");
$stmt->execute($searchParams);
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
            $notification = "Товар добавлен в корзину!";
        } elseif ($action === 'decrease' && $_SESSION['cart'][$product_id] > 0) {
            $_SESSION['cart'][$product_id]--;
            if ($_SESSION['cart'][$product_id] == 0) {
                unset($_SESSION['cart'][$product_id]);
            }
            $notification = "Товар удален из корзины!";
        }
    }
}
?>

<head>
    <link rel="stylesheet" href="/config/style.css?v=<?= time() ?>">
</head>

<body>

    <div id="notification-container"></div>

    <div class="container mt-4">
        <h2 class="text-center mb-4">Популярные товары</h2>


        <form method="GET" action="index.php" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Введите название товара или категорию..." value="<?= htmlspecialchars($searchQuery) ?>">
                <button type="submit" class="btn btn-primary">🔍 Поиск</button>
            </div>
        </form>

        <div class="row">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <?php 
                        $productId = (int) $product['id'];
                        $productName = htmlspecialchars($product['name'] ?? 'Неизвестный товар');
                        $productPrice = number_format((float) ($product['price'] ?? 0), 2, '.', ' ');
                        $productQuantity = (int) ($product['quantity'] ?? 0);
                        $cartQuantity = isset($_SESSION['cart'][$productId]) ? (int) $_SESSION['cart'][$productId] : 0;
                        $isOutOfStock = $productQuantity <= 0;
                        $category = htmlspecialchars($product['category'] ?? 'Без категории');
                    ?>
                    <div class="col-md-3 mb-4 d-flex align-items-stretch">
                        <div class="card shadow-sm text-center">
                            <img src="/uploads/<?= htmlspecialchars($product['image'] ?? 'no-image.png') ?>" 
                                 class="card-img-top product-img" 
                                 alt="<?= $productName ?>">

                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= $productName ?></h5>
                                <p class="card-text"><strong><?= $productPrice ?> ₽</strong></p>
                                <p class="card-text"> <?= $category ?></p>
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
                <div class="alert alert-warning text-center">
                    Ничего не найдено по запросу <b>"<?= htmlspecialchars($searchQuery) ?>"</b>.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

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

<?php include 'includes/footer.php'; ?>
