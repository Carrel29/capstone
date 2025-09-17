<?php
$pdo = new PDO("mysql:host=localhost;dbname=btonedatabase;charset=utf8mb4", "root", "");

// Hash new password
$hashedPassword = password_hash("root", PASSWORD_DEFAULT);

// Update existing admin account
$stmt = $pdo->prepare("UPDATE btuser 
    SET bt_password_hash = ?, bt_privilege_id = 1 
    WHERE bt_email = ?");

$stmt->execute([
    $hashedPassword,
    "lanceaeronm@gmail.com"
]);

echo "✅ Admin password updated and privilege set to ADMIN!";
