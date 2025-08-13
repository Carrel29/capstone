<?php
$host = '127.0.0.1';
$dbname = 'btonedatabase';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected successfully";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
