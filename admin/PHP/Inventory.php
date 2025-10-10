<?php
// ==================== FIXED SESSION START ====================
// Only start session once at the very top
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check admin authentication
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: Index.php");
    exit();
}

// Database Connection
$host = 'localhost';
$dbname = 'btonedatabase';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
// ==================== END FIXED SESSION ====================

// ==================== AUTO-UPDATE INVENTORY AVAILABILITY ====================
function updateInventoryAvailability($pdo) {
    try {
        // Mark past events as completed
        $completedEvents = $pdo->exec("
            UPDATE bookings 
            SET status = 'Completed' 
            WHERE status IN ('Approved', 'Pending') 
            AND DATE(EventDuration) < CURDATE()
        ");
        
        // Update inventory quantities
        $pdo->exec("
            UPDATE inventory i 
            SET i.available_quantity = i.quantity - COALESCE((
                SELECT SUM(be.quantity) 
                FROM booking_equipment be 
                INNER JOIN bookings b ON be.booking_id = b.id 
                WHERE be.equipment_id = i.id 
                AND b.status IN ('Approved', 'Pending') 
                AND DATE(b.EventDuration) >= CURDATE()
            ), 0)
        ");
        
        return $completedEvents;
    } catch (Exception $e) {
        error_log("Inventory update error: " . $e->getMessage());
        return 0;
    }
}

// Run the inventory update
$updatedEvents = updateInventoryAvailability($pdo);
// ==================== END AUTO-UPDATE ====================

// Get filters
$check_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$category = isset($_GET['category']) ? $_GET['category'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Main inventory query
$query = "SELECT i.*, 
    (i.quantity - COALESCE(
        (SELECT SUM(be.quantity) 
         FROM booking_equipment be 
         INNER JOIN bookings b ON be.booking_id = b.id
         WHERE be.equipment_id = i.id 
         AND b.status NOT IN ('Completed', 'Canceled')
         AND be.rental_start <= :check_date 
         AND be.rental_end >= :check_date), 0
    )) as available_quantity,
    COALESCE(
        (SELECT SUM(be.quantity) 
         FROM booking_equipment be 
         INNER JOIN bookings b ON be.booking_id = b.id
         WHERE be.equipment_id = i.id 
         AND b.status NOT IN ('Completed', 'Canceled')
         AND be.rental_start <= :check_date2 
         AND be.rental_end >= :check_date2), 0
    ) as booked_quantity
    FROM inventory i
    WHERE 1=1";

if ($category !== '') {
    $query .= " AND i.category = :category";
}
if ($status) {
    $query .= " AND i.status = :status";
}
$query .= " ORDER BY i.category, i.item_name";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':check_date', $check_date);
$stmt->bindParam(':check_date2', $check_date);
if ($category !== '') {
    $stmt->bindParam(':category', $category);
}
if ($status) {  
    $stmt->bindParam(':status', $status);
}
$stmt->execute();
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$categories = $pdo->query("SELECT DISTINCT category FROM inventory WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Get rental history
$historyQuery = "SELECT 
    i.item_name,
    CONCAT(u.bt_first_name, ' ', u.bt_last_name) as customer_name,
    be.rental_start,
    be.rental_end,
    be.quantity,
    b.status
FROM booking_equipment be
INNER JOIN inventory i ON be.equipment_id = i.id
INNER JOIN bookings b ON be.booking_id = b.id
INNER JOIN btuser u ON b.btuser_id = u.bt_user_id
ORDER BY be.rental_start DESC
LIMIT 10";

$historyStmt = $pdo->query($historyQuery);
$rentalHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$totalItems = $pdo->query("SELECT COUNT(*) FROM inventory")->fetchColumn();
$totalValue = $pdo->query("SELECT SUM(quantity * unit_price) FROM inventory")->fetchColumn();
$lowStockItems = $pdo->query("SELECT COUNT(*) FROM inventory WHERE available_quantity <= reorder_level AND available_quantity > 0")->fetchColumn();
$outOfStockItems = $pdo->query("SELECT COUNT(*) FROM inventory WHERE available_quantity = 0")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BTONE - Inventory Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-bg: #422b0d;
            --secondary-bg: #eae7de;
            --card-bg: #ffffff;
            --text-light: #ffffff;
            --text-dark: #000000;
            --accent: #A08963;
            --accent-dark: #8a745a;
            --highlight: #6b411e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--secondary-bg);
            color: var(--text-dark);
            min-height: 100vh;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--primary-bg);
            color: var(--text-light);
            height: 100vh;
            padding: 20px;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
        }

        .sidebar .logo {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--accent);
        }

        .sidebar .logo h2 {
            font-size: 24px;
            color: var(--text-light);
            margin: 0;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-menu li {
            margin: 8px 0;
        }

        .nav-menu li a {
            text-decoration: none;
            color: var(--text-light);
            padding: 12px 16px;
            display: block;
            border-radius: 6px;
            transition: background 0.3s;
            font-size: 14px;
        }

        .nav-menu li a:hover,
        .nav-menu li a.active {
            background-color: var(--accent-dark);
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
            width: calc(100% - 250px);
        }

        .section-header {
            margin: 30px 0 20px 0;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--accent);
        }

        .section-header h2 {
            color: var(--primary-bg);
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        /* Analytics Section */
        .analytics-section {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stats-card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stats-card h3 {
            margin-bottom: 10px;
            font-size: 16px;
            color: var(--highlight);
            font-weight: 600;
        }

        .stats-card p {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
            color: var(--primary-bg);
        }

        /* Filters */
        .filters {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            gap: 25px;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 200px;
        }

        .filter-group label {
            font-weight: 600;
            color: var(--highlight);
            font-size: 14px;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background: white;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        table thead {
            background: var(--primary-bg);
            color: var(--text-light);
        }

        table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            border: none;
        }

        table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        table tbody tr:hover {
            background-color: #f8f9fa;
        }

        table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status Badges - FIXED CLASS NAMES */
        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        .status-in {
            background: #d4edda;
            color: #155724;
        }

        .status-low {
            background: #fff3cd;
            color: #856404;
        }

        .status-out {
            background: #f8d7da;
            color: #721c24;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .analytics-section {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
                padding: 15px;
            }
            
            .main-content {
                margin-left: 200px;
                padding: 20px;
                width: calc(100% - 200px);
            }
            
            .filters {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .filter-group {
                min-width: auto;
            }
            
            table {
                font-size: 12px;
            }
            
            table th, table td {
                padding: 10px;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 60px;
                padding: 10px;
            }
            
            .sidebar .logo h2 {
                font-size: 0;
            }
            
            .sidebar .logo h2:after {
                content: "B";
                font-size: 20px;
            }
            
            .nav-menu li a span {
                display: none;
            }
            
            .main-content {
                margin-left: 60px;
                padding: 15px;
                width: calc(100% - 60px);
            }
            
            .analytics-section {
                grid-template-columns: 1fr;
            }
            
            .section-header h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="logo">
                <h2>BTONE Admin</h2>
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php"> Dashboard</a></li>
                <li><a href="add_user.php"> Admin Management</a></li>
                <li><a href="user_management.php"> User Management</a></li>
                <li><a href="calendar.php">Calendar</a></li>
                <li><a href="Inventory.php" class="active">Inventory</a></li>
                <li><a href="admin_management.php"> Edit</a></li>
                <li><a href="Index.php?logout=true">Logout</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <div class="section-header">
                <h2><i class="fas fa-boxes"></i> Equipment Inventory Overview</h2>
                <small style="color: #666; font-size: 12px;">Auto-updates every 5 minutes</small>
            </div>

            <!-- Analytics Section -->
            <div class="analytics-section">
                <div class="stats-card">
                    <h3>Total Items</h3>
                    <p><?php echo $totalItems; ?></p>
                </div>
                <div class="stats-card">
                    <h3>Total Value</h3>
                    <p>₱<?php echo number_format($totalValue, 2); ?></p>
                </div>
                <div class="stats-card">
                    <h3>Low Stock</h3>
                    <p style="color: #856404;"><?php echo $lowStockItems; ?></p>
                </div>
                <div class="stats-card">
                    <h3>Out of Stock</h3>
                    <p style="color: #721c24;"><?php echo $outOfStockItems; ?></p>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters">
                <div class="filter-group">
                    <label>Check Availability For:</label>
                    <input type="date" value="<?php echo $check_date; ?>" onchange="updateFilters('date', this.value)">
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

            <!-- Equipment Inventory Table -->
            <div class="section-header">
                <h2><i class="fas fa-tools"></i> Equipment Inventory</h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Total Qty</th>
                        <th>Available</th>
                        <th>Booked</th>
                        <th>Rental Rate</th>
                        <th>Supplier</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inventory)): ?>
                        <?php foreach($inventory as $item): 
                            $status_class = 'in';
                            $status_text = 'Available';
                            
                            if ($item['available_quantity'] <= 0) {
                                $status_class = 'out';
                                $status_text = 'Out of Stock';
                            } elseif ($item['available_quantity'] <= ($item['reorder_level'] ?? 5)) {
                                $status_class = 'low';
                                $status_text = 'Low Stock';
                            }
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['category'] ?? 'Uncategorized'); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td><strong><?php echo $item['available_quantity']; ?></strong></td>
                                <td><?php echo $item['booked_quantity']; ?></td>
                                <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['supplier'] ?? 'Not specified'); ?></td>
                                <td><?php echo htmlspecialchars($item['reorder_level'] ?? 5); ?></td>
                                <td>
                                    <span class="status status-<?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 30px;">No inventory items found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Rental History Section -->
            <div class="section-header">
                <h2><i class="fas fa-history"></i> Recent Rental History</h2>
            </div>

            <table>
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
                    <?php if (!empty($rentalHistory)): ?>
                        <?php foreach($rentalHistory as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($row['rental_start'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($row['rental_end'])); ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td>
                                    <span class="status <?php 
                                        echo $row['status'] === 'Approved' ? 'status-in' : 
                                             ($row['status'] === 'Pending' ? 'status-low' : 'status-out'); 
                                    ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px;">No rental history found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>

    <script>
    // Filter function
    function updateFilters(param, value) {
        const urlParams = new URLSearchParams(window.location.search);
        if (value) {
            urlParams.set(param, value);
        } else {
            urlParams.delete(param);
        }
        window.location.href = '?' + urlParams.toString();
    }

    // ==================== AUTO-REFRESH INVENTORY ====================
    let refreshCount = 0;
    const refreshInterval = 300000; // 5 minutes in milliseconds

    function autoRefreshInventory() {
        refreshCount++;
        console.log(`Auto-refreshing inventory... (Refresh #${refreshCount})`);
        
        // Show subtle notification
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            padding: 12px 16px;
            border-radius: 6px;
            z-index: 10000;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease-out;
        `;
        notification.innerHTML = 'Updating Inventory...';
        document.body.appendChild(notification);
        
        // Add CSS animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
        
        // Remove notification after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideIn 0.3s ease-out reverse';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }
        }, 3000);
        
        // Refresh page after 1 second (gives time to see notification)
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }

    // Start auto-refresh timer
    console.log(`Inventory auto-refresh enabled (${refreshInterval/60000} minute intervals)`);
    const refreshTimer = setInterval(autoRefreshInventory, refreshInterval);

    // Also refresh when user returns to the tab (after 30+ minutes away)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && refreshCount > 2) { // If user was away for a while
            console.log('User returned to tab, refreshing inventory...');
            autoRefreshInventory();
        }
    });

    // Display next refresh time
    const nextRefresh = new Date(Date.now() + refreshInterval);
    console.log(`Next auto-refresh at: ${nextRefresh.toLocaleTimeString()}`);
    // ==================== END AUTO-REFRESH ====================
    </script>
</body>
</html>