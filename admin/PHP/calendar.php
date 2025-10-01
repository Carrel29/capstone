<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check admin authentication
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: Index.php");
    exit();
}

require_once "../../Client/php/calendar-utils.php";
// Use the same PDO connection as in your dashboard
if (!isset($pdo)) {
    // If $pdo not defined, connect (for direct access)
    $host = 'localhost';
    $db   = 'btonedatabase';
    $user = 'root';
    $pass = '';
    $port = '3306';
    $charset = 'utf8mb4';
    $dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        exit("Database connection failed: " . $e->getMessage());
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['status'])) {
    $stmt = $pdo->prepare("UPDATE bookings SET status=? WHERE id=?");
    $stmt->execute([$_POST['status'], $_POST['booking_id']]);
    header("Location: ".$_SERVER['REQUEST_URI']);
    exit;
}

$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$bookings = getBookingsForMonth($pdo, $year, str_pad($month,2,"0",STR_PAD_LEFT));
$firstDay = date('N', strtotime("$year-$month-01"));
$daysInMonth = date('t', strtotime("$year-$month-01"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BTONE - Calendar Management</title>
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

        /* Calendar Styles - EXACTLY like booking-form.php */
        .calendar-main {
            background: var(--card-bg);
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(141,110,99,0.15);
            padding: 32px;
            margin-bottom: 30px;
            border: 1px solid #d7ccc8;
        }

        .calendar-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            margin-bottom: 14px;
        }

        .calendar-arrow {
            background: none;
            border: none;
            font-size: 1.7rem;
            color: #8d6e63;
            cursor: pointer;
            transition: all 0.18s;
            padding: 5px 15px;
            border-radius: 5px;
        }

        .calendar-arrow:hover {
            color: #5d4037;
            background: #e8e2da;
            transform: scale(1.1);
        }

        .calendar-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 6px;
            text-align: center;
            background: #f5f1e8;
            border-radius: 12px;
            overflow: hidden;
        }

        .calendar-table th {
            background: #8d6e63;
            color: #fff;
            padding: 12px 0;
            font-weight: 500;
            letter-spacing: 1px;
            border-radius: 6px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }

        /* PROPER CALENDAR COLORS - EXACTLY like booking-form.php */
        .calendar-day {
            background: #75d377; /* GREEN - Available */
            color: #fff;
            padding: 13px 0;
            border-radius: 7px;
            cursor: pointer;
            font-size: 1.18rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }

        .calendar-day[data-color="yellow"] {
            background: #ffe066; /* YELLOW - Partially Booked */
            color: #5e5e00;
        }

        .calendar-day[data-color="red"] {
            background: #f36c6c; /* RED - Fully Booked */
            color: #fff;
        }

        .calendar-day:hover {
            transform: scale(1.06);
            background: #36A2EB !important;
            color: #fff !important;
        }

        .legend {
            margin: 18px 0 0 0;
            text-align: center;
        }

        .legend span {
            display: inline-block;
            margin: 0 10px;
            padding: 3px 15px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            text-shadow: 1px 1px 1px rgba(0,0,0,0.1);
        }

        .legend .available { background: #75d377; color: #fff; }
        .legend .partial { background: #ffe066; color: #5e5e00; }
        .legend .full { background: #f36c6c; color: #fff; }

        /* Modal Styles */
        .modal-bg {
            display: none;
            position: fixed;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal {
            background: var(--card-bg);
            padding: 24px 20px 16px 20px;
            border-radius: 12px;
            max-width: 500px;
            margin: 7% auto 0 auto;
            position: relative;
            box-shadow: 0 6px 30px rgba(54,162,235,0.13);
        }

        .modal h4 {
            color: var(--primary-bg);
            margin-bottom: 20px;
            font-size: 20px;
            border-bottom: 2px solid var(--accent);
            padding-bottom: 10px;
        }

        .close-modal-btn {
            background: #8d6e63;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 15px;
        }

        .close-modal-btn:hover {
            background: #6d4c41;
        }

        .booking-entry {
            border-bottom: 1px solid #f0f0f0;
            margin-bottom: 12px;
            padding-bottom: 9px;
        }

        .booking-entry:last-child {
            border-bottom: none;
        }

        .booking-label {
            font-weight: 600;
            color: var(--highlight);
        }

        select {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
        }

        /* Responsive */
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
            
            .calendar-main {
                padding: 15px;
            }
            
            .calendar-table th,
            .calendar-table td {
                padding: 10px 5px;
                font-size: 12px;
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
            
            .calendar-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .section-header h2 {
                font-size: 20px;
            }
        }

        @media(max-width:650px){ 
            .calendar-main {
                max-width: 99vw;
                padding: 12px;
            } 
        }
        
        @media(max-width:500px){ 
            .modal {
                max-width: 98vw;
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
                <li><a href="calendar.php" class="active">Calendar</a></li>
                <li><a href="Inventory.php">Inventory</a></li>
                <li><a href="admin_management.php">Edit</a></li>
                <li><a href="Index.php?logout=true">Logout</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <div class="section-header">
                <h2>Event Calendar</h2>
            </div>

            <div class="calendar-main">
                <div class="calendar-header">
                    <form style="display:inline;" method="get">
                        <input type="hidden" name="year" value="<?=$month==1?$year-1:$year;?>">
                        <input type="hidden" name="month" value="<?=$month==1?12:$month-1;?>">
                        <button class="calendar-arrow" type="submit">&#8592;</button>
                    </form>
                    <span style="font-size:1.35rem;font-weight:600;color:#6d4c41;">
                        <?=date('F Y', strtotime("$year-$month-01"));?>
                    </span>
                    <form style="display:inline;" method="get">
                        <input type="hidden" name="year" value="<?=$month==12?$year+1:$year;?>">
                        <input type="hidden" name="month" value="<?=$month==12?1:$month+1;?>">
                        <button class="calendar-arrow" type="submit">&#8594;</button>
                    </form>
                </div>
                <table class="calendar-table">
                    <tr>
                        <th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th>
                    </tr>
                    <tr>
                        <?php
                        for ($blank=1; $blank<$firstDay; $blank++) echo "<td></td>";
                        for ($day=1, $cell=$firstDay; $day<=$daysInMonth; $day++, $cell++) {
                            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                            $color = isset($bookings[$dateStr]) ? getDayColor($bookings[$dateStr]) : 'green';
                            $data_color = $color;
                            if ($color == 'yellow') $data_color = 'yellow';
                            elseif ($color == 'red') $data_color = 'red';
                            else $data_color = 'green';
                            echo "<td class='calendar-day' data-color='$data_color' onclick=\"showModal('$dateStr')\">$day</td>";
                            if ($cell%7==0) echo "</tr><tr>";
                        }
                        ?>
                    </tr>
                </table>
                <div class="legend">
                    <span class="available">Available</span>
                    <span class="partial">Partially Booked</span>
                    <span class="full">Fully Booked</span>
                </div>
            </div>
        </main>
    </div>

    <?php
    for ($day=1; $day<=$daysInMonth; $day++) {
        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
        echo "<div class='modal-bg' id='modal-bg-$dateStr' onclick=\"hideModal('$dateStr')\"><div class='modal' onclick=\"event.stopPropagation()\">";
        echo "<h4>Bookings for ".date('F d, Y',strtotime($dateStr))."</h4>";
        if (isset($bookings[$dateStr]) && count($bookings[$dateStr]) > 0) {
            foreach ($bookings[$dateStr] as $b) {
                echo "<div class='booking-entry'>";
                echo "<span class='booking-label'>Event:</span> ".htmlspecialchars($b['btevent'])."<br>";
                echo "<span class='booking-label'>Time:</span> ".date('H:i',strtotime($b['btschedule']))."<br>";
                echo "<span class='booking-label'>Status:</span> ";
                echo "<form method='post' style='display:inline;'>";
                echo "<input type='hidden' name='booking_id' value='".$b['id']."'>";
                echo "<select name='status' onchange='this.form.submit()'>";
                foreach (['Pending','Approved','Canceled','Completed'] as $status) {
                    $sel = $b['status']==$status ? 'selected' : '';
                    echo "<option value='$status' $sel>$status</option>";
                }
                echo "</select></form><br>";
                echo "<span class='booking-label'>Customer:</span> ".htmlspecialchars($b['bt_first_name']." ".$b['bt_last_name'])."<br>";
                echo "<span class='booking-label'>Email:</span> ".htmlspecialchars($b['bt_email'])."<br>";
                echo "<span class='booking-label'>Phone:</span> ".htmlspecialchars($b['bt_phone_number']);
                echo "</div>";
            }
        } else {
            echo "<p>No bookings for this date.</p>";
        }
        echo "<button class='close-modal-btn' onclick=\"hideModal('$dateStr')\">Close</button>";
        echo "</div></div>";
    }
    ?>

    <script>
    function showModal(id) {
        document.getElementById('modal-bg-'+id).style.display = 'block';
    }
    function hideModal(id) {
        document.getElementById('modal-bg-'+id).style.display = 'none';
    }
    </script>
</body>
</html>