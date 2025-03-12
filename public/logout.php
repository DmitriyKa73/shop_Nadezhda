<?php
session_start();
session_unset();
session_destroy();
header("Location: login.php");
exit();
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="refresh" content="2;url=login.php">
</head>
<body>
    <p>Вы вышли. Сейчас произойдет автоматический переход на страницу входа...</p>
</body>
</html>
