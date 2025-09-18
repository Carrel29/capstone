<?php
session_start();
include_once "../includes/loginSession.php";
include_once "../includes/userData.php";
include_once "../includes/allData.php";
require_once "calendar-utils.php"; 

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit();
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add to cart functionality
if (isset($_GET['add_to_cart']) && $_GET['add_to_cart'] == 'true') {
    $packageData = [
        'event_type' => $_POST['event_type'],
        'date' => $_POST['date'],
        'time' => $_POST['time'],
        'duration' => $_POST['duration'],
        'attendees' => $_POST['attendees'],
        'address' => $_POST['address'],
        'message' => $_POST['message'],
        'package_details' => $_POST['package_details'],
        'total_price' => $_POST['total_price']
    ];
    
    $_SESSION['cart'][] = $packageData;
    header("Location: user_cart.php");
    exit();
}

// Database Connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'btonedatabase';
$port = '3306';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;port=$port", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle AJAX request for calendar data
if (isset($_GET['ajax']) && $_GET['ajax'] == 'calendar') {
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
    
    $bookings = getBookingsForMonth($pdo, $year, str_pad($month,2,"0",STR_PAD_LEFT));
    $firstDay = date('N', strtotime("$year-$month-01"));
    $daysInMonth = date('t', strtotime("$year-$month-01"));
    
    $calendarHTML = '';
    $calendarHTML .= '<div class="calendar-header">';
    $calendarHTML .= '<button class="calendar-arrow" onclick="loadCalendar(' . ($month == 1 ? $year - 1 : $year) . ', ' . ($month == 1 ? 12 : $month - 1) . ')">&#8592;</button>';
    $calendarHTML .= '<span style="font-size:1.35rem;font-weight:600;color:#6d4c41;">' . date('F Y', strtotime("$year-$month-01")) . '</span>';
    $calendarHTML .= '<button class="calendar-arrow" onclick="loadCalendar(' . ($month == 12 ? $year + 1 : $year) . ', ' . ($month == 12 ? 1 : $month + 1) . ')">&#8594;</button>';
    $calendarHTML .= '</div>';
    
    $calendarHTML .= '<table class="calendar-table">';
    $calendarHTML .= '<tr><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th></tr>';
    $calendarHTML .= '<tr>';
    
    for ($blank=1; $blank<$firstDay; $blank++) {
        $calendarHTML .= "<td></td>";
    }
    
    for ($day=1, $cell=$firstDay; $day<=$daysInMonth; $day++, $cell++) {
        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $color = isset($bookings[$dateStr]) ? getDayColor($bookings[$dateStr]) : 'green';
        $data_color = $color;
        if ($color == 'yellow') $data_color = 'yellow';
        elseif ($color == 'red') $data_color = 'red';
        else $data_color = 'green';
        
        // Get available time slots and booked times for partially booked dates
        $timeSlots = '';
        $bookedTimes = '';
        if (isset($bookings[$dateStr])) {
            $timeSlots = getAvailableTimeSlots($bookings[$dateStr]);
            $bookedTimes = getBookedTimes($bookings[$dateStr]);
        }
        
        $calendarHTML .= "<td class='calendar-day' data-color='$data_color' data-date='$dateStr' data-slots='$timeSlots' data-booked='$bookedTimes' onclick=\"handleDateClick('$dateStr', '$color')\">$day</td>";
        
        if ($cell%7==0) {
            $calendarHTML .= "</tr><tr>";
        }
    }
    
    $calendarHTML .= '</tr></table>';
    $calendarHTML .= '<div class="legend">';
    $calendarHTML .= '<span class="available">Available</span>';
    $calendarHTML .= '<span class="partial">Partially Booked</span>';
    $calendarHTML .= '<span class="full">Fully Booked</span>';
    $calendarHTML .= '</div>';
    
    echo $calendarHTML;
    exit;
}

