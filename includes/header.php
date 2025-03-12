<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_role = $_SESSION['user_role'] ?? 'user'; 
$isAuthenticated = isset($_SESSION['user_id']); 
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Магазин "Надежда"</title>
    <link rel="stylesheet" href="/assets/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">


<header class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="/index.php">Магазин "Надежда"</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="/index.php">Главная</a></li>
                <li class="nav-item"><a class="nav-link" href="/public/catalog.php">Каталог</a></li>
                <li class="nav-item"><a class="nav-link" href="/public/about.php">О магазине</a></li>
                <li class="nav-item"><a class="nav-link" href="/public/cart.php">Корзина</a></li>
                <li class="nav-item">
    <a class="nav-link" href="<?= $isAuthenticated ? '/public/profile.php' : '/public/login.php' ?>">
        <?= $isAuthenticated ? 'Мой профиль' : 'Войти' ?>
    </a>
</li>
<?php if ($user_role === 'moderator' || $user_role === 'admin'): ?>
    <li class="nav-item"><a class="nav-link text-danger" href="/admin/add_product.php">Добавить товар</a></li>
<?php endif; ?>
<?php if ($user_role === 'moderator' || $user_role === 'admin'): ?>
    <li class="nav-item"><a class="nav-link text-danger" href="/admin/manage_products.php">Редактировать товар</a></li>
<?php endif; ?>
<?php if ($user_role === 'admin'): ?>
    <li class="nav-item"><a class="nav-link text-danger" href="/admin/admin.php">Админ панель</a></li>
<?php endif; ?>
            </ul>
        </div>
    </div>
</header>

<main class="container mt-4">
