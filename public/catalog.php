<?php
require_once '../config/config.php';
require_once '../includes/header.php';

try {
    $stmt = $pdo->query("SELECT id, name, image FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при выполнении запроса: " . $e->getMessage());
}
?>
<head>
    <link rel="stylesheet" href="../config/style.css">
</head>
<div class="container mt-4">
    <h2 class="text-center mb-4">Категории товаров</h2>
    <div class="row">
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $category): ?>
                <div class="col-md-3 mb-4 d-flex align-items-stretch">
                    <div class="card shadow-sm text-center">
                        <img src="/uploads/<?= htmlspecialchars($category['image'] ?? 'category_placeholder.jpg') ?>" 
                             class="card-img-top product-img" 
                             alt="<?= htmlspecialchars($category['name']) ?>">

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($category['name'] ?? 'Без названия') ?></h5>
                            <a href="category.php?id=<?= $category['id'] ?>" class="btn btn-primary mt-auto">Перейти</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center">Категории не найдены.</p>
        <?php endif; ?>
    </div>
    <?php include '../includes/footer.php'; ?>

</div>

