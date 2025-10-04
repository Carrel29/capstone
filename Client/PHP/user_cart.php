<?php
session_start();
include_once "../includes/loginSession.php";
include_once "../includes/userData.php";

// Display payment success message if exists
if (isset($_SESSION['payment_success']) && $_SESSION['payment_success'] === true) {
    echo '<div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 1rem; margin: 1rem 0; border-radius: 8px; text-align: center; border: 1px solid #c3e6cb;">';
    echo htmlspecialchars($_SESSION['payment_message']);
    echo '</div>';

    // Clear the message
    unset($_SESSION['payment_success']);
    unset($_SESSION['payment_message']);
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

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? 0;
$customer_name = isset($_SESSION['login']) ? $_SESSION['login'] : (isset($_SESSION['fullname']) ? $_SESSION['fullname'] : '');

// Check if catering_orders table exists
$cateringTableExists = false;
try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'catering_orders'");
    $cateringTableExists = $checkTable->rowCount() > 0;
} catch (Exception $e) {
    $cateringTableExists = false;
}

// Get cart items (bookings with status 'Pending' and unpaid)
try {
    $cart_sql = "SELECT b.*, u.bt_first_name, u.bt_last_name, u.bt_email,
                        be.equipment_id, be.quantity as equipment_quantity, i.item_name, i.unit_price
                 FROM bookings b
                 JOIN btuser u ON b.btuser_id = u.bt_user_id
                 LEFT JOIN booking_equipment be ON b.id = be.booking_id 
                 LEFT JOIN inventory i ON be.equipment_id = i.id 
                 WHERE b.status = 'Pending' 
                 AND b.payment_status = 'unpaid'
                 AND b.btuser_id = ?
                 ORDER BY b.created_at DESC";

    $cart_stmt = $pdo->prepare($cart_sql);
    $cart_stmt->execute([$user_id]);
    $cart_results = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);

    $cart_items = [];
    $cart_total = 0;

    foreach ($cart_results as $row) {
        if (!isset($cart_items[$row['id']])) {
            $cart_items[$row['id']] = $row;
            $cart_items[$row['id']]['equipment'] = [];
            
            // Get comprehensive catering data
            $catering_data = $cateringTableExists ? getCateringData($pdo, $row['id']) : [];
            
            $cart_items[$row['id']]['catering'] = $catering_data;
            $cart_total += $row['total_cost'];
        }
        if ($row['equipment_id']) {
            $cart_items[$row['id']]['equipment'][] = [
                'name' => $row['item_name'],
                'price' => $row['unit_price'],
                'quantity' => $row['equipment_quantity']
            ];
        }
    }

} catch (Exception $e) {
    die("Error fetching cart items: " . $e->getMessage());
}

// Get pending bookings (bookings with status 'Pending' but paid)
try {
    $pending_sql = "SELECT b.*, u.bt_first_name, u.bt_last_name, u.bt_email,
                           be.equipment_id, be.quantity as equipment_quantity, i.item_name, i.unit_price
                    FROM bookings b
                    JOIN btuser u ON b.btuser_id = u.bt_user_id
                    LEFT JOIN booking_equipment be ON b.id = be.booking_id 
                    LEFT JOIN inventory i ON be.equipment_id = i.id 
                    WHERE b.status = 'Pending' 
                    AND b.payment_status IN ('partial', 'paid')
                    AND b.btuser_id = ?
                    ORDER BY b.created_at DESC";

    $pending_stmt = $pdo->prepare($pending_sql);
    $pending_stmt->execute([$user_id]);
    $pending_results = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

    $pending_items = [];
    $pending_total = 0;

    foreach ($pending_results as $row) {
        if (!isset($pending_items[$row['id']])) {
            $pending_items[$row['id']] = $row;
            $pending_items[$row['id']]['equipment'] = [];
            
            // Get comprehensive catering data
            $catering_data = $cateringTableExists ? getCateringData($pdo, $row['id']) : [];
            
            $pending_items[$row['id']]['catering'] = $catering_data;
            $pending_total += $row['total_cost'];
        }
        if ($row['equipment_id']) {
            $pending_items[$row['id']]['equipment'][] = [
                'name' => $row['item_name'],
                'price' => $row['unit_price'],
                'quantity' => $row['equipment_quantity']
            ];
        }
    }

} catch (Exception $e) {
    die("Error fetching pending bookings: " . $e->getMessage());
}

