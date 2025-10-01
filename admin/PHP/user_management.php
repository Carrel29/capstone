<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check session before proceeding
if (!isset($_SESSION['user_logged_in']) || !isset($_SESSION['bt_user_id']) || $_SESSION['role'] !== 'ADMIN') {
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

// Fetch all users with USER privilege
function getUsers($pdo) {
    $stmt = $pdo->prepare("
        SELECT u.bt_user_id, u.bt_first_name, u.bt_last_name, u.bt_email, u.bt_phone_number, u.bt_created_at
        FROM btuser u
        JOIN btuserprivilege p ON u.bt_privilege_id = p.bt_privilege_id
        WHERE p.bt_privilege_name = 'USER'
        ORDER BY u.bt_created_at DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch upcoming bookings for a specific user
function getUserUpcomingBookings($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT b.id, b.btevent, b.btschedule, b.status, b.total_cost
        FROM bookings b
        WHERE b.btuser_id = ? AND b.btschedule >= CURDATE() AND b.status != 'Canceled'
        ORDER BY b.btschedule ASC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch booking history for a specific user
function getUserBookingHistory($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT b.id, b.btevent, b.btschedule, b.btaddress, b.status, b.payment_status, b.total_cost, 
               s.GcashReferenceNo, s.AmountPaid, s.TotalAmount
        FROM bookings b
        LEFT JOIN sales s ON b.id = s.booking_id
        WHERE b.btuser_id = ?
        ORDER BY b.btschedule DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle AJAX request for booking history
if (isset($_GET['action']) && $_GET['action'] === 'get_booking_history' && isset($_GET['user_id'])) {
    $userId = intval($_GET['user_id']);
    $bookings = getUserBookingHistory($pdo, $userId);
    
    if (count($bookings) > 0) {
        echo '<table class="history-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Event</th>';
        echo '<th>Date</th>';
        echo '<th>Location</th>';
        echo '<th>Status</th>';
        echo '<th>Payment</th>';
        echo '<th>Total Cost</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($bookings as $booking) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($booking['btevent']) . '</td>';
            echo '<td>' . htmlspecialchars(date('M j, Y', strtotime($booking['btschedule']))) . '</td>';
            echo '<td>' . htmlspecialchars($booking['btaddress']) . '</td>';
            
            // Status badge
            $statusClass = 'status-' . strtolower($booking['status']);
            echo '<td><span class="status-badge ' . $statusClass . '">' . htmlspecialchars($booking['status']) . '</span></td>';
            
            // Payment status
            if (!empty($booking['GcashReferenceNo'])) {
                $paymentStatus = ($booking['AmountPaid'] >= $booking['TotalAmount']) ? 'Paid' : 'Partial';
                echo '<td><span class="gcash-reference">' . htmlspecialchars($booking['GcashReferenceNo']) . '</span><br><small>' . $paymentStatus . '</small></td>';
            } else {
                echo '<td><span class="not-paid">Not Paid</span></td>';
            }
            
            echo '<td>₱' . number_format($booking['total_cost'], 2) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<div class="no-bookings">';
        echo '<p>This user has no booking history.</p>';
        echo '</div>';
    }
    exit();
}

// Get data for main page
$users = getUsers($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - B'Tone Events</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{
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

        body{
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--secondary-bg);
            color: var(--text-dark);
            min-height: 100vh;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

      
/* Sidebar - Exact match to your original style */
.sidebar{
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

.nav-menu{
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-menu li{
    margin: 8px 0;
}

.nav-menu li a{
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

.main-content{
    margin-left: 250px;
    padding: 30px;
    min-height: 100vh;
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

        .section-header {
            margin: 0 0 25px 0;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--accent);
        }

        .section-header h2 {
            color: var(--primary-bg);
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .section-header p {
            color: var(--highlight);
            margin-top: 5px;
            font-size: 16px;
        }

        /* User Table Styles */
        .user-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .user-table thead {
            background: var(--primary-bg);
            color: var(--text-light);
        }
        
        .user-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            border: none;
        }
        
        .user-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        
        .user-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .user-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }
        
        .status-booked {
            background: #d4edda;
            color: #155724;
        }
        
        .status-none {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-canceled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .kebab-menu {
            position: relative;
            display: inline-block;
        }
        
        .kebab-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .kebab-btn:hover {
            background: rgba(0,0,0,0.05);
        }
        
        .kebab-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            min-width: 160px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 5px;
            z-index: 100;
            overflow: hidden;
        }
        
        .kebab-dropdown.show {
            display: block;
        }
        
        .kebab-item {
            padding: 10px 15px;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-size: 14px;
        }
        
        .kebab-item:hover {
            background: #f0f0f0;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 30px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .close-modal {
            font-size: 24px;
            cursor: pointer;
            color: var(--text-dark);
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .history-table th,
        .history-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .history-table th {
            background: var(--primary-bg);
            color: var(--text-light);
            font-weight: 600;
        }
        
        .history-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .no-bookings {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
        
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
        
        .gcash-reference {
            font-family: monospace;
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
        }
        
        .not-paid {
            color: #999;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
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
            
            .user-table {
                font-size: 12px;
            }
            
            .user-table th,
            .user-table td {
                padding: 10px;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 60px;
            }
            
            .nav-menu li a span {
                display: none;
            }
            
            .main-content {
                margin-left: 60px;
                padding: 15px;
                width: calc(100% - 60px);
            }
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
        <li><a href="add_user.php">Admin Management</a></li>
        <li><a href="user_management.php">User Management</a></li>
        <li><a href="calendar.php">Calendar</a></li>
        <li><a href="Inventory.php">Inventory</a></li>
        <li><a href="admin_management.php">Edit</a></li>
        <li><a href="Index.php?logout=true">Logout</a></li>
    </ul>
</nav>

        <main class="main-content">
            <div class="section-header">
                <h2>User Management</h2>
                <p>Manage customer accounts and view their booking history</p>
            </div>
            
            <div class="filters">
                <div class="filter-group">
                    <label for="search">Search Users</label>
                    <input type="text" id="search" placeholder="Search by name or email...">
                </div>
                <div class="filter-group">
                    <label for="bookingStatus">Booking Status</label>
                    <select id="bookingStatus">
                        <option value="all">All Users</option>
                        <option value="booked">Has Upcoming Event</option>
                        <option value="none">No Event Booked</option>
                    </select>
                </div>
            </div>
            
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Registration Date</th>
                        <th>Upcoming Event</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): 
                        $upcomingBooking = getUserUpcomingBookings($pdo, $user['bt_user_id']);
                    ?>
                    <tr class="user-row" data-user-id="<?php echo $user['bt_user_id']; ?>">
                        <td><?php echo htmlspecialchars($user['bt_first_name'] . ' ' . $user['bt_last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['bt_email']); ?></td>
                        <td><?php echo htmlspecialchars($user['bt_phone_number']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($user['bt_created_at'])); ?></td>
                        <td>
                            <?php if ($upcomingBooking): ?>
                                <span class="status-badge status-booked">
                                    <?php echo htmlspecialchars($upcomingBooking['btevent']); ?> - 
                                    <?php echo date('M j, Y', strtotime($upcomingBooking['btschedule'])); ?>
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-none">No event booked</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="kebab-menu">
                                <button class="kebab-btn" onclick="toggleKebabMenu(this)">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="kebab-dropdown">
                                    <button class="kebab-item" onclick="viewBookingHistory(<?php echo $user['bt_user_id']; ?>, '<?php echo htmlspecialchars($user['bt_first_name'] . ' ' . $user['bt_last_name']); ?>')">
                                        <i class="fas fa-history"></i> View Booking History
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
    
    <!-- Booking History Modal -->
    <div id="bookingHistoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalUserName">Booking History</h2>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            <div id="bookingHistoryContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>

    <script>
        // Toggle kebab menu
        function toggleKebabMenu(button) {
            const dropdown = button.nextElementSibling;
            const isShowing = dropdown.classList.contains('show');
            
            // Close all other dropdowns
            document.querySelectorAll('.kebab-dropdown.show').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
            
            // Toggle current dropdown if it wasn't already showing
            if (!isShowing) {
                dropdown.classList.add('show');
            }
            
            // Close dropdown when clicking elsewhere
            document.addEventListener('click', function closeDropdown(e) {
                if (!dropdown.contains(e.target) && e.target !== button) {
                    dropdown.classList.remove('show');
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }
        
        // View booking history
        function viewBookingHistory(userId, userName) {
            // Show loading state
            document.getElementById('bookingHistoryContent').innerHTML = '<p>Loading booking history...</p>';
            document.getElementById('modalUserName').textContent = 'Booking History - ' + userName;
            document.getElementById('bookingHistoryModal').style.display = 'flex';
            
            // Fetch booking history via AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'user_management.php?action=get_booking_history&user_id=' + userId, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    document.getElementById('bookingHistoryContent').innerHTML = xhr.responseText;
                } else {
                    document.getElementById('bookingHistoryContent').innerHTML = '<p>Error loading booking history.</p>';
                }
            };
            xhr.send();
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('bookingHistoryModal').style.display = 'none';
        }
        
        // Filter users
        document.getElementById('search').addEventListener('input', filterUsers);
        document.getElementById('bookingStatus').addEventListener('change', filterUsers);
        
        function filterUsers() {
            const searchTerm = document.getElementById('search').value.toLowerCase();
            const bookingStatus = document.getElementById('bookingStatus').value;
            
            document.querySelectorAll('.user-row').forEach(row => {
                const name = row.cells[0].textContent.toLowerCase();
                const email = row.cells[1].textContent.toLowerCase();
                const statusCell = row.cells[4];
                const hasBooking = statusCell.querySelector('.status-booked') !== null;
                
                const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
                const matchesStatus = 
                    bookingStatus === 'all' || 
                    (bookingStatus === 'booked' && hasBooking) ||
                    (bookingStatus === 'none' && !hasBooking);
                
                row.style.display = matchesSearch && matchesStatus ? '' : 'none';
            });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('bookingHistoryModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>