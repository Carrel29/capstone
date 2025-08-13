<?php
session_start();
include_once "../includes/loginSession.php";
include_once "../includes/userData.php";

// Display payment success message if exists
if (isset($_SESSION['payment_success']) && $_SESSION['payment_success'] === true) {
    echo '<div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 1rem; margin: 1rem 0; border-radius: 4px; text-align: center;">';
    echo htmlspecialchars($_SESSION['payment_message']);
    echo '</div>';

    // Clear the message
    unset($_SESSION['payment_success']);
    unset($_SESSION['payment_message']);
}

// Database Connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'btonedatabase';
$port = '3308';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the customer name from session
$customer_name = isset($_SESSION['login']) ? $_SESSION['login'] :
    (isset($_SESSION['fullname']) ? $_SESSION['fullname'] : '');

// Get cart items
$cart_sql = "SELECT ci.*, be.equipment_id, be.quantity as equipment_quantity, i.item_name, i.unit_price 
        FROM customer_inquiries ci
        LEFT JOIN booking_equipment be ON ci.id = be.booking_id 
        LEFT JOIN inventory i ON be.equipment_id = i.id 
        WHERE ci.status = 'In Cart' 
        AND ci.customer_name = ?
        ORDER BY ci.created_at DESC";

$cart_stmt = $conn->prepare($cart_sql);
$cart_stmt->bind_param("s", $customer_name);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

$cart_items = [];
$cart_total = 0;

while ($row = $cart_result->fetch_assoc()) {
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

// Get pending bookings
$pending_sql = "SELECT ci.*, be.equipment_id, be.quantity as equipment_quantity, i.item_name, i.unit_price 
        FROM customer_inquiries ci
        LEFT JOIN booking_equipment be ON ci.id = be.booking_id 
        LEFT JOIN inventory i ON be.equipment_id = i.id 
        WHERE ci.status = 'Pending' 
        AND ci.customer_name = ?
        ORDER BY ci.created_at DESC";

$pending_stmt = $conn->prepare($pending_sql);
$pending_stmt->bind_param("s", $customer_name);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();

$pending_items = [];
$pending_total = 0;

while ($row = $pending_result->fetch_assoc()) {
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart & Bookings - BTONE</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        header {
            background-color: #333;
            color: white;
            padding: 1rem;
            margin-bottom: 20px;
        }

        .company-name {
            margin: 0;
            font-size: 2rem;
        }

        .nav-bar ul {
            list-style: none;
            display: flex;
            gap: 20px;
            margin: 0;
            padding: 0;
        }

        .nav-bar a {
            color: white;
            text-decoration: none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        .current-time {
            color: #666;
            font-size: 0.9em;
        }

        .booking-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #fff;
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
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
            margin: 5px 0;
            padding: 5px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            align-items: center;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .btn-checkout {
            background-color: #28a745;
            color: white;
        }

        .btn-remove {
            background-color: #dc3545;
            color: white;
        }

        .empty-message {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }

        .status-pending {
            background-color: #ffc107;
            color: #000;
        }

        .user-info {
            margin-bottom: 20px;
            padding: 10px;
            background: #e9ecef;
            border-radius: 4px;
        }

        .section-divider {
            margin: 40px 0;
            border: 0;
            border-top: 2px solid #eee;
        }
    </style>
</head>

<body>
    <header>
        <h1 class="company-name">BTONE</h1>
        <nav class="nav-bar">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="adding.php">Add Booking</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="user-info">
            <strong>Welcome, <?php echo htmlspecialchars($customer_name); ?></strong>
            <div class="current-time">
                <?php echo date('Y-m-d H:i:s'); ?>
            </div>
        </div>

        <!-- Shopping Cart Section -->
        <div class="section">
            <div class="section-header">
                <h2>Shopping Cart</h2>
            </div>

            <?php if (empty($cart_items)): ?>
                <div class="empty-message">
                    <h3>Your cart is empty</h3>
                    <p>Add some bookings to get started!</p>
                    <a href="adding.php" class="btn btn-checkout">Add Booking</a>
                </div>
            <?php else: ?>
                <?php foreach ($cart_items as $item): ?>
                    <div class="booking-item">
                        <div class="booking-header">
                            <h3><?php echo htmlspecialchars($item['event_package']); ?></h3>
                            <span>₱<?php echo number_format($item['total_cost'], 2); ?></span>
                        </div>

                        <div class="booking-details">
                            <p><strong>Date:</strong> <?php echo htmlspecialchars($item['event_date']); ?></p>
                            <p><strong>Time:</strong> <?php echo htmlspecialchars($item['event_time']); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($item['location_type']); ?></p>

                            <?php if (!empty($item['equipment'])): ?>
                                <div class="equipment-list">
                                    <p><strong>Equipment:</strong></p>
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
                            <?php endif; ?>
                        </div>

                        <div class="button-group">
                            <button class="btn btn-remove" onclick="removeFromCart(<?php echo $item['id']; ?>)">
                                Remove
                            </button>
                            <form action="paying.php" method="POST" style="margin: 0;">
                                <input type="hidden" name="booking_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="customer_name"
                                    value="<?php echo htmlspecialchars($customer_name); ?>">
                                <button type="submit" class="btn btn-checkout">Checkout</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pending Bookings Section -->
        <div class="section">
            <div class="section-header">
                <h2>Pending Bookings</h2>
            </div>

            <?php if (empty($pending_items)): ?>
                <div class="empty-message">
                    <h3>No pending bookings</h3>
                </div>
            <?php else: ?>
                <?php foreach ($pending_items as $item): ?>
                    <div class="booking-item">
                        <div class="booking-header">
                            <h3><?php echo htmlspecialchars($item['event_package']); ?></h3>
                            <span class="status-badge status-pending">Pending</span>
                        </div>

                        <div class="booking-details">
                            <p><strong>Date:</strong> <?php echo htmlspecialchars($item['event_date']); ?></p>
                            <p><strong>Time:</strong> <?php echo htmlspecialchars($item['event_time']); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($item['location_type']); ?></p>
                            <p><strong>Total Cost:</strong> ₱<?php echo number_format($item['total_cost'], 2); ?></p>

                            <?php if (!empty($item['equipment'])): ?>
                                <div class="equipment-list">
                                    <p><strong>Equipment:</strong></p>
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
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function removeFromCart(itemId) {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                fetch('remove_from_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        item_id: itemId
                    })
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            window.location.reload();
                        } else {
                            throw new Error(data.message || 'Error removing item from cart');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error removing item: ' + error.message);
                    });
            }
        }
    </script>
</body>

</html>