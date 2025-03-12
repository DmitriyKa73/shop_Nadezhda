<?php
include '../includes/header.php';
include '../config/config.php';
include '../config/middleware.php';

checkAuth(['user', 'moderator', 'admin']);

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

$stmt = $pdo->prepare("SELECT email, role, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user_role === 'admin' || $user_role === 'moderator') {
  $stmt = $pdo->query("SELECT o.id, o.total_price, o.status, o.created_at, o.note, 
  o.phone, o.address, 
  u.email, o.full_name
FROM orders o 
JOIN users u ON o.user_id = u.id 
ORDER BY o.created_at DESC");

} else {
    $stmt = $pdo->prepare("SELECT id, total_price, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
}
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status']) && ($user_role === 'admin' || $user_role === 'moderator')) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];

    if ($new_status === 'Отклонен') {
        $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?")
                ->execute([$item['quantity'], $item['product_id']]);
        }
    }

    $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$new_status, $order_id]);
    header("Location: profile.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['repeat_order']) && $user_role === 'user') {
  $order_id = (int)$_POST['repeat_order'];

  $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
  $stmt->execute([$order_id]);
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($items as $item) {
      $product_id = $item['product_id'];
      $requested_quantity = $item['quantity'];

      $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ?");
      $stmt->execute([$product_id]);
      $product = $stmt->fetch(PDO::FETCH_ASSOC);
      $available_quantity = $product['quantity'];

      if ($available_quantity > 0) {
          $final_quantity = min($requested_quantity, $available_quantity);

          $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + $final_quantity;

          $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
          $stmt->execute([$final_quantity, $product_id]);
      }
  }

  header("Location: cart.php");
  exit();
}

?>
<div class="container mt-4">
    <h2 class="text-center">Мой профиль</h2>
<div class="card"> 
    <div class="card_profile mb-4">
        <div class="card-body">
            <h5 class="card-title">Информация о пользователе</h5>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <p><strong>Роль:</strong> <?= htmlspecialchars($user['role']) ?></p>
            <p><strong>Дата регистрации:</strong> <?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></p>

            <a href="/public/logout.php" class="btn btn-danger mt-3">Выйти из аккаунта</a>
            </div>
        </div>
        
    </div>

    <h3 class="mb-3">История заказов</h3>
    <?php if (!empty($orders)): ?>
        <table class="table table-bordered text-center">
            <thead class="table-dark">
                <tr>
                    <th>ID заказа</th>
                    <?php if ($user_role === 'admin'): ?>
                        <th>Email</th>
                    <?php endif; ?>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= $order['id'] ?></td>
                        <?php if ($user_role === 'admin'): ?>
                            <td><?= htmlspecialchars($order['email']) ?></td>
                        <?php endif; ?>
                        <td><?= number_format($order['total_price'], 2, '.', ' ') ?> ₽</td>
                        <td>
                            <?php if ($user_role === 'admin' || $user_role === 'moderator'): ?>
                                <form method="POST">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <select name="status" class="form-select">
                                        <?php
                                        $statuses = ['В обработке', 'На согласовании', 'Передан в доставку', 'Доставлен', 'Отклонен'];
                                        foreach ($statuses as $status) {
                                            $selected = $order['status'] === $status ? 'selected' : '';
                                            echo "<option value='$status' $selected>$status</option>";
                                        }
                                        ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary mt-1">Изменить</button>
                                </form>
                            <?php else: ?>
                                <?= htmlspecialchars($order['status']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                        <td>
                            <?php if ($user_role === 'user'): ?>
                                <form method="POST">
                                    <input type="hidden" name="repeat_order" value="<?= $order['id'] ?>">
                                    <button type="submit" class="btn btn-warning btn-sm">Повторить заказ</button>
                                </form>
                            <?php elseif ($user_role === 'admin'): ?>
                              <button class="btn btn-info btn-sm client-info-btn" 
        data-name="<?= htmlspecialchars($order['full_name'] ?? 'Не указано') ?>" 
        data-phone="<?= htmlspecialchars($order['phone'] ?? 'Не указано') ?>" 
        data-address="<?= htmlspecialchars($order['address'] ?? 'Не указано') ?>" 
        data-note="<?= htmlspecialchars($order['note'] ?? 'Нет примечания') ?>">
    Информация о клиенте
</button>


                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-center">У вас пока нет заказов.</p>
    <?php endif; ?>

</div>

<!-- Модальное окно -->
<div class="modal fade" id="clientInfoModal" tabindex="-1" aria-labelledby="clientInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Информация о клиенте</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
      </div>
      <div class="modal-body">
        <p><strong>Имя:</strong> <span id="client-name"></span></p>
        <p><strong>Телефон:</strong> <span id="client-phone"></span></p>
        <p><strong>Адрес:</strong> <span id="client-address"></span></p>
        <p><strong>Примечание:</strong> <span id="client-note"></span></p>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll(".client-info-btn").forEach(button => {
    button.addEventListener("click", function() {
        document.getElementById("client-name").innerText = this.dataset.name.trim() || "Не указано";
        document.getElementById("client-phone").innerText = this.dataset.phone.trim() || "Не указано";
        document.getElementById("client-address").innerText = this.dataset.address.trim() || "Не указано";
        document.getElementById("client-note").innerText = this.dataset.note.trim() || "Нет примечания";
        
        new bootstrap.Modal(document.getElementById('clientInfoModal')).show();
    });
});



</script>

<?php include '../includes/footer.php'; ?>
