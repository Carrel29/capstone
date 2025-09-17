<?php
session_start();
try {
    $db = new PDO("mysql:host=localhost;dbname=capstone", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Add custom colors function
function getUserCustomColors($db, $userId) {
    $stmt = $db->prepare("SELECT bg_color, font_family FROM user_customization WHERE user_id = ?");
    $stmt->execute([$userId]);
    $colors = $stmt->fetch(PDO::FETCH_ASSOC);
    return $colors ?: ['bg_color' => '#e8f4f8', 'font_family' => 'Arial']; // Default colors
}

// Get user's custom colors
$userColors = getUserCustomColors($db, $_SESSION['user_id']);

// Get selected date for availability check (default to today)
$check_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$category = isset($_GET['category']) ? $_GET['category'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Main query with booking information
$query = "SELECT i.*, 
    (i.quantity - COALESCE(
        (SELECT SUM(be.quantity) 
         FROM booking_equipment be 
         INNER JOIN customer_inquiries ci ON be.booking_id = ci.id
         WHERE be.equipment_id = i.id 
         AND ci.status NOT IN ('Completed', 'Cancelled')
         AND be.rental_start <= :check_date 
         AND be.rental_end >= :check_date), 0
    )) as available_quantity,
    COALESCE(
        (SELECT SUM(be.quantity) 
         FROM booking_equipment be 
         INNER JOIN customer_inquiries ci ON be.booking_id = ci.id
         WHERE be.equipment_id = i.id 
         AND ci.status NOT IN ('Completed', 'Cancelled')
         AND be.rental_start <= :check_date2 
         AND be.rental_end >= :check_date2), 0
    ) as booked_quantity,
    (SELECT COUNT(*) 
     FROM booking_equipment be 
     INNER JOIN customer_inquiries ci ON be.booking_id = ci.id 
     AND ci.status IN ('Confirmed', 'Ongoing')
    ) as currently_in_use
    FROM inventory i
    WHERE 1=1";

if ($category !== '') { // Only add the category filter if it's not empty
    $query .= " AND i.category = :category";
}
if ($status) {
    $query .= " AND i.status = :status";
}
$query .= " ORDER BY i.category, i.item_name";

$stmt = $db->prepare($query);
$stmt->bindParam(':check_date', $check_date);
$stmt->bindParam(':check_date2', $check_date);
if ($category !== '') { // Bind the category parameter only if it's not empty
    $stmt->bindParam(':category', $category);
}
if ($status) {  
    $stmt->bindParam(':status', $status);
}
$stmt->execute();
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$categories = $db->query("SELECT DISTINCT category FROM inventory ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Event Equipment Inventory</title>
    <link rel="stylesheet" href="assets_css/inventory.css">
    <style>
        body {
            font-family: <?php echo $userColors['font_family']; ?>;
        }
    </style>
</head>
<body>
    <a href="dashboard.php" class="back-button">←</a>
    
    <div class="container">
        <div class="header">
            <h1>Event Equipment Inventory</h1>
        </div>

        <div class="filters">
            <div class="filter-group">
                <label>Check Availability For:</label>
                <input type="date" 
                       value="<?php echo $check_date; ?>" 
                       onchange="updateFilters('date', this.value)">
            </div>

            <div class="filter-group">
                <label>Category:</label>
                <select onchange="updateFilters('category', this.value)">
                    <option value="">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $category == $cat ? 'selected' : ''; ?>>
                            <?php echo $cat; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Status:</label>
                <select onchange="updateFilters('status', this.value)">
                    <option value="">All Status</option>
                    <option value="In Stock" <?php echo $status == 'In Stock' ? 'selected' : ''; ?>>In Stock</option>
                    <option value="Low Stock" <?php echo $status == 'Low Stock' ? 'selected' : ''; ?>>Low Stock</option>
                    <option value="Out of Stock" <?php echo $status == 'Out of Stock' ? 'selected' : ''; ?>>Out of Stock</option>
                </select>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Total Quantity</th>
                    <th>Available Quantity</th>
                    <th>Booked</th>
                    <th>Rental Rate (per day/event)</th>
                    <th>Supplier</th>
                    <th>Reorder Level</th>
                    <th>Status</th>
                    <th>Usage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($inventory as $item): 
                    $status_class = $item['available_quantity'] > $item['reorder_level'] ? 'in' : 
                                  ($item['available_quantity'] > 0 ? 'low' : 'out');
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['category']); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td><?php echo $item['available_quantity']; ?></td>
                        <td><?php echo $item['booked_quantity']; ?></td>
                        <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($item['supplier']); ?></td>
                        <td><?php echo htmlspecialchars($item['reorder_level']); ?></td>
                        <td>
                            <span class="status status-<?php echo $status_class; ?>">
                                <?php echo $item['available_quantity'] > $item['reorder_level'] ? 'Available' : 
                                      ($item['available_quantity'] > 0 ? 'Low Stock' : 'Fully Booked'); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($item['currently_in_use'] > 0): ?>
                                <span class="usage-badge in-use">
                                    <?php echo $item['currently_in_use']; ?> Currently In Use
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="rental-history">
    <h2>Equipment Rental History</h2>
    <table class="history-table">
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Customer</th>
                <th>Rental Start</th>
                <th>Rental End</th>
                <th>Quantity</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $historyQuery = "SELECT 
                i.item_name,
                ci.customer_name,
                be.rental_start,
                be.rental_end,
                be.quantity,
                ci.status
            FROM booking_equipment be
            INNER JOIN inventory i ON be.equipment_id = i.id
            INNER JOIN customer_inquiries ci ON be.booking_id = ci.id
            ORDER BY be.rental_start DESC
            LIMIT 10";
            
            $historyStmt = $db->query($historyQuery);
            while ($row = $historyStmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($row['rental_start'])); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($row['rental_end'])); ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
    </div>

    <script>
    function updateFilters(param, value) {
        const urlParams = new URLSearchParams(window.location.search);
        if (value) {
            urlParams.set(param, value);
        } else {
            urlParams.delete(param);
        }
        window.location.href = '?' + urlParams.toString();
    }
    </script>
</body>
</html>