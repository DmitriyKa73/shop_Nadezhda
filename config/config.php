<?php
$host = "MySQL-8.2"; 
$dbname = "webshop_bd";
$username = "root";
$password = "";
$conn = new mysqli($host, $username, $password, $dbname);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}
?>
