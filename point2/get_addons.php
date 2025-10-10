<?php
require_once 'db_connect.php';

$query = "SELECT * FROM products WHERE category_id = 3 AND archived = 0";
$result = $conn->query($query);

$addons = [];
while($row = $result->fetch_assoc()) {
    $addons[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'price' => floatval($row['price'])
    ];
}

header('Content-Type: application/json');
echo json_encode($addons);