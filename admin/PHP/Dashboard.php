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

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $booking_id = $_POST['booking_id'];
        $new_status = $_POST['status'];
        $user_id = $_SESSION['bt_user_id'];
        
        try {
            // Get current booking details
            $stmt = $pdo->prepare("SELECT btschedule, status FROM bookings WHERE id = ?");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if cancellation is within 3 days
            if ($new_status === 'Canceled') {
                $event_date = new DateTime($booking['btschedule']);
                $current_date = new DateTime();
                $days_diff = $current_date->diff($event_date)->days;
                
                if ($days_diff < 3) {
                    echo json_encode(['success' => false, 'message' => 'Cancellations are only allowed 3 days before the event']);
                    exit;
                }
            }
            
            // Update status
            $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $booking_id]);
            
            // If status is Completed or Canceled, move to archive (you might want to create an archived_bookings table)
            if ($new_status === 'Completed' || $new_status === 'Canceled') {
                // Here you can implement archiving logic if needed
                // For now, we'll just update the status
            }
            
            echo json_encode(['success' => true]);
            exit;
            
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }
}

// Fetch Customer Inquiries with Payment Data - FIXED QUERY
function getBookings($pdo) {
    $stmt = $pdo->prepare("
        SELECT 
            b.*, 
            u.bt_first_name, 
            u.bt_last_name, 
            u.bt_email,
            -- Get the latest GCash reference number
            (SELECT s1.GcashReferenceNo 
             FROM sales s1 
             WHERE s1.booking_id = b.id 
             ORDER BY s1.DateCreated DESC 
             LIMIT 1) as GcashReferenceNo,
            -- Get total amount paid across all payments
            COALESCE(SUM(s2.AmountPaid), 0) as TotalAmountPaid,
            -- Get the total cost of the booking
            b.total_cost as TotalAmount
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
        WHERE status NOT IN ('Canceled', 'Completed')
        GROUP BY btevent
        ORDER BY booking_count DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMonthlyBookings($pdo)
{
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(btschedule, '%Y-%m') AS month, COUNT(*) AS booking_count
        FROM bookings
        WHERE btschedule >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        AND status NOT IN ('Canceled', 'Completed')
        GROUP BY DATE_FORMAT(btschedule, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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
        AND b.status NOT IN ('Canceled', 'Completed')
    ");
    $stmt->execute([$month, $year]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getArchivedBookings($pdo)
{
    $stmt = $pdo->prepare("
        SELECT 
            b.*, 
            u.bt_first_name, 
            u.bt_last_name, 
            u.bt_email,
            -- Get the latest GCash reference number
            (SELECT s1.GcashReferenceNo 
             FROM sales s1 
             WHERE s1.booking_id = b.id 
             ORDER BY s1.DateCreated DESC 
             LIMIT 1) as GcashReferenceNo,
            -- Get total amount paid across all payments
            COALESCE(SUM(s2.AmountPaid), 0) as TotalAmountPaid,
            -- Get the total cost of the booking
            b.total_cost as TotalAmount
        FROM bookings b
        JOIN btuser u ON b.btuser_id = u.bt_user_id
        LEFT JOIN sales s2 ON b.id = s2.booking_id
        WHERE b.status IN ('Canceled', 'Completed')
        GROUP BY b.id, u.bt_first_name, u.bt_last_name, u.bt_email, b.total_cost
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        /* Charts Section - Much Smaller Charts */
.charts-section {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}

.chart-card {
    background: var(--card-bg);
    border-radius: 8px;
    padding: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    flex: 1;
}

.chart-card h4 {
    margin: 0 0 8px 0;
    font-size: 13px;
    color: var(--highlight);
    text-align: center;
    font-weight: 600;
}

.chart-card canvas {
    width: 100% !important;
    height: 150px !important; /* Much smaller */
    max-height: 150px;
}

/* For extra small charts */
.charts-section.compact .chart-card canvas {
    height: 120px !important;
    max-height: 120px;
}

/* If still too big, try this ultra-compact version */
.charts-section.ultra-compact {
    gap: 10px;
}

.charts-section.ultra-compact .chart-card {
    padding: 8px;
}

.charts-section.ultra-compact .chart-card canvas {
    height: 100px !important;
    max-height: 100px;
}

.charts-section.ultra-compact .chart-card h4 {
    font-size: 12px;
    margin-bottom: 5px;
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
                        <td><?php echo htmlspecialchars($booking['btevent']); ?></td>
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
                            <?php if ($booking['TotalAmountPaid'] > 0): ?>
                                ₱<?php echo number_format($booking['TotalAmountPaid'], 2); ?>
                                <?php if ($booking['TotalAmountPaid'] < $booking['TotalAmount']): ?>
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
                
                // Status update variables
                let pendingBookingId = null;
                let pendingNewStatus = null;
                
                function confirmStatusChange(bookingId, newStatus) {
                    pendingBookingId = bookingId;
                    pendingNewStatus = newStatus;
                    
                    const modal = document.getElementById('confirmationModal');
                    const title = document.getElementById('confirmationTitle');
                    const message = document.getElementById('confirmationMessage');
                    
                    title.textContent = `Change to ${newStatus}`;
                    message.textContent = `Are you sure you want to change this booking to ${newStatus}?`;
                    
                    modal.style.display = 'block';
                }
                
                function updateStatus(bookingId, newStatus) {
                    const formData = new FormData();
                    formData.append('action', 'update_status');
                    formData.append('booking_id', bookingId);
                    formData.append('status', newStatus);
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload(); // Reload to show updated status
                        } else {
                            alert(data.message || 'Error updating status');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error updating status');
                    });
                }
                
                function toggleKebabMenu(button) {
                    const dropdown = button.nextElementSibling;
                    dropdown.classList.toggle('show');
                    
                    // Close other dropdowns
                    document.querySelectorAll('.kebab-dropdown').forEach(drop => {
                        if (drop !== dropdown) {
                            drop.classList.remove('show');
                        }
                    });
                }
                
                // Close dropdowns when clicking outside
                document.addEventListener('click', function(event) {
                    if (!event.target.matches('.kebab-btn')) {
                        document.querySelectorAll('.kebab-dropdown').forEach(dropdown => {
                            dropdown.classList.remove('show');
                        });
                    }
                });
                
                // Confirmation modal handlers
                document.getElementById('confirmYes').addEventListener('click', function() {
                    if (pendingBookingId && pendingNewStatus) {
                        updateStatus(pendingBookingId, pendingNewStatus);
                    }
                    document.getElementById('confirmationModal').style.display = 'none';
                });
                
                document.getElementById('confirmNo').addEventListener('click', function() {
                    document.getElementById('confirmationModal').style.display = 'none';
                    pendingBookingId = null;
                    pendingNewStatus = null;
                });
                
                // Close modal when clicking outside
                window.addEventListener('click', function(event) {
                    const modal = document.getElementById('confirmationModal');
                    if (event.target === modal) {
                        modal.style.display = 'none';
                        pendingBookingId = null;
                        pendingNewStatus = null;
                    }
                });
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
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($archived['status']); ?>">
                                            <?php echo htmlspecialchars($archived['status']); ?>
                                        </span>
                                    </td>
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
                                        <?php if ($archived['TotalAmountPaid'] > 0): ?>
                                            ₱<?php echo number_format($archived['TotalAmountPaid'], 2); ?>
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
        </main>
    </div>

    <script>
    // Month Navigation Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const prevMonthBtn = document.getElementById('prevMonth');
        const nextMonthBtn = document.getElementById('nextMonth');
        const currentMonthDisplay = document.getElementById('currentMonthDisplay');
        
        let currentDate = new Date();
        
        // Function to update dashboard for selected month
        function updateDashboardForMonth(year, month) {
            console.log('Updating dashboard for:', year, month);
            
            // Show loading state
            document.getElementById('totalBookings').textContent = 'Loading...';
            document.getElementById('confirmedBookings').textContent = 'Loading...';
            document.getElementById('pendingBookings').textContent = 'Loading...';
            document.getElementById('popularPackages').textContent = 'Loading...';
            
            // Add loading class to navigation
            document.querySelector('.month-navigation').classList.add('loading');
            
            // Make AJAX request to get month data
            fetch('get_month_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'month=' + encodeURIComponent(month) + 
                      '&year=' + encodeURIComponent(year)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update statistics
                    document.getElementById('totalBookings').textContent = data.total_bookings || 0;
                    document.getElementById('confirmedBookings').textContent = data.confirmed_bookings || 0;
                    document.getElementById('pendingBookings').textContent = data.pending_bookings || 0;
                    document.getElementById('popularPackages').textContent = data.popular_packages || 'N/A';
                    
                    // Update month display
                    const monthNames = ["January", "February", "March", "April", "May", "June",
                        "July", "August", "September", "October", "November", "December"
                    ];
                    currentMonthDisplay.textContent = `${monthNames[month-1]} ${year}`;
                    
                    showNotification(`Now viewing ${monthNames[month-1]} ${year}`, 'success');
                } else {
                    showNotification('Error loading month data: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error loading month data', 'error');
            })
            .finally(() => {
                // Remove loading class
                document.querySelector('.month-navigation').classList.remove('loading');
            });
        }
        
        // Previous Month button click
        if (prevMonthBtn) {
            prevMonthBtn.addEventListener('click', function() {
                currentDate.setMonth(currentDate.getMonth() - 1);
                const year = currentDate.getFullYear();
                const month = currentDate.getMonth() + 1; // JavaScript months are 0-indexed
                
                updateDashboardForMonth(year, month);
            });
        }
        
        // Next Month button click
        if (nextMonthBtn) {
            nextMonthBtn.addEventListener('click', function() {
                currentDate.setMonth(currentDate.getMonth() + 1);
                const year = currentDate.getFullYear();
                const month = currentDate.getMonth() + 1;
                
                updateDashboardForMonth(year, month);
            });
        }
    });

    function updateStatus(bookingId, newStatus, event) {
        if (!newStatus) return;
        
        console.log('Starting updateStatus:', { bookingId, newStatus });

        const statusCell = document.querySelector('.status-cell-' + bookingId);
        if (!statusCell) {
            console.error('Status cell not found for booking:', bookingId);
            showNotification('Error: Could not find booking element', 'error');
            return;
        }

        const originalStatus = statusCell.textContent;
        statusCell.textContent = 'Updating...';
        statusCell.style.color = '#ff9800';

        fetch('/capstone/admin/php/update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + encodeURIComponent(bookingId) + 
                  '&status=' + encodeURIComponent(newStatus)
        })
        .then(response => {
            console.log('Response received, status:', response.status);
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Parsed response data:', data);
            if (data.success) {
                statusCell.textContent = newStatus;
                statusCell.style.color = getStatusColor(newStatus);
                showNotification('Status updated successfully!', 'success');
                
                // Reset the dropdown - now using the event parameter
                if (event && event.target) {
                    event.target.value = '';
                }
                
                // Refresh page after delay to update analytics
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                statusCell.textContent = originalStatus;
                statusCell.style.color = getStatusColor(originalStatus);
                showNotification('Error: ' + (data.error || 'Update failed'), 'error');
            }
        })
        .catch(error => {
            console.error('Fetch error details:', error);
            statusCell.textContent = originalStatus;
            statusCell.style.color = getStatusColor(originalStatus);
            showNotification('Network error: ' + error.message, 'error');
        });
    }

    function getStatusColor(status) {
        const colors = {
            'Pending': '#ff9800',
            'Approved': '#4caf50',
            'Canceled': '#f44336',
            'Completed': '#2196f3'
        };
        return colors[status] || '#666';
    }

    function showNotification(message, type) {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notification => notification.remove());

        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 4px;
            color: white;
            z-index: 1000;
            font-weight: bold;
            transition: opacity 0.3s;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    // Modal functionality
    document.addEventListener('DOMContentLoaded', function() {
        const historyButton = document.getElementById('historyButton');
        const historyModal = document.getElementById('historyModal');
        const closeModal = document.querySelector('.close-modal');
        const clearArchiveBtn = document.getElementById('clearArchiveBtn');

        if (historyButton && historyModal) {
            historyButton.addEventListener('click', function() {
                historyModal.style.display = 'block';
            });

            closeModal.addEventListener('click', function() {
                historyModal.style.display = 'none';
            });

            window.addEventListener('click', function(event) {
                if (event.target === historyModal) {
                    historyModal.style.display = 'none';
                }
            });
        }

        if (clearArchiveBtn) {
            clearArchiveBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to clear all archived inquiries? This action cannot be undone.')) {
                    // Add your clear archive logic here
                    showNotification('Archive cleared successfully!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
            });
        }
    });
    </script>
</body>
</html>