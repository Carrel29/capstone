<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    session_start();

    // Set default redirect URL
    $redirectUrl = "Index.php"; // Default fallback

    try {
        $db = new PDO("mysql:host=localhost;dbname=capstone", "root", "");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }

    // User role check for redirect
    if (isset($_SESSION['user_id'])) {
        try {
            // Using the existing database connection
            $query = "SELECT role FROM admin_users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute(['id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Determine redirect URL based on role
            if ($user && $user['role'] === 'admin') {
                $redirectUrl = "Dashboard.php";
            } else {
                $redirectUrl = "employee_dashboard.php";
            }
        } catch (PDOException $e) {
            // In case of error, log it (redirect stays as login.php)
            error_log("Role check failed: " . $e->getMessage());
        }
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

    class Calendar {
        private $db;

        public function __construct($db) {
            $this->db = $db;
        }

        public function getMonthEvents($year, $month) {
            $query = "SELECT ci.*, 
                TIME_FORMAT(event_time, '%H:%i') as formatted_time,
                CASE 
                    WHEN TIME(event_time) < '12:00:00' THEN 'Morning'
                    WHEN TIME(event_time) < '17:00:00' THEN 'Afternoon'
                    ELSE 'Evening'
                END as time_slot
            FROM customer_inquiries ci 
            WHERE YEAR(event_date) = :year 
            AND MONTH(event_date) = :month
            AND status NOT IN ('Cancelled', 'Completed')
            ORDER BY event_date, event_time";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute(['year' => $year, 'month' => $month]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getDayEvents($date) {
            $query = "SELECT * FROM customer_inquiries 
                    WHERE DATE(event_date) = :date
                    ORDER BY event_date";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['date' => $date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    $calendar = new Calendar($db);

    // Handle AJAX requests
    if (isset($_GET['year']) && isset($_GET['month'])) {
        header('Content-Type: application/json');
        $events = $calendar->getMonthEvents($_GET['year'], $_GET['month']);
        echo json_encode($events);
        exit;
    } elseif (isset($_GET['date'])) {
        header('Content-Type: application/json');
        $events = $calendar->getDayEvents($_GET['date']);
        echo json_encode($events);
        exit;
    }
?>

    <!DOCTYPE html>
    <html>
    <head>
        <title>Event Calendar</title>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <link rel="stylesheet" href="assets_css/calendar.css">
        <style>
           body{
            font-family: <?php echo htmlspecialchars($userColors['font_family']); ?>, sans-serif;
           }
           .calendar{
            font-family: <?php echo $userColors['font_family']; ?>;
           }
        </style>
    </head>
    <body>

    <div class="calendar-container">
        <div class="calendar"></div>
        <div class="instructions">
            <h3>Booking Status:</h3>
            <p style="background:#90EE90;padding:10px;">Green: No issues</p>
            <p style="background:#FFA500;padding:10px;">Orange: Two bookings</p>
            <p style="background:#FF6347;padding:10px;">Red: Conflict</p>
        </div>
    </div>

    <a href="<?php echo $redirectUrl; ?>" class="back-button">←</a>
        <div class="calendar"></div>
        
        <div id="eventModal" class="modal">
            <div class="modal-content">
                <h2>Events for <span id="selectedDate"></span></h2>
                <div id="eventsList"></div>
                <button onclick="closeModal()">Close</button>
            </div>
        </div>

        <script src="asset_js/calendar.js"></script>
 
    </body>
    </html>