// Get approved bookings (bookings with status 'Approved')
try {
    $approved_sql = "SELECT b.*, u.bt_first_name, u.bt_last_name, u.bt_email,
                            be.equipment_id, be.quantity as equipment_quantity, i.item_name, i.unit_price
                     FROM bookings b
                     JOIN btuser u ON b.btuser_id = u.bt_user_id
                     LEFT JOIN booking_equipment be ON b.id = be.booking_id 
                     LEFT JOIN inventory i ON be.equipment_id = i.id 
                     WHERE b.status = 'Approved'
                     AND b.btuser_id = ?
                     ORDER BY b.btschedule ASC";

    $approved_stmt = $pdo->prepare($approved_sql);
    $approved_stmt->execute([$user_id]);
    $approved_results = $approved_stmt->fetchAll(PDO::FETCH_ASSOC);

    $approved_items = [];
    $approved_total = 0;

    foreach ($approved_results as $row) {
        if (!isset($approved_items[$row['id']])) {
            $approved_items[$row['id']] = $row;
            $approved_items[$row['id']]['equipment'] = [];
            
            // Get comprehensive catering data
            $catering_data = $cateringTableExists ? getCateringData($pdo, $row['id']) : [];
            
            $approved_items[$row['id']]['catering'] = $catering_data;
            $approved_total += $row['total_cost'];
        }
        if ($row['equipment_id']) {
            $approved_items[$row['id']]['equipment'][] = [
                'name' => $row['item_name'],
                'price' => $row['unit_price'],
                'quantity' => $row['equipment_quantity']
            ];
        }
    }

} catch (Exception $e) {
    die("Error fetching approved bookings: " . $e->getMessage());
}

// Get past bookings (bookings with status 'Completed' or 'Canceled')
try {
    $past_sql = "SELECT b.*, u.bt_first_name, u.bt_last_name, u.bt_email,
                        be.equipment_id, be.quantity as equipment_quantity, i.item_name, i.unit_price
                 FROM bookings b
                 JOIN btuser u ON b.btuser_id = u.bt_user_id
                 LEFT JOIN booking_equipment be ON b.id = be.booking_id 
                 LEFT JOIN inventory i ON be.equipment_id = i.id 
                 WHERE b.status IN ('Completed', 'Canceled')
                 AND b.btuser_id = ?
                 ORDER BY b.btschedule DESC
                 LIMIT 20";

    $past_stmt = $pdo->prepare($past_sql);
    $past_stmt->execute([$user_id]);
    $past_results = $past_stmt->fetchAll(PDO::FETCH_ASSOC);

    $past_items = [];
    $past_total = 0;

    foreach ($past_results as $row) {
        if (!isset($past_items[$row['id']])) {
            $past_items[$row['id']] = $row;
            $past_items[$row['id']]['equipment'] = [];
            
            // Get comprehensive catering data
            $catering_data = $cateringTableExists ? getCateringData($pdo, $row['id']) : [];
            
            $past_items[$row['id']]['catering'] = $catering_data;
            $past_total += $row['total_cost'];
        }
        if ($row['equipment_id']) {
            $past_items[$row['id']]['equipment'][] = [
                'name' => $row['item_name'],
                'price' => $row['unit_price'],
                'quantity' => $row['equipment_quantity']
            ];
        }
    }

} catch (Exception $e) {
    die("Error fetching past bookings: " . $e->getMessage());
}

