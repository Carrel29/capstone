<?php
session_start();
include_once "../includes/loginSession.php";
include_once "../includes/userData.php";
include_once "../includes/allData.php";
include_once "../includes/db.php";
require_once "calendar-utils.php"; 

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit();
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle form submission to database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    try {
        // Validate required fields
        $required_fields = ['btaddress', 'btevent', 'event_date', 'event_time', 'event_duration', 'btattendees'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields. Missing: $field");
            }
        }

        // Prepare data
        $user_id = $_SESSION['user_id'];
        $btaddress = trim($_POST['btaddress']);
        $btevent = trim($_POST['btevent']);
        
        // Validate and adjust duration
        $duration = (int)$_POST['event_duration'];
        if (!in_array($duration, [4, 6, 8])) {
            // Adjust to nearest valid duration
            if ($duration < 5) $duration = 4;
            else if ($duration < 7) $duration = 6;
            else $duration = 8;
        }
        
        $btschedule = date('Y-m-d H:i:s', strtotime($_POST['event_date'] . ' ' . $_POST['event_time']));
        $EventDuration = date('Y-m-d H:i:s', strtotime('+' . $duration . ' hours', strtotime($btschedule)));
        $btattendees = (int)$_POST['btattendees'];
        $additional_headcount = max(0, (int)($_POST['additional_headcount'] ?? 0));
        
        // Handle services
        $btservices = '';
        $service_ids = [];
        if (!empty($_POST['btservices']) && is_array($_POST['btservices'])) {
            $service_names = [];
            foreach ($_POST['btservices'] as $service_name) {
                $service_stmt = $pdo->prepare("SELECT services_id, price FROM service WHERE name = ?");
                $service_stmt->execute([$service_name]);
                $service = $service_stmt->fetch();
                if ($service) {
                    $service_names[] = $service_name;
                    $service_ids[] = $service['services_id'];
                }
            }
            $btservices = implode(', ', $service_names);
        }
        
        $btmessage = isset($_POST['btmessage']) ? trim($_POST['btmessage']) : '';
        
        // Calculate total cost based on package or custom event
        $total_cost = 0;
        
        // Check if it's a predefined package
        $package_stmt = $pdo->prepare("SELECT * FROM packages WHERE name = ? AND status = 'active'");
        $package_stmt->execute([$btevent]);
        $package = $package_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($package) {
            // Validate attendees count
            if ($btattendees < $package['min_attendees']) {
                throw new Exception("Minimum attendees for this package is " . $package['min_attendees']);
            }
            
            if ($btattendees > $package['max_attendees']) {
                throw new Exception("Maximum attendees for this package is " . $package['max_attendees']);
            }
            
            // Calculate cost for package
            $excessAttendees = max(0, $btattendees - $package['base_attendees']);
            $total_cost = $package['base_price'] + ($excessAttendees * $package['excess_price']);
        } else {
            // Custom event - minimum 20,000 pesos
            $total_cost = max(20000, $btattendees * 300); // Base calculation
        }

        // Add equipment costs
        if (!empty($_POST['equipment'])) {
            foreach ($_POST['equipment'] as $equipment_id => $value) {
                if ($value == '1') {
                    $equipment_stmt = $pdo->prepare("SELECT unit_price, item_name FROM inventory WHERE id = ?");
                    $equipment_stmt->execute([$equipment_id]);
                    $equipment = $equipment_stmt->fetch();
                    if ($equipment) {
                        $total_cost += $equipment['unit_price'];
                    }
                }
            }
        }

        // Add service costs
        if (!empty($_POST['btservices'])) {
            foreach ($_POST['btservices'] as $service_name) {
                $service_stmt = $pdo->prepare("SELECT price FROM service WHERE name = ?");
                $service_stmt->execute([$service_name]);
                $service = $service_stmt->fetch();
                if ($service) {
                    $total_cost += $service['price'];
                }
            }
        }

        // Insert into database
        $stmt = $pdo->prepare("INSERT INTO bookings 
            (btuser_id, btaddress, btevent, btschedule, EventDuration, total_cost, additional_headcount, btattendees, btservices, btmessage, status, payment_status) 
            VALUES 
            (:user_id, :btaddress, :btevent, :btschedule, :EventDuration, :total_cost, :additional_headcount, :btattendees, :btservices, :btmessage, 'Pending', 'unpaid')");

        $stmt->execute([
            ':user_id' => $user_id,
            ':btaddress' => $btaddress,
            ':btevent' => $btevent,
            ':btschedule' => $btschedule,
            ':EventDuration' => $EventDuration,
            ':total_cost' => $total_cost,
            ':additional_headcount' => $additional_headcount,
            ':btattendees' => $btattendees,
            ':btservices' => $btservices,
            ':btmessage' => $btmessage
        ]);

        $booking_id = $pdo->lastInsertId();

        // Handle equipment - USING INVENTORY TABLE DIRECTLY
        if (!empty($_POST['equipment'])) {
            foreach ($_POST['equipment'] as $equipment_id => $value) {
                if ($value == '1') {
                    $equipment_stmt = $pdo->prepare("SELECT item_name, unit_price, available_quantity FROM inventory WHERE id = ?");
                    $equipment_stmt->execute([$equipment_id]);
                    $equipment = $equipment_stmt->fetch();
                    
                    if ($equipment) {
                        // Check if there's enough available quantity
                        if ($equipment['available_quantity'] > 0) {
                            // Update inventory - reduce available quantity and increase rented quantity
                            // First check if rented_quantity column exists
                            $check_column = $pdo->prepare("SHOW COLUMNS FROM inventory LIKE 'rented_quantity'");
                            $check_column->execute();
                            $column_exists = $check_column->fetch();
                            
                            if ($column_exists) {
                                $update_inventory = $pdo->prepare("UPDATE inventory SET available_quantity = available_quantity - 1, rented_quantity = COALESCE(rented_quantity, 0) + 1 WHERE id = ?");
                            } else {
                                $update_inventory = $pdo->prepare("UPDATE inventory SET available_quantity = available_quantity - 1 WHERE id = ?");
                            }
                            $update_inventory->execute([$equipment_id]);
                        } else {
                            // Log the issue but don't stop the booking process
                            error_log("Equipment '{$equipment['item_name']}' is unavailable for booking ID: $booking_id");
                        }
                    }
                }
            }
        }

        // Store the booking ID in session for catering to use
        $_SESSION['current_booking_id'] = $booking_id;
        
        // Create a session cart item for payment system
        $cartItem = [
            'booking_id' => $booking_id,
            'event_type' => $btevent,
            'date' => $_POST['event_date'],
            'time' => $_POST['event_time'],
            'duration' => $duration,
            'attendees' => $btattendees,
            'address' => $btaddress,
            'message' => $btmessage,
            'total_price' => $total_cost,
            'services' => $btservices,
            'additional_headcount' => $additional_headcount
        ];

        // Add to session cart
        $_SESSION['cart'][] = $cartItem;
        
        // Redirect based on user action
        if (isset($_POST['proceed_to_catering']) && $_POST['proceed_to_catering'] == '1') {
            // Redirect to catering page
            $_SESSION['success_message'] = "Booking created successfully! Now add catering services.";
            header("Location: catering.php");
            exit();
        } else {
            // Redirect to cart page
            $_SESSION['success_message'] = "Booking added to cart successfully!";
            header("Location: user_cart.php");
            exit();
        }

    } catch (Exception $e) {
        $error_message = "Booking failed: " . $e->getMessage();
    }
}

