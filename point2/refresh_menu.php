<?php

function refreshMenuItems($conn) {
    $sql = "SELECT p.*, c.name as category_name, p.classification 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.archived = 0 
            ORDER BY 
                CASE 
                    WHEN p.classification = 'drinks' THEN 1 
                    ELSE 2 
                END,
                c.name,
                p.name";
    $result = $conn->query($sql);
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}

// Add this function to help debug menu items
function debugMenuItems($menuItems) {
    echo "<pre>";
    print_r($menuItems);
    echo "</pre>";
}

function clearMenuCache() {
    if (isset($_SESSION['menu_items'])) {
        unset($_SESSION['menu_items']);
    }
}
?>