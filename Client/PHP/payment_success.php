<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit();
}

// Check if payment was successful
if (!isset($_SESSION['payment_success']) || !$_SESSION['payment_success']) {
    header("Location: booking-form.php");
    exit();
}

// Get the booking ID from session
$booking_id = $_SESSION['last_booking_id'] ?? null;
if (!$booking_id) {
    header("Location: booking-form.php");
    exit();
}

// Get booking details
try {
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        header("Location: booking-form.php");
        exit();
    }
    
    // Get catering details if exists
    $catering_stmt = $pdo->prepare("
        SELECT co.*, cp.name as package_name, cp.base_price 
        FROM catering_orders co 
        LEFT JOIN catering_packages cp ON co.package_id = cp.id 
        WHERE co.booking_id = ? AND co.status = 'confirmed'
    ");
    $catering_stmt->execute([$booking_id]);
    $catering_order = $catering_stmt->fetch();
    
} catch (Exception $e) {
    die("Error retrieving booking details: " . $e->getMessage());
}

// Clear the payment success flag and session data
unset($_SESSION['payment_success']);
unset($_SESSION['payment_message']);
unset($_SESSION['last_booking_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - BTONE Events</title>
    <style>
        body {
            background: #f5f1e8;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #5d4037;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .success-container {
            max-width: 600px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(141,110,99,0.15);
            padding: 40px;
            border: 1px solid #d7ccc8;
            text-align: center;
        }
        
        .success-icon {
            font-size: 4rem;
            color: #4caf50;
            margin-bottom: 20px;
        }
        
        .success-container h1 {
            color: #6d4c41;
            margin: 0 0 20px 0;
        }
        
        .success-container p {
            font-size: 1.1rem;
            margin: 10px 0;
            color: #7d6e63;
        }
        
        .booking-details {
            background: #f5eee6;
            border: 2px solid #d7ccc8;
            border-radius: 12px;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }
        
        .booking-details h3 {
            color: #6d4c41;
            margin-top: 0;
            border-bottom: 2px solid #8d6e63;
            padding-bottom: 10px;
        }
        
        .catering-details {
            background: #e8f5e9;
            border: 2px solid #4caf50;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
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
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #8d6e63, #6d4c41);
            color: white;
        }
        
        .btn-secondary {
            background: #e0e0e0;
            color: #5d4037;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .payment-status {
            background: #e8f5e9;
            border: 2px solid #4caf50;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            font-weight: bold;
            color: #2e7d32;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            padding: 5px 0;
            border-bottom: 1px solid #e8e2da;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .total-amount {
            font-size: 1.2rem;
            font-weight: bold;
            color: #6d4c41;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #8d6e63;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">✅</div>
        <h1>Payment Successful!</h1>
        
        <div class="payment-status">
            <?php echo htmlspecialchars($_SESSION['payment_message'] ?? 'Your booking has been confirmed and payment has been processed successfully.'); ?>
        </div>
        
        <p>Thank you for your payment. Your booking is now confirmed.</p>
        
        <div class="booking-details">
            <h3>Booking Details</h3>
            
            <div class="detail-item">
                <span><strong>Booking ID:</strong></span>
                <span>#<?php echo $booking_id; ?></span>
            </div>
            
            <div class="detail-item">
                <span><strong>Event Type:</strong></span>
                <span><?php echo htmlspecialchars($booking['btevent']); ?></span>
            </div>
            
            <div class="detail-item">
                <span><strong>Date:</strong></span>
                <span><?php echo date('F j, Y', strtotime($booking['btschedule'])); ?></span>
            </div>
            
            <div class="detail-item">
                <span><strong>Time:</strong></span>
                <span><?php echo date('g:i A', strtotime($booking['btschedule'])); ?></span>
            </div>
            
            <div class="detail-item">
                <span><strong>Duration:</strong></span>
                <span><?php echo round((strtotime($booking['EventDuration']) - strtotime($booking['btschedule'])) / 3600); ?> hours</span>
            </div>
            
            <div class="detail-item">
                <span><strong>Attendees:</strong></span>
                <span><?php echo $booking['btattendees']; ?> people</span>
            </div>
            
            <div class="detail-item">
                <span><strong>Address:</strong></span>
                <span><?php echo htmlspecialchars($booking['btaddress']); ?></span>
            </div>
            
            <?php if ($catering_order): ?>
                <div class="catering-details">
                    <h4>🍽️ Catering Service Included</h4>
                    <div class="detail-item">
                        <span><strong>Package:</strong></span>
                        <span><?php echo htmlspecialchars($catering_order['package_name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span><strong>Catering Cost:</strong></span>
                        <span>₱<?php echo number_format($catering_order['base_price'], 2); ?></span>
                    </div>
                    <div class="detail-item">
                        <span><strong>Status:</strong></span>
                        <span style="color: #4caf50; font-weight: bold;">Confirmed</span>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="detail-item total-amount">
                <span><strong>Total Amount Paid:</strong></span>
                <span>₱<?php echo number_format($booking['total_cost'], 2); ?></span>
            </div>
            
            <div class="detail-item">
                <span><strong>Payment Status:</strong></span>
                <span style="color: #4caf50; font-weight: bold;"><?php echo ucfirst($booking['payment_status']); ?></span>
            </div>
        </div>
        
        <p>You will receive a confirmation email shortly with all the details of your booking.</p>
        
        <div class="btn-group">
            <a href="booking-form.php" class="btn btn-primary">Book Another Event</a>
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
</body>
</html>