// Fetch packages from database
$packages = $pdo->query("SELECT * FROM packages WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);

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
        
        // Get booked times and available slots for tooltip
        $bookedTimes = [];
        $availableSlots = [];
        if (isset($bookings[$dateStr])) {
            $bookedTimes = getBookedTimesFormatted($bookings[$dateStr]);
            $availableSlots = getAvailableTimeSlots($bookings[$dateStr]);
        }
        
        $bookedTimesJson = htmlspecialchars(json_encode($bookedTimes));
        $availableSlotsJson = htmlspecialchars(json_encode($availableSlots));
        
        $calendarHTML .= "<td class='calendar-day' data-color='$color' data-date='$dateStr' 
                          data-booked='$bookedTimesJson' data-available='$availableSlotsJson' 
                          onclick=\"handleDateClick('$dateStr', '$color')\">$day</td>";
        
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

// Fetch available equipment from inventory table
function getAvailableEquipment($pdo)
{
    $sql = "SELECT id, item_name, category, available_quantity, unit_price 
            FROM inventory 
            WHERE available_quantity > 0 
            AND category IS NOT NULL
            ORDER BY category, item_name";
    $stmt = $pdo->query($sql);
    $equipment = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $equipment[$row['category']][] = $row;
    }

    return $equipment;
}

