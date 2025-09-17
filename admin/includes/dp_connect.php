<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check session before proceeding
if (
    !isset($_SESSION['user_logged_in']) || 
    !isset($_SESSION['bt_user_id']) ||
    $_SESSION['role'] !== 'ADMIN') {
    header("Location: Index.php");
    exit();
}

// Database Configuration
$host = 'localhost';
$dbname = 'btonedatabase';
$username = 'root';
$password = '';
// Database Connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>