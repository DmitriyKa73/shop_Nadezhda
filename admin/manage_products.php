<?php
include '../includes/header.php';
include '../config/config.php';
include '../config/middleware.php';

checkAuth(['admin', 'moderator']);

$stmt = $pdo->query("SELECT p.id, p.name, p.price, p.quantity, p.image, c.name AS category_name, p.category_id 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id
                     ORDER BY p.id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categoryStmt = $pdo->query("SELECT id, name FROM categories");
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = (int)$_POST['delete_product'];

    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($product && file_exists("../uploads/" . $product['image'])) {
        unlink("../uploads/" . $product['image']);
    }

    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);

    header("Location: manage_products.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $product_id = (int)$_POST['edit_product'];
    $name = trim($_POST['name']);
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $new_image = $_FILES['image'] ?? null;

    $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    if (!$stmt->fetch()) {
        die("Ошибка: выбранная категория не существует.");
    }

    if ($new_image && $new_image['error'] === UPLOAD_ERR_OK) {
        $imageName = time() . '_' . basename($new_image['name']);
        move_uploaded_file($new_image['tmp_name'], "../uploads/$imageName");

        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $oldProduct = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($oldProduct && file_exists("../uploads/" . $oldProduct['image'])) {
            unlink("../uploads/" . $oldProduct['image']);
        }

        $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, quantity = ?, category_id = ?, image = ? WHERE id = ?");
        $stmt->execute([$name, $price, $quantity, $category_id, $imageName, $product_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, quantity = ?, category_id = ? WHERE id = ?");
        $stmt->execute([$name, $price, $quantity, $category_id, $product_id]);
    }

    header("Location: manage_products.php");
    exit();
}
?>

<div class="container mt-4">
    <h2 class="text-center">Управление товарами</h2>
    <table class="table table-bordered text-center">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Цена</th>
                <th>Количество</th>
                <th>Категория</th>
                <th>Фото</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= $product['id'] ?></td>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= number_format($product['price'], 2, '.', ' ') ?> ₽</td>
                    <td><?= $product['quantity'] ?></td>
                    <td><?= htmlspecialchars($product['category_name'] ?? 'Без категории') ?></td>
                    <td>
                        <img src="../uploads/<?= htmlspecialchars($product['image']) ?>" width="50" height="50" alt="Фото">
                    </td>
                    <td>
                        <button class="btn btn-warning btn-sm edit-btn" data-id="<?= $product['id'] ?>"
                                data-name="<?= htmlspecialchars($product['name']) ?>"
                                data-price="<?= $product['price'] ?>"
                                data-quantity="<?= $product['quantity'] ?>"
                                data-category="<?= $product['category_id'] ?>"
                                data-image="<?= htmlspecialchars($product['image']) ?>">
                            Редактировать
                        </button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="delete_product" value="<?= $product['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены, что хотите удалить этот товар?');">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Редактировать товар</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" name="edit_product" id="edit_product_id">
          <div class="mb-3">
            <label class="form-label">Название</label>
            <input type="text" class="form-control" name="name" id="edit_name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Цена</label>
            <input type="number" class="form-control" name="price" id="edit_price" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Количество</label>
            <input type="number" class="form-control" name="quantity" id="edit_quantity" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Категория</label>
            <select class="form-control" name="category_id" id="edit_category">
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Изображение</label>
            <input type="file" class="form-control" name="image">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Сохранить изменения</button>
        </div>
      </form>
    </div>
  </div>
</div>


<script>
document.querySelectorAll(".edit-btn").forEach(button => {
    button.addEventListener("click", function() {
        document.getElementById("edit_product_id").value = this.dataset.id;
        document.getElementById("edit_name").value = this.dataset.name;
        document.getElementById("edit_price").value = this.dataset.price;
        document.getElementById("edit_quantity").value = this.dataset.quantity;
        document.getElementById("edit_category").value = this.dataset.category;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});
</script>

<?php include '../includes/footer.php'; ?>

