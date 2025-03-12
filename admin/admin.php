<?php
include '../includes/header.php';

include '../config/config.php';
include '../config/middleware.php';

checkAuth(['admin']); 

$stmt = $pdo->query("SELECT id, email, role FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="../assets/admin.css">
    <link rel="stylesheet" href="../config/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Админ-панель</h1>
    

        <h2 class="mt-4">Пользователи</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= $user['email'] ?></td>
                        <td><?= $user['role'] ?></td>
                        <td>
                        <form action="change_role.php" method="POST">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <select name="new_role" class="form-select">
                                    <option value="user" <?= $user['role'] == 'user' ? 'selected' : '' ?>>User</option>
                                    <option value="moderator" <?= $user['role'] == 'moderator' ? 'selected' : '' ?>>Moderator</option>
                                    <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm mt-1">Изменить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<? include '../includes/footer.php'; ?>