// Function to get available time slots
function getAvailableTimeSlots($bookings) {
    $allHours = range(8, 22); // 8 AM to 10 PM
    $bookedHours = [];
    
    foreach ($bookings as $booking) {
        $startHour = (int)date('H', strtotime($booking['btschedule']));
        $duration = (int)$booking['EventDuration'];
        $endHour = $startHour + $duration;
        
        for ($hour = $startHour; $hour < $endHour; $hour++) {
            if ($hour < 24) {
                $bookedHours[$hour] = true;
            }
        }
    }
    
    $availableHours = array_diff($allHours, array_keys($bookedHours));
    
    if (empty($availableHours)) {
        return 'No available hours';
    }
    
    $slots = [];
    $currentSlot = [];
    
    sort($availableHours);
    foreach ($availableHours as $hour) {
        if (empty($currentSlot)) {
            $currentSlot[] = $hour;
        } elseif ($hour == end($currentSlot) + 1) {
            $currentSlot[] = $hour;
        } else {
            $slots[] = $currentSlot;
            $currentSlot = [$hour];
        }
    }
    $slots[] = $currentSlot;
    
    $formattedSlots = [];
    foreach ($slots as $slot) {
        $start = sprintf('%02d:00', $slot[0]);
        $end = sprintf('%02d:00', end($slot) + 1);
        $formattedSlots[] = date('g:i A', strtotime($start)) . ' - ' . date('g:i A', strtotime($end));
    }
    
    return implode(', ', $formattedSlots);
}

// Function to get booked times
function getBookedTimes($bookings) {
    $bookedSlots = [];
    
    foreach ($bookings as $booking) {
        $start = date('g:i A', strtotime($booking['btschedule']));
        $end = date('g:i A', strtotime($booking['btschedule']) + ($booking['EventDuration'] * 3600));
        $bookedSlots[] = $start . ' - ' . $end;
    }
    
    return implode(', ', $bookedSlots);
}

// Event packages data
$eventPackages = [
    'Weddings' => [
        'base_price' => 50000,
        'base_attendees' => 50,
        'excess_price' => 900,
        'duration' => 8,
        'includes' => [
            'Venue rental for 8 hours',
            'Event Coordination & Setup',
            'Lights (2x)',
            'Speakers (4x)',
            'Tables & Chairs with linens',
            'Backdrop & stage decor',
            'Basic catering for 50 pax'
        ]
    ],
    'Birthday Party' => [
        'base_price' => 25000,
        'base_attendees' => 30,
        'excess_price' => 500,
        'duration' => 6,
        'includes' => [
            'Venue rental for 6 hours',
            'Themed backdrop & balloons',
            'Lights (2x)',
            'Speakers (2x)',
            'Tables & chairs with covers',
            'Basic catering for 30 pax'
        ]
    ],
    'Corporate Event' => [
        'base_price' => 40000,
        'base_attendees' => 100,
        'excess_price' => 700,
        'duration' => 6,
        'includes' => [
            'Venue rental for 6 hours',
            'Professional stage & backdrop',
            'Projector & screen',
            'Lights (4x)',
            'Speakers (4x)',
            'Tables & chairs',
            'Basic catering for 100 pax'
        ]
    ],
    'Christening' => [
        'base_price' => 20000,
        'base_attendees' => 30,
        'excess_price' => 400,
        'duration' => 6,
        'includes' => [
            'Venue rental for 6 hours',
            'Simple backdrop & floral decor',
            'Lights (2x)',
            'Speakers (2x)',
            'Tables & chairs with linens',
            'Basic catering for 30 pax'
        ]
    ],
    'Debut' => [
        'base_price' => 35000,
        'base_attendees' => 50,
        'excess_price' => 800,
        'duration' => 6,
        'includes' => [
            'Venue rental for 6 hours',
            'Themed stage & backdrop',
            'Lights (3x)',
            'Speakers (3x)',
            'Tables & chairs with covers',
            'Basic catering for 50 pax'
        ]
    ],
    '18 Birthday' => [
        'base_price' => 28000,
        'base_attendees' => 40,
        'excess_price' => 600,
        'duration' => 6,
        'includes' => [
            'Venue rental for 6 hours',
            'Special themed decor',
            'Lights (3x)',
            'Speakers (3x)',
            'Tables & chairs with covers',
            'Basic catering for 40 pax'
        ]
    ],
    'Graduation' => [
        'base_price' => 22000,
        'base_attendees' => 40,
        'excess_price' => 450,
        'duration' => 5,
        'includes' => [
            'Venue rental for 5 hours',
            'Graduation-themed decor',
            'Lights (2x)',
            'Speakers (2x)',
            'Tables & chairs',
            'Basic catering for 40 pax'
        ]
    ]
];

