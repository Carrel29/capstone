<?php
$serverName = "localhost";
$dBUsername = "root";
$dBPassword = "";
$dbName = "btonedatabase";
$port = "3306";

try {
    $dsn = "mysql:host=$serverName;port=$port;dbname=$dbName";
    $pdo = new PDO($dsn, $dBUsername, $dBPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Session security
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 600) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}