$available_equipment = getAvailableEquipment($pdo);

// Count unpaid bookings for cart badge
try {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) as cart_count FROM bookings WHERE btuser_id = ? AND status = 'Pending' AND payment_status = 'unpaid'");
    $count_stmt->execute([$_SESSION['user_id']]);
    $cart_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['cart_count'];
} catch (Exception $e) {
    $cart_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/style.css" />
    <title>Booking Form - BTONE Events</title>
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
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 18px rgba(0,0,0,0.3);
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
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .btn-add-cart {
            background: linear-gradient(135deg, #388e3c, #2e7d32);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-add-cart:hover {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(56,142,60,0.4);
        }
        
        .btn-payment {
            background: linear-gradient(135deg, #f57c00, #e65100);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-payment:hover {
            background: linear-gradient(135deg, #e65100, #bf360c);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(245,124,0,0.4);
        }
        
        .btn-icon {
            font-size: 1.2rem;
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
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-add-cart, .btn-payment {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }
    </style>
    <script>
        const eventPackages = <?php echo json_encode($packages); ?>;
        
        // Helper function to format currency with commas and 2 decimal places
        function formatCurrency(amount) {
            return parseFloat(amount).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
        // Modal functions
        function showModal(title, content) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalContent').innerHTML = content;
            document.getElementById('timeSlotModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('timeSlotModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('timeSlotModal').addEventListener('click', function(e) {
                if (e.target === this) closeModal();
            });
        });

        // Updated handleDateClick function
        function handleDateClick(dateStr, color) {
            const dayElement = document.querySelector(`[data-date="${dateStr}"]`);
            const bookedTimes = JSON.parse(dayElement.getAttribute('data-booked') || '[]');
            const availableSlots = JSON.parse(dayElement.getAttribute('data-available') || '[]');
            
            if (color === 'red') {
                showModal('Date Fully Booked - ' + formatDate(dateStr), 
                    '<p style="color: #f36c6c; font-weight: bold;">This date is fully booked. Please choose another date.</p>');
                return;
            }
            
            if (color === 'yellow') {
                let content = '<div style="text-align: left;">';
                content += '<h4 style="color: #8d6e63; margin-bottom: 15px;">Current Bookings:</h4>';
                
                if (bookedTimes.length > 0) {
                    content += '<ul style="list-style: none; padding: 0; margin: 0 0 20px 0;">';
                    bookedTimes.forEach(time => {
                        content += `<li style="padding: 5px 0; border-bottom: 1px solid #eee;">⏰ ${time}</li>`;
                    });
                    content += '</ul>';
                } else {
                    content += '<p style="color: #7d6e63;">No specific time slots booked yet.</p>';
                }
                
                content += '<h4 style="color: #8d6e63; margin-bottom: 15px;">Available Time Slots:</h4>';
                
                if (availableSlots.length > 0) {
                    content += '<ul style="list-style: none; padding: 0; margin: 0;">';
                    availableSlots.forEach(slot => {
                        content += `<li style="padding: 8px 0; border-bottom: 1px solid #eee;">
                            <span style="color: #4caf50; font-weight: bold;">✓</span> 
                            ${slot.start} - ${slot.end}
                        </li>`;
                    });
                    content += '</ul>';
                } else {
                    content += '<p style="color: #f36c6c;">No available time slots remaining.</p>';
                }
                
                content += '</div>';
                
                showModal('Date Partially Booked - ' + formatDate(dateStr), content);
                return;
            }
            
            // For green dates, just select the date
            updateCalendarDay(dateStr);
        }

        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }
        
        function updateForm() {
            const eventType = document.getElementById('btevent').value;
            const bundleDiv = document.querySelector('.wedding-bundle');
            const packageDetails = document.getElementById('packageDetails');
            
            bundleDiv.style.display = eventType === 'Weddings' ? 'block' : 'none';
            
            // Show package details if it's a predefined package
            const selectedPackage = eventPackages.find(pkg => pkg.name === eventType);
            if (selectedPackage) {
                showPackageDetails(selectedPackage);
                // Set duration based on package
                document.getElementById('event_duration').value = selectedPackage.duration;
                updateTimeRange();
                
                // Set minimum and maximum attendees
                document.getElementById('btattendees').min = selectedPackage.min_attendees;
                document.getElementById('btattendees').max = selectedPackage.max_attendees;
                document.getElementById('btattendees').value = selectedPackage.base_attendees;
            } else {
                packageDetails.style.display = 'none';
                // Reset attendees for custom events
                document.getElementById('btattendees').min = 1;
                document.getElementById('btattendees').max = 500;
                document.getElementById('btattendees').value = 50;
            }
            
            updateTotalCost();
        }
        
        function showPackageDetails(package) {
            const packageDetails = document.getElementById('packageDetails');
            const packageContent = document.getElementById('packageContent');
            
            // Build includes list
            let includesHTML = '<ul>';
            package.includes.split(',').forEach(item => {
                includesHTML += `<li>${item.trim()}</li>`;
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
            updatePriceCalculation(package);
            
            // Show package details
            packageDetails.style.display = 'block';
        }
        
        function updatePriceCalculation(package) {
            const attendees = parseInt(document.getElementById('btattendees').value) || package.base_attendees;
            const priceCalculation = document.getElementById('priceCalculation');
            
            const excessAttendees = Math.max(0, attendees - package.base_attendees);
            const excessCost = parseFloat(excessAttendees) * parseFloat(package.excess_price);
            const totalCost = parseFloat(package.base_price) + excessCost;
            
            priceCalculation.innerHTML = `
                <div class="price-breakdown">
                    <p>Base price (${package.base_attendees} pax): ₱${formatCurrency(package.base_price)}</p>
                    ${excessAttendees > 0 ? `
                        <p>Excess attendees (${excessAttendees} pax × ₱${formatCurrency(package.excess_price)}): ₱${formatCurrency(excessCost)}</p>
                    ` : ''}
                </div>
                <div class="total-price">
                    Total: ₱${formatCurrency(totalCost)}
                    ${excessAttendees > 0 ? `(${package.base_attendees} + ${excessAttendees} pax)` : ''}
                </div>
            `;
            
            // Update total cost display
            document.getElementById('totalCost').textContent = formatCurrency(totalCost);
        }
        
        function updateTotalCost() {
            const eventType = document.getElementById('btevent').value;
            const attendees = parseInt(document.getElementById('btattendees').value) || 0;
            let totalCost = 0;

            // If no event type is selected, show 0
            if (!eventType) {
                document.getElementById('totalCost').textContent = "0.00";
                return;
            }

            const selectedPackage = eventPackages.find(pkg => pkg.name === eventType);
            if (selectedPackage) {
                // Package calculation - convert to numbers to ensure proper addition
                const excessAttendees = Math.max(0, attendees - selectedPackage.base_attendees);
                totalCost = parseFloat(selectedPackage.base_price) + (parseFloat(excessAttendees) * parseFloat(selectedPackage.excess_price));
                
                // Update package details display
                updatePriceCalculation(selectedPackage);
            } else if (eventType === 'Custom') {
                // Custom event calculation - minimum 20,000 pesos
                totalCost = Math.max(20000, parseFloat(attendees) * 300);
            }

            // Add equipment costs - ensure we're adding numbers, not concatenating strings
            document.querySelectorAll('input[name^="equipment"]:checked').forEach(checkbox => {
                const priceElement = checkbox.parentElement.querySelector('label');
                const priceText = priceElement.textContent.match(/₱([\d,]+\.?\d*)/);
                if (priceText) {
                    const price = parseFloat(priceText[1].replace(/,/g, ''));
                    totalCost += price;
                }
            });

            // Add service costs - ensure we're adding numbers, not concatenating strings
            document.querySelectorAll('input[name="btservices[]"]:checked').forEach(checkbox => {
                const priceElement = checkbox.parentElement.querySelector('label');
                const priceText = priceElement.textContent.match(/₱([\d,]+\.?\d*)/);
                if (priceText) {
                    const price = parseFloat(priceText[1].replace(/,/g, ''));
                    totalCost += price;
                }
            });

            // Format the total cost with proper thousands separators and 2 decimal places
            document.getElementById('totalCost').textContent = formatCurrency(totalCost);
        }
        
        function handleEquipmentChange() {
            updateTotalCost();
        }
        
        function handleServiceChange() {
            updateTotalCost();
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
            
            if (!eventType || !date || !time || !duration || !attendees || !address) {
                alert('Please fill in all required fields before adding to cart.');
                return;
            }
            
            // Clear catering flag
            document.getElementById('proceed_to_catering').value = '0';
            
            // Submit the form to save to database
            document.getElementById('bookingForm').submit();
        }
        
        function proceedToCatering() {
            const eventType = document.getElementById('btevent').value;
            const date = document.getElementById('event_date').value;
            const time = document.getElementById('event_time').value;
            const duration = document.getElementById('event_duration').value;
            const attendees = document.getElementById('btattendees').value;
            const address = document.getElementById('btaddress').value;
            
            if (!eventType || !date || !time || !duration || !attendees || !address) {
                alert('Please fill in all required fields before proceeding to catering menu.');
                return;
            }
            
            // Set catering flag
            document.getElementById('proceed_to_catering').value = '1';
            
            // Submit the form to save to database
            document.getElementById('bookingForm').submit();
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
        
        function attachCheckboxListeners() {
            // Add event listeners to equipment checkboxes
            document.querySelectorAll('input[name^="equipment"]').forEach(checkbox => {
                checkbox.addEventListener('change', handleEquipmentChange);
            });
            
            // Add event listeners to service checkboxes  
            document.querySelectorAll('input[name="btservices[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', handleServiceChange);
            });
        }
        
        function attachCalendarEventListeners() {
            document.querySelectorAll('.calendar-day').forEach(day => {
                day.addEventListener('click', function() {
                    const dateStr = this.getAttribute('data-date');
                    const color = this.getAttribute('data-color');
                    handleDateClick(dateStr, color);
                });
            });
            
            // Add checkbox listeners
            attachCheckboxListeners();
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
        View Cart <span class="cart-count"><?php echo $cart_count; ?></span>
    </a>

    <div class="booking-main">
        <div class="booking-header">
            <h1>Booking Form</h1>
            <p style="color:#8d6e63;">Browse months freely - your form data will be preserved</p>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <form id="bookingForm" method="POST" action="booking-form.php">
            <input type="hidden" name="submit_booking" value="1">
            <input type="hidden" name="proceed_to_catering" id="proceed_to_catering" value="0">
            
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
                    <input type="text" name="btaddress" id="btaddress" class="form-control" required>
                </div>
                
                <!-- Event Details -->
                <div class="form-group">
                    <label for="event">Event Type *</label>
                    <div class="select-wrapper">
                        <select name="btevent" class="form-control" id="btevent" onchange="updateForm()" required>
                            <option value="">Select Event Type</option>
                            <?php foreach ($packages as $package): ?>
                                <option value="<?php echo htmlspecialchars($package['name']); ?>">
                                    <?php echo htmlspecialchars($package['name']); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="Custom">Custom Event</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="event_date">Event Date *</label>
                    <input type="date" name="event_date" id="event_date" class="form-control" 
                           min="<?php echo date('Y-m-d'); ?>" onchange="updateTimeRange()" required>
                </div>
                
                <div class="form-group">
                    <label for="event_time">Start Time *</label>
                    <input type="time" name="event_time" id="event_time" class="form-control" onchange="updateTimeRange()" required>
                </div>
                
                <div class="form-group">
                    <label for="event_duration">Duration (hours) *</label>
                    <div class="select-wrapper">
                        <select name="event_duration" id="event_duration" class="form-control" onchange="updateTimeRange()" required>
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
                    <input type="number" name="btattendees" id="btattendees" min="1" class="form-control" onchange="updateTotalCost()" required>
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
                            
                            // Get booked times and available slots for tooltip
                            $bookedTimes = [];
                            $availableSlots = [];
                            if (isset($bookings[$dateStr])) {
                                $bookedTimes = getBookedTimesFormatted($bookings[$dateStr]);
                                $availableSlots = getAvailableTimeSlots($bookings[$dateStr]);
                            }
                            
                            $bookedTimesJson = htmlspecialchars(json_encode($bookedTimes));
                            $availableSlotsJson = htmlspecialchars(json_encode($availableSlots));
                            
                            echo "<td class='calendar-day' data-color='$color' data-date='$dateStr' 
                                  data-booked='$bookedTimesJson' data-available='$availableSlotsJson' 
                                  onclick=\"handleDateClick('$dateStr', '$color')\">$day</td>";
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

            <!-- Time Slot Modal -->
            <div id="timeSlotModal" class="modal">
                <div class="modal-content">
                    <h3 id="modalTitle">Date Availability</h3>
                    <div id="modalContent"></div>
                    <button onclick="closeModal()" style="margin-top: 20px; padding: 10px 20px; background: #8d6e63; color: white; border: none; border-radius: 6px; cursor: pointer;">Close</button>
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
                                            name="equipment[<?php echo $item['id']; ?>]" value="1"
                                            onchange="handleEquipmentChange()">
                                        <label for="equipment-<?php echo $item['id']; ?>">
                                            <?php echo htmlspecialchars($item['item_name']); ?>
                                            - ₱<?php echo number_format($item['unit_price'], 2); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <h3>Equipment (Optional)</h3>
                    <p style="color: #8d6e63; font-style: italic;">No equipment currently available for booking.</p>
                </div>
            <?php endif; ?>

            <!-- Services Section -->
            <div class="form-group">
                <h3>Services (Optional)</h3>
                <div class="checkbox-group">
                    <?php foreach ($services as $service): ?>
                        <div class="checkbox-item">
                            <input type="checkbox" id="service-<?php echo $service['services_id']; ?>"
                                name="btservices[]" value="<?php echo htmlspecialchars($service['name']); ?>"
                                onchange="handleServiceChange()">
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

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button type="button" class="btn-add-cart" onclick="addToCart()">
                    <span class="btn-icon">📦</span>
                    Add to Cart
                </button>
                <button type="button" class="btn-payment" onclick="proceedToCatering()">
                    <span class="btn-icon">🍽️</span>
                    Proceed to Catering Menu
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