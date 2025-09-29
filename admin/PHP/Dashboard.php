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

// Fetch Customer Inquiries with Payment Data
function getBookings($pdo) {
    $stmt = $pdo->prepare("
        SELECT b.*, u.bt_first_name, u.bt_last_name, u.bt_email,
               s.GcashReferenceNo, s.AmountPaid, s.TotalAmount, s.DateCreated as payment_date
        FROM bookings b
        JOIN btuser u ON b.btuser_id = u.bt_user_id
        LEFT JOIN sales s ON b.id = s.booking_id
        ORDER BY b.btschedule DESC
        LIMIT 50
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch Booking Analytics
function getBookingAnalytics($pdo)
{
    $stmt = $pdo->prepare("
        SELECT btevent, COUNT(*) AS booking_count
        FROM bookings
        GROUP BY btevent
        ORDER BY booking_count DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Add this function with your other functions
function getMonthlyBookings($pdo)
{
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(btschedule, '%Y-%m') AS month, COUNT(*) AS booking_count
        FROM bookings
        WHERE btschedule >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(btschedule, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Add this with your other functions
function getMonthDetails($pdo, $month, $year)
{
    $stmt = $pdo->prepare("
        SELECT
        COUNT(*) AS total_bookings,
        SUM(CASE WHEN b.status = 'Approved' THEN 1 ELSE 0 END) as confirmed_bookings,
        SUM(CASE WHEN b.status = 'Pending' THEN 1 ELSE 0 END) as pending_bookings,
        GROUP_CONCAT(DISTINCT btevent) as popular_packages
        FROM bookings b
        WHERE MONTH(b.btschedule) = ? AND YEAR(b.btschedule) = ?
    ");
    $stmt->execute([$month, $year]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Add this after your other functions - FIXED THE AMBIGUOUS COLUMN ERROR
function getArchivedBookings($pdo)
{
    $stmt = $pdo->prepare("
        SELECT b.*, u.bt_first_name, u.bt_last_name, u.bt_email,
               s.GcashReferenceNo, s.AmountPaid, s.TotalAmount
        FROM bookings b
        JOIN btuser u ON b.btuser_id = u.bt_user_id
        LEFT JOIN sales s ON b.id = s.booking_id
        WHERE b.status IN ('Canceled', 'Completed')
        ORDER BY b.btschedule DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch Data
$bookingData = getBookings($pdo);
$bookingAnalytics = getBookingAnalytics($pdo);
$monthlyBookings = getMonthlyBookings($pdo);
$archivedData = getArchivedBookings($pdo);

// Get current month's data
$currentMonth = date('n');
$currentYear = date('Y');
$currentMonthData = getMonthDetails($pdo, $currentMonth, $currentYear);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css">
    <link rel="stylesheet" href="../assets_css/admin.css">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        
        .gcash-reference {
            font-family: monospace;
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
        }
        
        .payment-status {
            font-size: 11px;
            display: block;
            margin-top: 2px;
        }
        
        .paid-full {
            color: #4caf50;
        }
        
        .paid-partial {
            color: #ff9800;
        }
        
        .not-paid {
            color: #999;
            font-style: italic;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
    <nav class="sidebar">
    <div class="logo">
        <h2>Admin Dashboard</h2>
    </div>
    <ul class="nav-menu">
        <li><a href="dashboard.php" >Dashboard</a></li>
        <li><a href="add_user.php">Admin Management</a></li>
        <li><a href="user_management">User Management</a></li>
        <li><a href="calendar.php">Calendar</a></li>
        <li><a href="Inventory.php">Inventory</a></li>
        <li><a href="admin_management.php">Edit</a></li>
        <li><a href="Index.php?logout=true">Logout</a></li>
    </ul>
</nav>

        <main class="main-content">
            <div class="analytics-section">
    <div class="stat-card">
        <h3>Total Bookings</h3>
        <p id="totalBookings"><?php echo $currentMonthData['total_bookings'] ?? 0; ?></p>
    </div>
    <div class="stat-card">
        <h3>Confirmed</h3>
        <p id="confirmedBookings"><?php echo $currentMonthData['confirmed_bookings'] ?? 0; ?></p>
    </div>
    <div class="stat-card">
        <h3>Pending</h3>
        <p id="pendingBookings"><?php echo $currentMonthData['pending_bookings'] ?? 0; ?></p>
    </div>
    <div class="stat-card">
        <h3>Popular Packages</h3>
        <p id="popularPackages"><?php echo $currentMonthData['popular_packages'] ?? 'N/A'; ?></p>
    </div>
</div>

<!-- Charts Row -->
<div class="charts-section">
    <div class="chart-card">
        <h4>Event Bookings</h4>
        <canvas id="bookingChart"></canvas>
    </div>
    <div class="chart-card">
        <h4>Monthly Trends</h4>
        <canvas id="monthlyTrendChart"></canvas>
    </div>
</div>
<!-- Month Navigation -->
<div class="month-navigation">
    <button id="prevMonth" class="nav-btn">&lt; Previous Month</button>
    <h2 id="currentMonthDisplay"><?php echo date('F Y'); ?></h2>
    <button id="nextMonth" class="nav-btn">Next Month &gt;</button>
</div>

            <div class="table-header">
                <h3>Active Inquiries</h3>
                <div class="table-actions">
                    <button id="historyButton" class="history-button">View Archived Inquiries</button>
                    <span id="refreshIndicator" class="refresh-indicator">Table updated</span>
                </div>
            </div>
            <table class="customer-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date of Inquiry</th>
                        <th>Status</th>
                        <th>Package</th>
                        <th>GCash Reference</th>
                        <th>Amount Paid</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                  <?php foreach ($bookingData as $booking): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booking['bt_first_name'] . ' ' . $booking['bt_last_name']); ?></td>
                        <td><?php echo htmlspecialchars($booking['btschedule']);?></td>
                        <td class="status-cell-<?php echo htmlspecialchars($booking['id']); ?>">
                        <?php echo htmlspecialchars($booking['status']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($booking['btevent']);?></td>
                        <td>
                            <?php if (!empty($booking['GcashReferenceNo'])): ?>
                                <span class="gcash-reference">
                                    <?php echo htmlspecialchars($booking['GcashReferenceNo']); ?>
                                </span>
                            <?php else: ?>
                                <span class="not-paid">Not Paid</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($booking['AmountPaid'])): ?>
                                ₱<?php echo number_format($booking['AmountPaid'], 2); ?>
                                <?php if ($booking['AmountPaid'] < $booking['TotalAmount']): ?>
                                    <span class="payment-status paid-partial">(Partial)</span>
                                <?php else: ?>
                                    <span class="payment-status paid-full">(Full)</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="not-paid">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <select class="status-select" onchange="updateStatus('<?php echo htmlspecialchars($booking['id']);?>', this.value)">
                                <option value=""> Change Status</option>
                                    <?php
                                        $statuses = ['Pending', 'Approved', 'Canceled', 'Completed'];
                                        foreach ($statuses as$statusOption):
                                            $selected = ($booking['status'] === $statusOption) ? 'selected' :'';
                                    ?>
                                    <option value="<?php echo $statusOption; ?>" <?php echo $selected; ?>>
                                        <?php echo $statusOption; ?>
                                    </option>
                                    <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>


            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>    
                // Booking Analytics Chart
                const monthlyData = <?php echo json_encode($monthlyBookings); ?>;               
                const bookingAnalytics = <?php echo json_encode($bookingAnalytics); ?>;
            </script>
            <script src="../asset_js/dashboard.js"></script>
  
            <?php include __DIR__ . '/../includes/admin_list.php'; ?>    

            <div id="historyModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <span class="close-modal">&times;</span>
                        <h2 class="modal-title">Archived Inquiries History</h2>
                        <button id="clearArchiveBtn" class="clear-archive-btn">Clear All</button>
                    </div>
                    <table class="modal-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Date of Inquiry</th>
                                <th>Status</th>
                                <th>Package</th>
                                <th>GCash Reference</th>
                                <th>Amount Paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archivedData as $archived): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($archived['bt_first_name'] . ' ' . $archived['bt_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($archived['btschedule']); ?></td>
                                    <td><?php echo htmlspecialchars($archived['status']); ?></td>
                                    <td><?php echo htmlspecialchars($archived['btevent']); ?></td>
                                    <td>
                                        <?php if (!empty($archived['GcashReferenceNo'])): ?>
                                            <span class="gcash-reference">
                                                <?php echo htmlspecialchars($archived['GcashReferenceNo']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="not-paid">Not Paid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($archived['AmountPaid'])): ?>
                                            ₱<?php echo number_format($archived['AmountPaid'], 2); ?>
                                        <?php else: ?>
                                            <span class="not-paid">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
</body>

</html>