// Current values
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$bookings = getBookingsForMonth($pdo, $year, str_pad($month,2,"0",STR_PAD_LEFT));
$firstDay = date('N', strtotime("$year-$month-01"));
$daysInMonth = date('t', strtotime("$year-$month-01"));

// Get user data
$data = new AllData($pdo);
$getUserById = $data->getUserById($user_id);
$services = $data->getAllServices();

// Fetch available equipment
function getAvailableEquipment($pdo)
{
    $sql = "SELECT id, item_name, category, available_quantity, unit_price 
            FROM inventory 
            WHERE available_quantity > 0 
            AND category IN ('Sound Equipment', 'Visual Equipment', 'Lighting Equipment', 'Effects Equipment', 'Furniture')
            ORDER BY category, item_name";
    $stmt = $pdo->query($sql);
    $equipment = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $equipment[$row['category']][] = $row;
    }

    return $equipment;
}

$available_equipment = getAvailableEquipment($pdo);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/style.css" />
    <title>Booking Form</title>
    <style>
        body {
            background: #f5f1e8;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #5d4037;
        }
        
        .back-btn {
            display: inline-block;
            padding: 8px 18px;
            background: #8d6e63;
            color: #fff;
            border: none;
            border-radius: 22px;
            font-weight: bold;
            margin: 24px 0 0 24px;
            font-size: 1rem;
            box-shadow: 0 2px 4px rgba(141,110,99,0.3);
            cursor: pointer;
            text-decoration: none;
            transition: background 0.18s;
        }
        
        .back-btn:hover {
            background: #6d4c41;
            color: #fff;
        }
        
        .booking-main {
            max-width: 1000px;
            margin: 32px auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(141,110,99,0.15);
            padding: 32px;
            border: 1px solid #d7ccc8;
        }
        
        .booking-header {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .booking-header h1 {
            color: #6d4c41;
            margin: 0;
            font-size: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #5d4037;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #d7ccc8;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
            background: #faf7f4;
            color: #5d4037;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        
        .form-control:focus {
            border-color: #8d6e63;
            outline: none;
            background: #fff;
        }
        
        .form-control[readonly] {
            background-color: #e8e2da;
            cursor: not-allowed;
            color: #7a6158;
        }
        
        .calendar-section {
            grid-column: 1 / -1;
            margin: 20px 0;
            padding: 20px;
            background: #efebe9;
            border-radius: 12px;
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
        
        /* PROPER CALENDAR COLORS */
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
        
        /* Time Slot Tooltip */
        .time-slot-tooltip {
            position: absolute;
            background: #fff;
            border: 2px solid #8d6e63;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            max-width: 250px;
            font-size: 12px;
            color: #5d4037;
        }
        
        .time-slot-tooltip h4 {
            margin: 0 0 8px 0;
            color: #6d4c41;
            font-size: 14px;
            border-bottom: 1px solid #d7ccc8;
            padding-bottom: 4px;
        }
        
        .time-slot-tooltip ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .time-slot-tooltip li {
            padding: 4px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .time-slot-tooltip li:last-child {
            border-bottom: none;
        }
        
        .time-range {
            font-size: 14px;
            color: #8d6e63;
            margin-top: 5px;
            font-weight: 500;
        }
        
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin: 15px 0;
            padding: 15px;
            background: #efebe9;
            border-radius: 8px;
            border: 1px solid #d7ccc8;
        }
        
        .checkbox-category {
            border: 1px solid #d7ccc8;
            padding: 15px;
            border-radius: 8px;
            background: #faf7f4;
        }
        
        .checkbox-category h4 {
            margin: 0 0 10px 0;
            color: #6d4c41;
            border-bottom: 2px solid #8d6e63;
            padding-bottom: 5px;
        }
        
        .checkbox-item {
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .wedding-bundle {
            background: #f5eee6;
            border: 2px solid #d7ccc8;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .wedding-bundle h3 {
            color: #6d4c41;
            margin-top: 0;
        }
        
        .total-cost {
            background: linear-gradient(135deg, #8d6e63, #6d4c41);
            color: #fff;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            font-size: 1.2rem;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            box-shadow: 0 4px 8px rgba(141,110,99,0.3);
        }
        
        .btn-group-card {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-view-now {
            background: linear-gradient(135deg, #8d6e63, #6d4c41);
            color: #fff;
            box-shadow: 0 4px 8px rgba(141,110,99,0.3);
        }
        
        .btn-view-now:hover {
            background: linear-gradient(135deg, #6d4c41, #5d4037);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(141,110,99,0.4);
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #ffcdd2;
        }
        
        .success {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 12px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #c8e6c9;
        }
        
        .select-wrapper {
            position: relative;
        }
        
        .select-wrapper::after {
            content: "▼";
            font-size: 12px;
            color: #8d6e63;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
        }
        
        .package-details {
            background: #f5eee6;
            border: 2px solid #d7ccc8;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            display: none;
        }
        
        .package-details h3 {
            color: #6d4c41;
            margin-top: 0;
            border-bottom: 2px solid #8d6e63;
            padding-bottom: 10px;
        }
        
        .package-includes {
            margin: 15px 0;
        }
        
        .package-includes ul {
            list-style: none;
            padding: 0;
        }
        
        .package-includes li {
            padding: 5px 0;
            border-bottom: 1px solid #e8e2da;
        }
        
        .package-includes li:last-child {
            border-bottom: none;
        }
        
        .package-includes li:before {
            content: "✓ ";
            color: #388e3c;
            font-weight: bold;
        }
        
        .price-calculation {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #d7ccc8;
            margin: 15px 0;
        }
        
        .price-breakdown {
            font-size: 14px;
            color: #5d4037;
        }
        
        .total-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #6d4c41;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #8d6e63;
        }
        
        .add-to-cart-btn {
            background: linear-gradient(135deg, #388e3c, #2e7d32);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 15px;
        }
        
        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            transform: translateY(-2px);
        }
        
        .view-cart-btn {
            background: #8d6e63;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }
        
        .cart-count {
            background: #f36c6c;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 12px;
            margin-left: 5px;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .booking-main {
                margin: 15px;
                padding: 20px;
            }
            
            .calendar-header {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
    <script>
        const eventPackages = <?php echo json_encode($eventPackages); ?>;
        
        function updateForm() {
            const eventType = document.getElementById('btevent').value;
            const bundleDiv = document.querySelector('.wedding-bundle');
            const packageDetails = document.getElementById('packageDetails');
            
            bundleDiv.style.display = eventType === 'Weddings' ? 'block' : 'none';
            
            // Show package details if it's a predefined package
            if (eventPackages[eventType]) {
                showPackageDetails(eventType);
                // Set duration based on package
                document.getElementById('event_duration').value = eventPackages[eventType].duration;
                updateTimeRange();
            } else {
                packageDetails.style.display = 'none';
            }
            
            updateTotalCost();
        }
        
        function showPackageDetails(eventType) {
            const package = eventPackages[eventType];
            const packageDetails = document.getElementById('packageDetails');
            const packageContent = document.getElementById('packageContent');
            
            // Build includes list
            let includesHTML = '<ul>';
            package.includes.forEach(item => {
                includesHTML += `<li>${item}</li>`;
            });
            includesHTML += '</ul>';
            
            // Update package content
            packageContent.innerHTML = `
                <div class="package-includes">
                    <h4>Package Includes:</h4>
                    ${includesHTML}
                </div>
                <div id="priceCalculation" class="price-calculation"></div>
            `;
            
            // Update price calculation
            updatePriceCalculation(eventType);
            
            // Show package details
            packageDetails.style.display = 'block';
        }
        
        function updatePriceCalculation(eventType) {
            const package = eventPackages[eventType];
            const attendees = parseInt(document.getElementById('btattendees').value) || package.base_attendees;
            const priceCalculation = document.getElementById('priceCalculation');
            
            const excessAttendees = Math.max(0, attendees - package.base_attendees);
            const excessCost = excessAttendees * package.excess_price;
            const totalCost = package.base_price + excessCost;
            
            priceCalculation.innerHTML = `
                <div class="price-breakdown">
                    <p>Base price (${package.base_attendees} pax): ₱${package.base_price.toLocaleString()}</p>
                    ${excessAttendees > 0 ? `
                        <p>Excess attendees (${excessAttendees} pax × ₱${package.excess_price}): ₱${excessCost.toLocaleString()}</p>
                    ` : ''}
                </div>
                <div class="total-price">
                    Total: ₱${totalCost.toLocaleString()}
                    ${excessAttendees > 0 ? `(${package.base_attendees} + ${excessAttendees} pax)` : ''}
                </div>
            `;
            
            // Update total cost display
            document.getElementById('totalCost').textContent = totalCost.toLocaleString();
        }
        
        function updateTotalCost() {
            const eventType = document.getElementById('btevent').value;
            const attendees = parseInt(document.getElementById('btattendees').value) || 0;
            
            if (eventPackages[eventType]) {
                updatePriceCalculation(eventType);
            } else {
                // Custom event calculation
                const baseCost = eventType === 'Weddings' ? 1000 : 300;
                const additionalHeads = Math.max(0, attendees - 50);
                const totalCost = baseCost + (additionalHeads * 50);
                document.getElementById('totalCost').textContent = totalCost.toLocaleString();
            }
        }
        
        function handleDateClick(dateStr, color) {
            if (color === 'red') {
                alert('This date is fully booked. Please choose another date.');
                return;
            }
            updateCalendarDay(dateStr);
        }
        
        function updateCalendarDay(dateStr) {
            document.getElementById('event_date').value = dateStr;
            if (!document.getElementById('event_time').value) {
                document.getElementById('event_time').value = '09:00';
            }
            updateTimeRange();
        }
        
        function updateTimeRange() {
            const timeInput = document.getElementById('event_time');
            const durationSelect = document.getElementById('event_duration');
            const timeRangeDisplay = document.getElementById('timeRangeDisplay');
            
            if (timeInput.value && durationSelect.value) {
                const startTime = timeInput.value;
                const duration = parseInt(durationSelect.value);
                const startDate = new Date(`2000-01-01T${startTime}`);
                const endDate = new Date(startDate.getTime() + duration * 60 * 60 * 1000);
                
                const formatTime = (date) => {
                    let hours = date.getHours();
                    let minutes = date.getMinutes();
                    const ampm = hours >= 12 ? 'PM' : 'AM';
                    hours = hours % 12;
                    hours = hours ? hours : 12;
                    minutes = minutes < 10 ? '0' + minutes : minutes;
                    return hours + ':' + minutes + ' ' + ampm;
                };
                
                timeRangeDisplay.textContent = `${formatTime(startDate)} - ${formatTime(endDate)}`;
                timeRangeDisplay.style.display = 'block';
            } else {
                timeRangeDisplay.style.display = 'none';
            }
        }
        
        function addToCart() {
            const eventType = document.getElementById('btevent').value;
            const date = document.getElementById('event_date').value;
            const time = document.getElementById('event_time').value;
            const duration = document.getElementById('event_duration').value;
            const attendees = document.getElementById('btattendees').value;
            const address = document.getElementById('btaddress').value;
            const message = document.getElementById('btmessage').value;
            
            if (!eventType || !date || !time || !duration || !attendees || !address) {
                alert('Please fill in all required fields before adding to cart.');
                return;
            }
            
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '?add_to_cart=true';
            
            const fields = {
                'event_type': eventType,
                'date': date,
                'time': time,
                'duration': duration,
                'attendees': attendees,
                'address': address,
                'message': message,
                'package_details': JSON.stringify(eventPackages[eventType] || {}),
                'total_price': document.getElementById('totalCost').textContent.replace(/,/g, '')
            };
            
            Object.entries(fields).forEach(([key, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
        }
        
        function showTimeSlots(event) {
            const day = event.target;
            const color = day.getAttribute('data-color');
            const timeSlots = day.getAttribute('data-slots');
            const bookedTimes = day.getAttribute('data-booked');
            
            if (color === 'yellow') {
                const tooltip = document.createElement('div');
                tooltip.className = 'time-slot-tooltip';
                
                let tooltipHTML = '<h4>Availability Information</h4><ul>';
                
                if (bookedTimes) {
                    tooltipHTML += '<li><strong>Booked Times:</strong><br>' + bookedTimes.split(', ').join('<br>') + '</li>';
                }
                
                if (timeSlots && timeSlots !== 'No available hours') {
                    tooltipHTML += '<li><strong>Available Slots:</strong><br>' + timeSlots.split(', ').join('<br>') + '</li>';
                }
                
                tooltipHTML += '</ul>';
                tooltip.innerHTML = tooltipHTML;
                
                const rect = day.getBoundingClientRect();
                tooltip.style.position = 'fixed';
                tooltip.style.left = (rect.left + window.scrollX) + 'px';
                tooltip.style.top = (rect.bottom + window.scrollY + 5) + 'px';
                
                document.body.appendChild(tooltip);
                day._tooltip = tooltip;
            }
        }
        
        function hideTimeSlots(event) {
            const day = event.target;
            if (day._tooltip) {
                document.body.removeChild(day._tooltip);
                day._tooltip = null;
            }
        }
        
        function loadCalendar(year, month) {
            const calendarSection = document.getElementById('calendar-container');
            calendarSection.innerHTML = '<div style="text-align:center;padding:20px;color:#8d6e63;">Loading calendar...</div>';
            
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `?ajax=calendar&year=${year}&month=${month}`, true);
            xhr.onload = function() {
                if (this.status === 200) {
                    calendarSection.innerHTML = this.responseText;
                    attachCalendarEventListeners();
                }
            };
            xhr.send();
        }
        
        function attachCalendarEventListeners() {
            document.querySelectorAll('.calendar-day').forEach(day => {
                day.addEventListener('mouseenter', showTimeSlots);
                day.addEventListener('mouseleave', hideTimeSlots);
            });
        }
        
        function restoreFormData() {
            const savedData = sessionStorage.getItem('bookingFormData');
            if (savedData) {
                const formData = JSON.parse(savedData);
                Object.keys(formData).forEach(key => {
                    const element = document.querySelector(`[name="${key}"]`);
                    if (element && formData[key]) {
                        element.value = formData[key];
                    }
                });
                updateForm();
            }
        }
        
        function saveFormData() {
            const formData = {
                btaddress: document.querySelector('[name="btaddress"]')?.value,
                btevent: document.querySelector('[name="btevent"]')?.value,
                event_date: document.querySelector('[name="event_date"]')?.value,
                event_time: document.querySelector('[name="event_time"]')?.value,
                event_duration: document.querySelector('[name="event_duration"]')?.value,
                btattendees: document.querySelector('[name="btattendees"]')?.value,
                additional_headcount: document.querySelector('[name="additional_headcount"]')?.value,
                btmessage: document.querySelector('[name="btmessage"]')?.value
            };
            sessionStorage.setItem('bookingFormData', JSON.stringify(formData));
        }
    </script>
</head>

<body onload="restoreFormData(); attachCalendarEventListeners();">
    <a href="index.php" class="back-btn">&#8592; Back to Home</a>
    <a href="user_cart.php" class="view-cart-btn">
        View Cart <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
    </a>

    <div class="booking-main">
        <div class="booking-header">
            <h1>Booking Form</h1>
            <p style="color:#8d6e63;">Browse months freely - your form data will be preserved</p>
        </div>
        
        <form id="bookingForm" method="POST">
            <input type="hidden" name="submit_booking" value="1">
            
            <div class="form-grid">
                <!-- User Information -->
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" value="<?php echo htmlspecialchars($fullname); ?>" readonly class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?php echo htmlspecialchars($email); ?>" readonly class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Contact No.</label>
                    <input type="text" value="<?php echo $getUserById['bt_phone_number']; ?>" readonly class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="address">Address *</label>
                    <input type="text" name="btaddress" id="btaddress" class="form-control">
                </div>
                
                <!-- Event Details -->
                <div class="form-group">
                    <label for="event">Event Type *</label>
                    <div class="select-wrapper">
                        <select name="btevent" class="form-control" id="btevent" onchange="updateForm()">
                            <option value="">Select Event Type</option>
                            <?php
                            foreach ($eventPackages as $event => $package) {
                                echo '<option value="' . htmlspecialchars($event) . '">' . htmlspecialchars($event) . '</option>';
                            }
                            ?>
                            <option value="Custom">Custom Event</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="event_date">Event Date *</label>
                    <input type="date" name="event_date" id="event_date" class="form-control" 
                           min="<?php echo date('Y-m-d'); ?>" onchange="updateTimeRange()">
                </div>
                
                <div class="form-group">
                    <label for="event_time">Start Time *</label>
                    <input type="time" name="event_time" id="event_time" class="form-control" onchange="updateTimeRange()">
                </div>
                
                <div class="form-group">
                    <label for="event_duration">Duration *</label>
                    <div class="select-wrapper">
                        <select name="event_duration" id="event_duration" class="form-control" onchange="updateTimeRange()">
                            <option value="">Select Duration</option>
                            <option value="4">4 hours</option>
                            <option value="6">6 hours</option>
                            <option value="8">8 hours</option>
                        </select>
                    </div>
                    <div id="timeRangeDisplay" class="time-range" style="display: none;">
                        Time range will appear here
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="btattendees">Number of Attendees *</label>
                    <input type="number" name="btattendees" id="btattendees" min="1" class="form-control" onchange="updateTotalCost()">
                </div>
            </div>

            <!-- Package Details -->
            <div id="packageDetails" class="package-details">
                <h3>Package Details</h3>
                <div id="packageContent"></div>
            </div>

            <!-- Calendar Section -->
            <div class="calendar-section" id="calendar-container">
                <div class="calendar-header">
                    <button class="calendar-arrow" onclick="saveFormData(); loadCalendar(<?php echo $month == 1 ? $year - 1 : $year; ?>, <?php echo $month == 1 ? 12 : $month - 1; ?>)">&#8592;</button>
                    <span style="font-size:1.35rem;font-weight:600;color:#6d4c41;">
                        <?php echo date('F Y', strtotime("$year-$month-01")); ?>
                    </span>
                    <button class="calendar-arrow" onclick="saveFormData(); loadCalendar(<?php echo $month == 12 ? $year + 1 : $year; ?>, <?php echo $month == 12 ? 1 : $month + 1; ?>)">&#8594;</button>
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
                            $timeSlots = ($color == 'yellow' && isset($bookings[$dateStr])) ? getAvailableTimeSlots($bookings[$dateStr]) : '';
                            $bookedTimes = ($color == 'yellow' && isset($bookings[$dateStr])) ? getBookedTimes($bookings[$dateStr]) : '';
                            echo "<td class='calendar-day' data-color='$color' data-date='$dateStr' data-slots='$timeSlots' data-booked='$bookedTimes' onclick=\"handleDateClick('$dateStr', '$color')\">$day</td>";
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

            <!-- Wedding Bundle -->
            <div class="wedding-bundle" style="display:none;">
                <h3>Wedding Bundle</h3>
                <p>Includes: Basic Sound System, Lights, and Projector</p>
                <p>Base Cost: ₱1000</p>
                <div class="form-group">
                    <label>Additional Headcount (₱50/head)</label>
                    <input type="number" name="additional_headcount" value="0" min="0" 
                           class="form-control" onchange="updateTotalCost()">
                </div>
            </div>

            <!-- Equipment Section -->
            <?php if (!empty($available_equipment)): ?>
                <div class="form-group">
                    <h3>Equipment (Optional)</h3>
                    <div class="checkbox-group">
                        <?php foreach ($available_equipment as $category => $items): ?>
                            <div class="checkbox-category">
                                <h4><?php echo htmlspecialchars($category); ?></h4>
                                <?php foreach ($items as $item): ?>
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="equipment-<?php echo $item['id']; ?>"
                                            name="equipment[<?php echo $item['id']; ?>]" value="1">
                                        <label for="equipment-<?php echo $item['id']; ?>">
                                            <?php echo htmlspecialchars($item['item_name']); ?>
                                            (₱<?php echo number_format($item['unit_price'], 2); ?>)
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Services Section -->
            <div class="form-group">
                <h3>Services (Optional)</h3>
                <div class="checkbox-group">
                    <?php foreach ($services as $service): ?>
                        <div class="checkbox-item">
                            <input type="checkbox" id="service-<?php echo $service['services_id']; ?>"
                                name="btservices[]" value="<?php echo htmlspecialchars($service['name']); ?>">
                            <label for="service-<?php echo $service['services_id']; ?>">
                                <?php echo htmlspecialchars($service['name']); ?> - ₱<?php echo number_format($service['price'], 2); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Message -->
            <div class="form-group">
                <label for="Message">Special Requests/Message</label>
                <textarea name="btmessage" id="btmessage" rows="3" class="form-control" placeholder="Any special requests or notes..."></textarea>
            </div>

            <!-- Total Cost -->
            <div class="total-cost">
                Total Cost: ₱<span id="totalCost">0.00</span>
            </div>

            <!-- Add to Cart Button -->
            <div style="text-align: center; margin: 30px 0;">
                <button type="button" class="add-to-cart-btn" onclick="addToCart()">
                    📦 Add to Cart
                </button>
            </div>
        </form>
    </div>

    <script>
        // Initialize calendar on load
        loadCalendar(<?php echo $year; ?>, <?php echo $month; ?>);
    </script>
</body>
</html>