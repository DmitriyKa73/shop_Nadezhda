<?php
session_start();
include __DIR__ . '/../config/config.php';

$name = $email = $password = $confirm_password = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 6 || preg_match('/^\d+$/', $password)) {
        $error = "Пароль должен быть не менее 6 символов и содержать хотя бы одну букву!";
    } elseif ($password !== $confirm_password) {
        $error = "Пароли не совпадают!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'user')");

        try {
            $stmt->execute([$name, $email, $hashed_password]);
            header("Location: login.php");
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Ошибка дублирования email
                $error = "Пользователь с таким email уже зарегистрирован!";
            } else {
                $error = "Ошибка регистрации: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-body">
                    <h2 class="text-center mb-4">Регистрация</h2>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form action="" method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Имя</label>
                            <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Подтвердите пароль</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Зарегистрироваться</button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="login.php" class="btn btn-outline-secondary w-100">Уже есть аккаунт? Войти</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
