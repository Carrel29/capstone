<?php
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$conn = new mysqli("localhost", "root", "", "cafe_pos");
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Set timezone and get date
date_default_timezone_set('Asia/Manila');
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$query = "
    SELECT 
        t.transaction_date,
        t.username,
        t.payment_method,
        t.total_amount,
        t.cart_items,
        t.cash_received,
        t.cash_change,
        t.reference_number,
        c.name as category_name
    FROM transactions t
    CROSS JOIN JSON_TABLE(
        t.cart_items,
        '$[*]' COLUMNS(
            cart_item JSON PATH '$'
        )
    ) as ti
    JOIN products p ON 
        LOWER(TRIM(JSON_UNQUOTE(JSON_EXTRACT(ti.cart_item, '$.name')))) REGEXP CONCAT('^', LOWER(p.name))
    JOIN categories c ON p.category_id = c.id
    WHERE DATE(t.transaction_date) = ?
    GROUP BY t.id, c.name
    ORDER BY t.transaction_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $date);
$stmt->execute();
$result = $stmt->get_result();

$categoryData = [];

while ($row = $result->fetch_assoc()) {
    $category = $row['category_name'];
    
    if (!isset($categoryData[$category])) {
        $categoryData[$category] = [];
    }
    
    $cartItems = json_decode($row['cart_items'], true);
    
    foreach ($cartItems as $item) {
        $categoryData[$category][] = [
            'name' => $item['name'],
            'size' => $item['size'] ?? 'Regular',
            'quantity' => intval($item['quantity']),
            'price' => floatval($item['price']),
            'total_amount' => floatval($item['price']) * intval($item['quantity'])
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($categoryData);