// Function to get comprehensive catering data
function getCateringData($pdo, $booking_id) {
    $catering_data = [
        'package_name' => null,
        'dishes' => [],
        'addons' => [],
        'catering_cost' => 0,
        'special_requests' => null
    ];
    
    try {
        // Get catering order with package details
        $order_sql = "SELECT co.*, cp.name as package_name, cp.base_price 
                      FROM catering_orders co 
                      LEFT JOIN catering_packages cp ON co.package_id = cp.id 
                      WHERE co.booking_id = ? AND co.status = 'confirmed' 
                      ORDER BY co.created_at DESC LIMIT 1";
        $order_stmt = $pdo->prepare($order_sql);
        $order_stmt->execute([$booking_id]);
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $catering_data['package_name'] = $order['package_name'];
            $catering_data['catering_cost'] = $order['base_price'] ?? 0;
            
            // Get selected dishes with their names
            $dishes_sql = "SELECT cd.name, cd.category 
                           FROM catering_order_dishes cod 
                           JOIN catering_dishes cd ON cod.dish_id = cd.id 
                           WHERE cod.catering_order_id = ?";
            $dishes_stmt = $pdo->prepare($dishes_sql);
            $dishes_stmt->execute([$order['id']]);
            $dishes = $dishes_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($dishes as $dish) {
                $catering_data['dishes'][] = [
                    'name' => $dish['name'],
                    'category' => $dish['category']
                ];
            }
            
            // Get addons
            $addons_sql = "SELECT ca.name, ca.price 
                           FROM catering_order_addons coa 
                           JOIN catering_addons ca ON coa.addon_id = ca.id 
                           WHERE coa.catering_order_id = ?";
            $addons_stmt = $pdo->prepare($addons_sql);
            $addons_stmt->execute([$order['id']]);
            $addons = $addons_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($addons as $addon) {
                $catering_data['addons'][] = [
                    'name' => $addon['name'],
                    'price' => $addon['price']
                ];
                $catering_data['catering_cost'] += $addon['price'];
            }
        }
    } catch (Exception $e) {
        // Silently fail - catering data might not exist for this booking
        error_log("Error fetching catering data for booking $booking_id: " . $e->getMessage());
    }
    
    return $catering_data;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart & Bookings - BTONE</title>
    <link rel="stylesheet" href="../CSS/style.css" />
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
        
        .cart-main {
            max-width: 1200px;
            margin: 32px auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(141,110,99,0.15);
            padding: 32px;
            border: 1px solid #d7ccc8;
        }
        
        .cart-header {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .cart-header h1 {
            color: #6d4c41;
            margin: 0;
            font-size: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        .section {
            background: #faf7f4;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #d7ccc8;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e8e2da;
        }
        
        .section-header h2 {
            color: #6d4c41;
            margin: 0;
            font-size: 1.5rem;
        }
        
        .booking-item {
            background: #fff;
            border: 2px solid #e8e2da;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(141,110,99,0.1);
        }
        
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0ebe6;
        }
        
        .booking-header h3 {
            color: #6d4c41;
            margin: 0;
            font-size: 1.3rem;
        }
        
        .booking-price {
            font-size: 1.4rem;
            font-weight: bold;
            color: #8d6e63;
        }
        
        .booking-details {
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 15px;
            margin-bottom: 12px;
            align-items: start;
        }
        
        .detail-label {
            font-weight: 600;
            color: #6d4c41;
        }
        
        .detail-value {
            color: #5d4037;
        }
        
        .equipment-list {
            margin-left: 20px;
            color: #666;
        }
        
        .equipment-list ul {
            list-style-type: none;
            padding-left: 0;
        }
        
        .equipment-list li {
            margin: 8px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #8d6e63;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #f0ebe6;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-checkout {
            background: linear-gradient(135deg, #388e3c, #2e7d32);
            color: white;
            box-shadow: 0 4px 8px rgba(56,142,60,0.3);
        }
        
        .btn-checkout:hover {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(56,142,60,0.4);
        }
        
        .btn-remove {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            box-shadow: 0 4px 8px rgba(220,53,69,0.3);
        }
        
        .btn-remove:hover {
            background: linear-gradient(135deg, #c82333, #a71e2a);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(220,53,69,0.4);
        }
        
        .btn-details {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            box-shadow: 0 4px 8px rgba(23,162,184,0.3);
        }
        
        .btn-details:hover {
            background: linear-gradient(135deg, #138496, #117a8b);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(23,162,184,0.4);
        }
        
        .empty-message {
            text-align: center;
            padding: 60px 40px;
            color: #8d6e63;
        }
        
        .empty-message h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #6d4c41;
        }
        
        .empty-message p {
            font-size: 1.1rem;
            margin-bottom: 25px;
            color: #7d6e63;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
            display: inline-block;
        }
        
        .status-pending {
            background: linear-gradient(135deg, #ffc107, #ff9800);
            color: #000;
        }
        
        .status-paid {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .status-partial {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
        }
        
        .status-approved {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .status-completed {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
        }
        
        .status-canceled {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .user-info {
            background: linear-gradient(135deg, #8d6e63, #6d4c41);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(141,110,99,0.3);
        }
        
        .user-info strong {
            font-size: 1.2rem;
            display: block;
            margin-bottom: 8px;
        }
        
        .current-time {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .total-amount {
            background: linear-gradient(135deg, #8d6e63, #6d4c41);
            color: #fff;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            margin: 30px 0;
            font-size: 1.8rem;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(141,110,99,0.3);
        }
        
        .btn-icon {
            font-size: 1.2rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e8e2da;
        }

        .modal-header h3 {
            color: #6d4c41;
            margin: 0;
        }

        .close-modal {
            color: #8d6e63;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s;
        }

        .close-modal:hover {
            color: #6d4c41;
        }

        .modal-section {
            margin-bottom: 20px;
            padding: 15px;
            background: #faf7f4;
            border-radius: 8px;
            border: 1px solid #e8e2da;
        }

        .modal-section h4 {
            color: #6d4c41;
            margin: 0 0 10px 0;
            font-size: 1.1rem;
        }

        .modal-detail-row {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 10px;
            margin-bottom: 8px;
            align-items: start;
        }

        .modal-detail-label {
            font-weight: 600;
            color: #6d4c41;
        }

        .modal-detail-value {
            color: #5d4037;
        }

        .catering-items {
            margin-left: 15px;
        }

        .catering-items ul {
            list-style-type: none;
            padding-left: 0;
        }

        .catering-items li {
            margin: 5px 0;
            padding: 8px;
            background: #fff;
            border-radius: 4px;
            border-left: 3px solid #8d6e63;
        }

        .cost-breakdown {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #e8e2da;
        }

        .cost-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0ebe6;
        }

        .cost-total {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #8d6e63;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        @media (max-width: 768px) {
            .cart-main {
                margin: 15px;
                padding: 20px;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .detail-row {
                grid-template-columns: 1fr;
                gap: 5px;
            }
            
            .booking-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .modal-content {
                margin: 10% auto;
                width: 95%;
                padding: 20px;
            }

            .modal-detail-row {
                grid-template-columns: 1fr;
                gap: 5px;
            }
        }
    </style>
</head>

<body>
    <a href="index.php" class="back-btn">&#8592; Back to Home</a>
    <a href="booking-form.php" class="back-btn" style="background: #388e3c;">➕ New Booking</a>

    <div class="cart-main">
        <div class="cart-header">
            <h1>My Bookings & Cart</h1>
            <p style="color:#8d6e63;">Manage your event bookings and payments</p>
        </div>

        <div class="user-info">
            <strong>Welcome, <?php echo htmlspecialchars($customer_name); ?></strong>
            <div class="current-time">
                <?php echo date('F j, Y g:i A'); ?>
            </div>
        </div>

        <!-- Shopping Cart Section -->
        <div class="section">
            <div class="section-header">
                <h2>🛒 Shopping Cart</h2>
                <span class="cart-count"><?php echo count($cart_items); ?> item(s)</span>
            </div>

            <?php if (empty($cart_items)): ?>
                <div class="empty-message">
                    <h3>Your cart is empty</h3>
                    <p>Add some bookings to get started!</p>
                    <a href="booking-form.php" class="btn btn-checkout">
                        <span class="btn-icon">➕</span>
                        Book New Event
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($cart_items as $item): ?>
                    <div class="booking-item">
                        <div class="booking-header">
                            <h3><?php echo htmlspecialchars($item['btevent']); ?></h3>
                            <span class="booking-price">₱<?php echo number_format($item['total_cost'], 2); ?></span>
                        </div>

                        <div class="booking-details">
                            <div class="detail-row">
                                <span class="detail-label">📅 Date:</span>
                                <span class="detail-value"><?php echo date('F j, Y', strtotime($item['btschedule'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">⏰ Time:</span>
                                <span class="detail-value"><?php echo date('g:i A', strtotime($item['btschedule'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">📞 Alternative #:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($item['btaddress']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">👥 Attendees:</span>
                                <span class="detail-value"><?php echo $item['btattendees']; ?> people</span>
                            </div>

                            <?php if (!empty($item['equipment'])): ?>
                                <div class="detail-row">
                                    <span class="detail-label">🎛️ Equipment:</span>
                                    <div class="detail-value">
                                        <div class="equipment-list">
                                            <ul>
                                                <?php foreach ($item['equipment'] as $equipment): ?>
                                                    <li>
                                                        <?php echo htmlspecialchars($equipment['name']); ?> -
                                                        <?php echo $equipment['quantity']; ?> x
                                                        ₱<?php echo number_format($equipment['price'], 2); ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="button-group">
                            <button type="button" class="btn btn-details" onclick="showBookingDetails('<?php echo $item['id']; ?>')">
                                <span class="btn-icon">📋</span>
                                View Details
                            </button>
                            <form action="remove_booking.php" method="POST" style="margin: 0;">
                                <input type="hidden" name="booking_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn btn-remove" onclick="return confirm('Are you sure you want to remove this booking?')">
                                    <span class="btn-icon">🗑️</span>
                                    Remove
                                </button>
                            </form>
                            <form action="catering.php" method="GET" style="margin: 0;">
                                <input type="hidden" name="booking_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn btn-checkout">
                                    <span class="btn-icon">🍽️</span>
                                    Proceed to Catering
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="total-amount">
                    Total Cart Amount: ₱<?php echo number_format($cart_total, 2); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pending Bookings Section -->
        <div class="section">
            <div class="section-header">
                <h2>⏳ Pending Bookings</h2>
                <span class="cart-count"><?php echo count($pending_items); ?> booking(s)</span>
            </div>

            <?php if (empty($pending_items)): ?>
                <div class="empty-message">
                    <h3>No pending bookings</h3>
                    <p>Your paid bookings will appear here while awaiting admin approval</p>
                </div>
            <?php else: ?>
                <?php foreach ($pending_items as $item): ?>
                    <div class="booking-item">
                        <div class="booking-header">
                            <h3><?php echo htmlspecialchars($item['btevent']); ?></h3>
                            <span class="status-badge 
                                <?php echo $item['payment_status'] === 'paid' ? 'status-paid' : 'status-partial'; ?>">
                                <?php echo ucfirst($item['payment_status']); ?> Payment
                            </span>
                        </div>

                        <div class="booking-details">
                            <div class="detail-row">
                                <span class="detail-label">📅 Date:</span>
                                <span class="detail-value"><?php echo date('F j, Y', strtotime($item['btschedule'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">⏰ Time:</span>
                                <span class="detail-value"><?php echo date('g:i A', strtotime($item['btschedule'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label"> 📞 Alternative #</span>
                                <span class="detail-value"><?php echo htmlspecialchars($item['btaddress']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">👥 Attendees:</span>
                                <span class="detail-value"><?php echo $item['btattendees']; ?> people</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">💰 Total Cost:</span>
                                <span class="detail-value">₱<?php echo number_format($item['total_cost'], 2); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">💵 Amount Paid:</span>
                                <span class="detail-value">₱<?php echo number_format($item['payment_status'] === 'paid' ? $item['total_cost'] : $item['total_cost'] * 0.2, 2); ?></span>
                            </div>

                            <?php if (!empty($item['equipment'])): ?>
                                <div class="detail-row">
                                    <span class="detail-label">🎛️ Equipment:</span>
                                    <div class="detail-value">
                                        <div class="equipment-list">
                                            <ul>
                                                <?php foreach ($item['equipment'] as $equipment): ?>
                                                    <li>
                                                        <?php echo htmlspecialchars($equipment['name']); ?> -
                                                        <?php echo $equipment['quantity']; ?> x
                                                        ₱<?php echo number_format($equipment['price'], 2); ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        
<div class="button-group">
    <button type="button" class="btn btn-details" onclick="showBookingDetails('<?php echo $item['id']; ?>')">
        <span class="btn-icon">📋</span>
        View Details
    </button>
    
    <?php if ($item['payment_status'] === 'partial'): ?>
        <form action="payment.php" method="GET" style="margin: 0;">
            <input type="hidden" name="booking_id" value="<?php echo $item['id']; ?>">
            <input type="hidden" name="continue_payment" value="true">
            <button type="submit" class="btn btn-checkout">
                <span class="btn-icon">💳</span>
                Pay Remaining Balance
            </button>
        </form>
    <?php endif; ?>
</div>
                    </div>
                <?php endforeach; ?>
                
                <div class="total-amount">
                    Total Pending Amount: ₱<?php echo number_format($pending_total, 2); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Approved Bookings Section -->
        <div class="section">
            <div class="section-header">
                <h2>✅ Approved Bookings</h2>
                <span class="cart-count"><?php echo count($approved_items); ?> booking(s)</span>
            </div>

            <?php if (empty($approved_items)): ?>
                <div class="empty-message">
                    <h3>No approved bookings</h3>
                    <p>Your approved bookings will appear here once confirmed by admin</p>
                </div>
            <?php else: ?>
                <?php foreach ($approved_items as $item): ?>
                    <div class="booking-item">
                        <div class="booking-header">
                            <h3><?php echo htmlspecialchars($item['btevent']); ?></h3>
                            <span class="status-badge status-approved">
                                ✅ Approved
                            </span>
                        </div>

                        <div class="booking-details">
                            <div class="detail-row">
                                <span class="detail-label">📅 Event Date:</span>
                                <span class="detail-value"><?php echo date('F j, Y', strtotime($item['btschedule'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">⏰ Event Time:</span>
                                <span class="detail-value"><?php echo date('g:i A', strtotime($item['btschedule'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">📞 Alternative #:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($item['btaddress']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">👥 Attendees:</span>
                                <span class="detail-value"><?php echo $item['btattendees']; ?> people</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">💰 Total Cost:</span>
                                <span class="detail-value">₱<?php echo number_format($item['total_cost'], 2); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">📞 Contact Admin:</span>
                                <span class="detail-value">Please contact admin for any changes or questions</span>
                            </div>

                            <?php if (!empty($item['equipment'])): ?>
                                <div class="detail-row">
                                    <span class="detail-label">🎛️ Equipment:</span>
                                    <div class="detail-value">
                                        <div class="equipment-list">
                                            <ul>
                                                <?php foreach ($item['equipment'] as $equipment): ?>
                                                    <li>
                                                        <?php echo htmlspecialchars($equipment['name']); ?> -
                                                        <?php echo $equipment['quantity']; ?> x
                                                        ₱<?php echo number_format($equipment['price'], 2); ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="button-group">
                            <button type="button" class="btn btn-details" onclick="showBookingDetails('<?php echo $item['id']; ?>')">
                                <span class="btn-icon">📋</span>
                                View Details
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Past Bookings Section -->
        <div class="section">
            <div class="section-header">
                <h2>📋 Booking History</h2>
                <span class="cart-count"><?php echo count($past_items); ?> booking(s)</span>
            </div>

            <?php if (empty($past_items)): ?>
                <div class="empty-message">
                    <h3>No past bookings</h3>
                    <p>Your completed and canceled bookings will appear here</p>
                </div>
            <?php else: ?>
                <?php foreach ($past_items as $item): ?>
                    <div class="booking-item">
                        <div class="booking-header">
                            <h3><?php echo htmlspecialchars($item['btevent']); ?></h3>
                            <span class="status-badge 
                                <?php echo $item['status'] === 'Completed' ? 'status-completed' : 'status-canceled'; ?>">
                                <?php echo $item['status'] === 'Completed' ? '✅ Completed' : '❌ Canceled'; ?>
                            </span>
                        </div>

                        <div class="booking-details">
                            <div class="detail-row">
                                <span class="detail-label">📅 Event Date:</span>
                                <span class="detail-value"><?php echo date('F j, Y', strtotime($item['btschedule'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">⏰ Event Time:</span>
                                <span class="detail-value"><?php echo date('g:i A', strtotime($item['btschedule'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">📞 Alternative #:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($item['btaddress']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">👥 Attendees:</span>
                                <span class="detail-value"><?php echo $item['btattendees']; ?> people</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">💰 Total Cost:</span>
                                <span class="detail-value">₱<?php echo number_format($item['total_cost'], 2); ?></span>
                            </div>

                            <?php if (!empty($item['equipment'])): ?>
                                <div class="detail-row">
                                    <span class="detail-label">🎛️ Equipment:</span>
                                    <div class="detail-value">
                                        <div class="equipment-list">
                                            <ul>
                                                <?php foreach ($item['equipment'] as $equipment): ?>
                                                    <li>
                                                        <?php echo htmlspecialchars($equipment['name']); ?> -
                                                        <?php echo $equipment['quantity']; ?> x
                                                        ₱<?php echo number_format($equipment['price'], 2); ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="button-group">
                            <button type="button" class="btn btn-details" onclick="showBookingDetails('<?php echo $item['id']; ?>')">
                                <span class="btn-icon">📋</span>
                                View Details
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div id="bookingDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Booking Details</h3>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            <div id="modalContent">
                <!-- Content will be loaded here by JavaScript -->
            </div>
        </div>
    </div>

        <script>
        function showBookingDetails(bookingId) {
            // Get all booking data from PHP arrays
            const allBookings = [
                ...<?php echo json_encode(array_values($cart_items)); ?>,
                ...<?php echo json_encode(array_values($pending_items)); ?>,
                ...<?php echo json_encode(array_values($approved_items)); ?>,
                ...<?php echo json_encode(array_values($past_items)); ?>
            ];

            // Find the specific booking
            const booking = allBookings.find(b => b.id == bookingId);
            
            if (booking) {
                console.log('Booking found:', booking); // Debug log
                
                // Calculate equipment total safely
                let equipmentTotal = 0;
                if (booking.equipment && Array.isArray(booking.equipment)) {
                    equipmentTotal = booking.equipment.reduce((total, eq) => {
                        const price = parseFloat(eq.price) || 0;
                        const quantity = parseInt(eq.quantity) || 0;
                        return total + (price * quantity);
                    }, 0);
                }

                // Get catering cost safely
                const cateringCost = parseFloat(booking.catering?.catering_cost) || 0;
                const baseCost = parseFloat(booking.total_cost) - equipmentTotal - cateringCost;

                // Build catering section safely
                let cateringSection = '';
                const catering = booking.catering || {};
                
                const hasCateringData = catering.package_name || 
                                      (catering.dishes && catering.dishes.length > 0) ||
                                      (catering.addons && catering.addons.length > 0) ||
                                      cateringCost > 0;

                if (hasCateringData) {
                    let dishesHtml = '';
                    if (catering.dishes && catering.dishes.length > 0) {
                        dishesHtml = `
                            <div class="modal-detail-row">
                                <span class="modal-detail-label">Selected Dishes:</span>
                                <div class="modal-detail-value catering-items">
                                    <ul>
                                        ${catering.dishes.map(dish => `
                                            <li>
                                                <strong>${escapeHtml(dish.name || 'Unknown Dish')}</strong>
                                                ${dish.category ? ` (${escapeHtml(dish.category)})` : ''}
                                            </li>
                                        `).join('')}
                                    </ul>
                                </div>
                            </div>
                        `;
                    }

                    let addonsHtml = '';
                    if (catering.addons && catering.addons.length > 0) {
                        addonsHtml = `
                            <div class="modal-detail-row">
                                <span class="modal-detail-label">Add-ons:</span>
                                <div class="modal-detail-value catering-items">
                                    <ul>
                                        ${catering.addons.map(addon => `
                                            <li>
                                                ${escapeHtml(addon.name || 'Unknown Addon')} - 
                                                ₱${parseFloat(addon.price || 0).toFixed(2)}
                                            </li>
                                        `).join('')}
                                    </ul>
                                </div>
                            </div>
                        `;
                    }

                    cateringSection = `
                        <div class="modal-section">
                            <h4>🍽️ Catering Details</h4>
                            ${catering.package_name ? `
                            <div class="modal-detail-row">
                                <span class="modal-detail-label">Package:</span>
                                <span class="modal-detail-value">${escapeHtml(catering.package_name)}</span>
                            </div>
                            ` : ''}
                            ${dishesHtml}
                            ${addonsHtml}
                            ${cateringCost > 0 ? `
                            <div class="modal-detail-row">
                                <span class="modal-detail-label">Catering Cost:</span>
                                <span class="modal-detail-value">₱${cateringCost.toFixed(2)}</span>
                            </div>
                            ` : ''}
                        </div>
                    `;
                }

                // Build equipment section safely
                let equipmentSection = '';
                if (booking.equipment && Array.isArray(booking.equipment) && booking.equipment.length > 0) {
                    equipmentSection = `
                        <div class="modal-section">
                            <h4>🎛️ Equipment Rental</h4>
                            <div class="equipment-list">
                                <ul>
                                    ${booking.equipment.map(eq => {
                                        const price = parseFloat(eq.price) || 0;
                                        const quantity = parseInt(eq.quantity) || 0;
                                        const total = price * quantity;
                                        return `
                                            <li>
                                                ${escapeHtml(eq.name || 'Unknown Equipment')} - 
                                                ${quantity} x 
                                                ₱${price.toFixed(2)} = 
                                                ₱${total.toFixed(2)}
                                            </li>
                                        `;
                                    }).join('')}
                                </ul>
                            </div>
                        </div>
                    `;
                }

                // Build modal content
                const modalContent = `
                    <div class="modal-section">
                        <h4>📅 Event Information</h4>
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Event Type:</span>
                            <span class="modal-detail-value">${escapeHtml(booking.btevent || 'Not specified')}</span>
                        </div>
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Date & Time:</span>
                            <span class="modal-detail-value">${formatDateTime(booking.btschedule)}</span>
                        </div>
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Alternative #:</span>
                            <span class="modal-detail-value">${escapeHtml(booking.btaddress || 'Not specified')}</span>
                        </div>
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Number of Attendees:</span>
                            <span class="modal-detail-value">${booking.btattendees || 0} people</span>
                        </div>
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Status:</span>
                            <span class="modal-detail-value">
                                <span class="status-badge ${getStatusClass(booking.status, booking.payment_status)}">
                                    ${getStatusText(booking.status, booking.payment_status)}
                                </span>
                            </span>
                        </div>
                    </div>

                    ${equipmentSection}

                    ${cateringSection}

                    <div class="modal-section">
                        <h4>💰 Cost Breakdown</h4>
                        <div class="cost-breakdown">
                            <div class="cost-item">
                                <span>Base Package:</span>
                                <span>₱${Math.max(0, baseCost).toFixed(2)}</span>
                            </div>
                            ${equipmentTotal > 0 ? `
                            <div class="cost-item">
                                <span>Equipment Rental:</span>
                                <span>₱${equipmentTotal.toFixed(2)}</span>
                            </div>
                            ` : ''}
                            ${cateringCost > 0 ? `
                            <div class="cost-item">
                                <span>Catering Service:</span>
                                <span>₱${cateringCost.toFixed(2)}</span>
                            </div>
                            ` : ''}
                            <div class="cost-total">
                                <span>Total Cost:</span>
                                <span>₱${parseFloat(booking.total_cost || 0).toFixed(2)}</span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-section">
                        <h4>👤 Customer Information</h4>
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Name:</span>
                            <span class="modal-detail-value">${escapeHtml(booking.bt_first_name || '')} ${escapeHtml(booking.bt_last_name || '')}</span>
                        </div>
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Email:</span>
                            <span class="modal-detail-value">${escapeHtml(booking.bt_email || '')}</span>
                        </div>
                    </div>
                `;

                document.getElementById('modalContent').innerHTML = modalContent;
                document.getElementById('bookingDetailsModal').style.display = 'block';
            } else {
                console.error('Booking not found:', bookingId);
                alert('Booking details not found. Please try again.');
            }
        }

        function closeModal() {
            document.getElementById('bookingDetailsModal').style.display = 'none';
        }

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDateTime(dateTimeString) {
            if (!dateTimeString) return 'Not scheduled';
            try {
                const date = new Date(dateTimeString);
                if (isNaN(date.getTime())) return 'Invalid date';
                return date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (e) {
                return 'Invalid date';
            }
        }

        function getStatusClass(status, paymentStatus) {
            if (status === 'Approved') return 'status-approved';
            if (status === 'Completed') return 'status-completed';
            if (status === 'Canceled') return 'status-canceled';
            if (paymentStatus === 'paid') return 'status-paid';
            if (paymentStatus === 'partial') return 'status-partial';
            return 'status-pending';
        }

        function getStatusText(status, paymentStatus) {
            if (status === 'Approved') return '✅ Approved';
            if (status === 'Completed') return '✅ Completed';
            if (status === 'Canceled') return '❌ Canceled';
            if (paymentStatus === 'paid') return '💰 Paid';
            if (paymentStatus === 'partial') return '💵 Partial Payment';
            return '⏳ Pending';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('bookingDetailsModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        function removeFromCart(itemId) {
            if (confirm('Are you sure you want to remove this booking?')) {
                window.location.href = 'remove_booking.php?booking_id=' + itemId;
            }
        }

        // Debug function to check booking data
        function debugBookings() {
            const allBookings = [
                ...<?php echo json_encode(array_values($cart_items)); ?>,
                ...<?php echo json_encode(array_values($pending_items)); ?>,
                ...<?php echo json_encode(array_values($approved_items)); ?>,
                ...<?php echo json_encode(array_values($past_items)); ?>
            ];
            
            console.log('Total bookings found:', allBookings.length);
            allBookings.forEach((booking, index) => {
                console.log(`Booking ${index}:`, {
                    id: booking.id,
                    hasEquipment: !!booking.equipment,
                    equipmentCount: booking.equipment ? booking.equipment.length : 0,
                    hasCatering: !!booking.catering,
                    cateringData: booking.catering
                });
            });
        }
        // Uncomment the line below to enable debug logging
        // debugBookings();
    </script>
</body>
</html>