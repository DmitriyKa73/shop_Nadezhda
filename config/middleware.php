<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}




function checkAuth($roles = []) {
    if (!isset($_SESSION['user_role'])) {
        header("Location: /public/login.php"); 
        exit();
    }

    if (!empty($roles) && !in_array($_SESSION['user_role'], $roles)) {
        header("Location: /index.php"); 
        exit();
    }
}
