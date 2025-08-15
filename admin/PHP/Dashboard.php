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

// Fetch Customer Inquiries
function getBookings($pdo) {
    $stmt = $pdo->prepare("
        SELECT b.*, u.bt_first_name, u.bt_last_name, u.bt_email
        FROM bookings b
        JOIN btuser u ON b.btuser_id = u.bt_user_id
        ORDER BY b.btschedule DESC
        LIMIT 50
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
   $bookingData = getBookings($pdo);


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

// Fetch Data
$bookingData = getBookings($pdo);
$bookingAnalytics = getBookingAnalytics($pdo);
$monthlyBookings = getMonthlyBookings($pdo);
$archivedData =  getArchivedBookings($pdo);


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
// Add this line where you fetch your other data

$monthlyBookings = getMonthlyBookings($pdo);

// Add this with your other functions
function getMonthDetails($pdo, $month, $year)
{
    $stmt = $pdo->prepare("
        SELECT
        COUNT(*) AS total_bookings,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as confirmed_bookings,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_bookings,
        GROUP_CONCAT(DISTINCT btevent) as popular_packages
        FROM bookings
        WHERE MONTH(btschedule) = ? AND YEAR(btschedule) = ?
    ");
    $stmt->execute([$month, $year]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get current month's data
$currentMonth = date('n');
$currentYear = date('Y');
$currentMonthData = getMonthDetails($pdo, $currentMonth, $currentYear);

// Add this after your other functions
function getArchivedBookings($pdo)
{
    $stmt = $pdo->prepare("
        SELECT b.*, u.bt_first_name, u.bt_last_name, u.bt_email
        FROM bookings b
        JOIN btuser u ON b.btuser_id = u.bt_user_id
        WHERE status IN ('Canceled', 'Completed')
        ORDER BY b.btschedule DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Add this with your other data fetching
$archivedData = getArchivedBookings($pdo);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css">
    <link rel="stylesheet" href="assets_css\Admin_Dashboard.css">
    <style>
        body {
            font-family:
                <?php echo htmlspecialchars($userColors['font_family']); ?>
                , sans-serif;
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
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="add_user.php">Employee Management</a></li>
        <li><a href="calendar.php">Calendar</a></li>
        <li><a href="Inventory.php">Inventory</a></li>
        <li><a href="payment.php">Payments</a></li> <!-- Fixed line -->
        <li><a href="Settings.php">Settings</a></li>
        <li><a href="Index.php?logout=true">Logout</a></li>
    </ul>
</nav>

        <main class="main-content">
            <div class="analytics-section">
                <div class="chart-container">
                    <h4 >Event Bookings</h4>
                    <canvas id="bookingChart"></canvas>
                </div>
                <div class="month-details-container">
                    <div class="month-navigation">
                        <button id="prevMonth" class="nav-btn">&lt; Previous Month</button>
                        <h2 id="currentMonthDisplay"><?php echo date('F Y'); ?></h2>
                        <button id="nextMonth" class="nav-btn">Next Month &gt;</button>
                    </div>

                        <div class="month-stats">
                            <div class="stat-card">
                                <h3>Total Bookings</h3>
                                <p id="totalBookings">
                                    <?php echo $currentMonthData['total_bookings'] ?? 0; ?>
                                </p>
                            </div>
                            <div class="stat-card">
                                <h3>Confirmed Bookings</h3>
                                <p id="confirmedBookings">
                                    <?php echo $currentMonthData['confirmed_bookings'] ?? 0; ?>
                                </p>
                            </div>
                            <div class="stat-card">
                                <h3>Pending Bookings</h3>
                                <p id="pendingBookings">
                                    <?php echo $currentMonthData['pending_bookings'] ?? 0; ?>
                                </p>
                            </div>
                            <div class="stat-card">
                                <h3>Popular Packages</h3>
                                <p id="popularPackages">
                                    <?php echo $currentMonthData['popular_packages'] ?? 'N/A'; ?>
                                </p>
                            </div>
                        </div>
                <div class="chart-container">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
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
            <script src="asset_js/status.js"></script>
            <script src="asset_js/charts.js"></script>
            <script src="asset_js/modal.js"></script>
            
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archivedData as $archived): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($archived['bt_first_name'] . ' ' . $archived['bt_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($archived['btschedule']); ?></td>
                                    <td><?php echo htmlspecialchars($archived['status']); ?></td>
                                    <td><?php echo htmlspecialchars($archived['btevent']); ?></td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
</body>

</html>