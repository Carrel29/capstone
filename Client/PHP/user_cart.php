
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

// Database Connection - using the same connection as your other files
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

// Get the customer name from session
$customer_name = isset($_SESSION['login']) ? $_SESSION['login'] :
    (isset($_SESSION['fullname']) ? $_SESSION['fullname'] : '');

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? 0;

// Get cart items (bookings with status 'In Cart')
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
                                <span class="detail-label">📍 Address:</span>
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
                            <form action="remove_booking.php" method="POST" style="margin: 0;">
                                <input type="hidden" name="booking_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn btn-remove" onclick="return confirm('Are you sure you want to remove this booking?')">
                                    <span class="btn-icon">🗑️</span>
                                    Remove
                                </button>
                            </form>
                            <form action="payment.php" method="GET" style="margin: 0;">
                                <input type="hidden" name="booking_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn btn-checkout">
                                    <span class="btn-icon">💳</span>
                                    Proceed to Payment
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
                                <span class="detail-label">📍 Address:</span>
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
                    </div>
                <?php endforeach; ?>
                
                <div class="total-amount">
                    Total Pending Amount: ₱<?php echo number_format($pending_total, 2); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function removeFromCart(itemId) {
            if (confirm('Are you sure you want to remove this booking?')) {
                window.location.href = 'remove_booking.php?booking_id=' + itemId;
            }
        }
    </script>
</body>

</html>