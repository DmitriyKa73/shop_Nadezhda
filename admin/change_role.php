<?php
include '../config/middleware.php';
include '../config/config.php';

checkAuth(['admin']); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];

    
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$new_role, $user_id]);

    header("Location: admin.php");
    exit();
}
?>
