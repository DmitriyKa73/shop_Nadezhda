<?php
session_start();
include '../includes/header.php';
include '../config/config.php';

//загрузка категорий из БД
$stmt = $pdo->query("SELECT id, name FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $price = (float) $_POST['price'];
    $quantity = (int) $_POST['quantity'];
    $category_id = (int) $_POST['category_id']; 
    $image = $_FILES['image'];

    if ($image['error'] === UPLOAD_ERR_OK) {
        $imagePath = '../uploads/' . basename($image['name']);
        move_uploaded_file($image['tmp_name'], $imagePath);
    } else {
        $imagePath = null; 
    }

    // Добавление товара в БД
    $stmt = $pdo->prepare("INSERT INTO products (name, price, quantity, category_id, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $price, $quantity, $category_id, $imagePath]);

    echo "<div class='alert alert-success'>Товар успешно добавлен!</div>";
}
?>
<head>
    <link rel="stylesheet" href="../config/style.css">
</head>
<div class="container mt-4">

    <h2 class="text-center">Добавление товара</h2>
    
    <form method="POST" enctype="multipart/form-data">

        <div class="mb-3">
            <label class="form-label">Название товара</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Цена</label>
            <input type="number" name="price" class="form-control" step="0.01" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Количество</label>
            <input type="number" name="quantity" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Категория</label>
            <select name="category_id" class="form-control" required>
                <option value="">Выберите категорию</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Изображение</label>
            <input type="file" name="image" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">Добавить товар</button>
    </form>
   </div>

<?php include '../includes/footer.php'; ?>
