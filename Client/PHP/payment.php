<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);


if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit();
}

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=btonedatabase;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Get booking ID from URL or session
$booking_id = $_GET['booking_id'] ?? $_SESSION['current_booking_id'] ?? null;

if (!$booking_id) {
    header("Location: user_cart.php");
    exit();
}

// Get booking details
try {
    $booking_stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND btuser_id = ?");
    $booking_stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $booking_stmt->fetch();
    
    if (!$booking) {
        header("Location: user_cart.php");
        exit();
    }
} catch (Exception $e) {
    die("Error fetching booking: " . $e->getMessage());
}

// Check if catering is included
$catering_total = 0;
$catering_details = [];
if (isset($_SESSION['catering_info'])) {
    $catering_total = $_SESSION['catering_info']['total'];
    $catering_details = $_SESSION['catering_info'];
}

// Calculate total amount (booking + catering)
$totalAmount = $booking['total_cost'] + $catering_total;

$minimumPayment = $totalAmount * 0.2;
$error = '';

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    $paymentAmount = floatval($_POST['payment_amount']);
    $paymentType = $_POST['payment_type'];
    $referenceNumber = trim($_POST['reference_number']);
    $customerName = $_SESSION['bt_first_name'] ?? 'Customer';

    // Validate payment amount
    if ($paymentType === 'partial' && $paymentAmount < $minimumPayment) {
        $error = "Partial payment must be at least 20% of the total amount (₱" . number_format($minimumPayment, 2) . ")";
    } elseif ($paymentAmount > $totalAmount) {
        $error = "Payment amount cannot exceed total amount of ₱" . number_format($totalAmount, 2);
    } elseif (empty($referenceNumber)) {
        $error = "Please enter a valid GCash reference number";
    } else {
        try {
            $pdo->beginTransaction();

            // Determine payment status
            $paymentStatus = ($paymentType === 'full' || $paymentAmount >= $totalAmount) ? 'paid' : 'partial';

            // Update booking payment status
            $stmt = $pdo->prepare("UPDATE bookings SET payment_status = ?, total_cost = ? WHERE id = ?");
            $stmt->execute([$paymentStatus, $totalAmount, $booking_id]);

            // Insert into sales table
            $stmt = $pdo->prepare("INSERT INTO sales 
                (btuser_id, booking_id, GcashReferenceNo, TotalAmount, AmountPaid, Status, DateCreated)
                VALUES (?, ?, ?, ?, ?, 1, NOW())");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $booking_id,
                $referenceNumber,
                $totalAmount,
                $paymentAmount
            ]);

            // Update catering order status if exists
            if (isset($_SESSION['catering_order_id'])) {
                $stmt = $pdo->prepare("UPDATE catering_orders SET status = 'confirmed' WHERE id = ?");
                $stmt->execute([$_SESSION['catering_order_id']]);
            }

            // Insert into payment logs
            $stmt = $pdo->prepare("INSERT INTO payment_status_log 
                (booking_id, old_payment_status, new_payment_status, changed_by, created_at)
                VALUES (?, 'unpaid', ?, ?, NOW())");
            
            $stmt->execute([
                $booking_id,
                $paymentStatus,
                $customerName
            ]);

            $pdo->commit();

            // Store booking ID for success page
            $_SESSION['last_booking_id'] = $booking_id;
            
            // Clear session data
            unset($_SESSION['current_booking_id']);
            unset($_SESSION['catering_info']);
            unset($_SESSION['catering_order_id']);
            unset($_SESSION['selected_package']);
            unset($_SESSION['selected_dishes']);
            unset($_SESSION['selected_addons']);

            $_SESSION['payment_success'] = true;
            $_SESSION['payment_message'] = "Payment of ₱" . number_format($paymentAmount, 2) . " processed successfully!";

            header("Location: payment_success.php");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Payment processing failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - BTONE Events</title>
    <style>
        body {
            background: #f5f1e8;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 20px;
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
            margin: 0 0 20px 0;
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
        
        .payment-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(141,110,99,0.15);
            padding: 30px;
            border: 1px solid #d7ccc8;
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .payment-header h1 {
            color: #6d4c41;
            margin: 0;
        }
        
        .order-summary {
            margin: 20px 0;
        }
        
        .booking-details, .catering-details {
            border: 2px solid #d7ccc8;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            background: #faf7f4;
        }
        
        .catering-items {
            margin: 10px 0;
            padding: 10px;
            background: #f5eee6;
            border-radius: 8px;
        }
        
        .dish-list {
            list-style: none;
            padding: 0;
            margin: 5px 0;
        }
        
        .dish-list li {
            padding: 2px 0;
            border-bottom: 1px dashed #d7ccc8;
        }
        
        .dish-list li:last-child {
            border-bottom: none;
        }
        
        .total-amount {
            background: linear-gradient(135deg, #8d6e63, #6d4c41);
            color: #fff;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 30px 0;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .payment-info {
            background: #f5eee6;
            border: 2px solid #d7ccc8;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .payment-info h3 {
            color: #6d4c41;
            margin-top: 0;
            border-bottom: 2px solid #8d6e63;
            padding-bottom: 10px;
        }
        
        .qr-code-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            border: 2px solid #d7ccc8;
        }
        
        .qr-code-section img {
            max-width: 250px;
            border: 3px solid #8d6e63;
            border-radius: 12px;
            padding: 10px;
            background: #fff;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #5d4037;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #d7ccc8;
            border-radius: 8px;
            font-size: 16px;
            background: #faf7f4;
            color: #5d4037;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: #8d6e63;
            outline: none;
            background: #fff;
        }
        
        .payment-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }
        
        .payment-option {
            border: 2px solid #d7ccc8;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .payment-option.selected {
            border-color: #8d6e63;
            background: #f5eee6;
        }
        
        .payment-option h4 {
            margin: 0 0 10px 0;
            color: #6d4c41;
        }
        
        .pay-now-btn {
            background: linear-gradient(135deg, #388e3c, #2e7d32);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: block;
            margin: 30px auto;
            width: 200px;
        }
        
        .pay-now-btn:hover {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            transform: translateY(-2px);
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #ffcdd2;
            text-align: center;
        }
        
        .info-text {
            color: #8d6e63;
            font-size: 14px;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .payment-options {
                grid-template-columns: 1fr;
            }
            
            .payment-container {
                margin: 10px;
                padding: 20px;
            }
        }
    </style>
    <script>
        function updatePaymentAmount() {
            const paymentType = document.querySelector('input[name="payment_type"]:checked').value;
            const totalAmount = <?php echo $totalAmount; ?>;
            const minimumPayment = <?php echo $minimumPayment; ?>;
            const paymentAmountInput = document.getElementById('payment_amount');
            
            if (paymentType === 'full') {
                paymentAmountInput.value = totalAmount.toFixed(2);
                paymentAmountInput.readOnly = true;
            } else {
                paymentAmountInput.value = minimumPayment.toFixed(2);
                paymentAmountInput.readOnly = false;
                paymentAmountInput.min = minimumPayment;
            }
        }
        
        function validatePayment() {
            const paymentAmount = parseFloat(document.getElementById('payment_amount').value);
            const totalAmount = <?php echo $totalAmount; ?>;
            const referenceNumber = document.getElementById('reference_number').value;
            
            if (!referenceNumber) {
                alert('Please enter your GCash reference number.');
                return false;
            }
            
            if (paymentAmount > totalAmount) {
                alert('Payment amount cannot exceed total amount.');
                return false;
            }
            
            return true;
        }
    </script>
</head>
<body>
    <a href="javascript:history.back()" class="back-btn">&#8592; Back</a>

    <div class="payment-container">
        <div class="payment-header">
            <h1>Payment</h1>
            <p>Complete your booking payment</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="order-summary">
            <h2>Order Summary</h2>
            
            <!-- Booking Details -->
            <div class="booking-details">
                <h3>Booking Details</h3>
                <p><strong>Event Type:</strong> <?php echo htmlspecialchars($booking['btevent']); ?></p>
                <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($booking['btschedule'])); ?></p>
                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['btschedule'])); ?></p>
                <p><strong>Attendees:</strong> <?php echo $booking['btattendees']; ?> people</p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($booking['btaddress']); ?></p>
                <p><strong>Booking Cost:</strong> ₱<?php echo number_format($booking['total_cost'], 2); ?></p>
            </div>
            
            <!-- Catering Details -->
            <?php if ($catering_total > 0): ?>
                <div class="catering-details">
                    <h3>Catering Service</h3>
                    <p><strong>Catering Cost:</strong> ₱<?php echo number_format($catering_total, 2); ?></p>
                    
                    <?php if (isset($catering_details['package_id'])): ?>
                        <?php
                        // Fetch package name
                        $package_stmt = $pdo->prepare("SELECT name FROM catering_packages WHERE id = ?");
                        $package_stmt->execute([$catering_details['package_id']]);
                        $package = $package_stmt->fetch();
                        ?>
                        <p><strong>Package:</strong> <?php echo htmlspecialchars($package['name'] ?? 'Selected Package'); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($catering_details['dishes'])): ?>
    <div class="catering-items">
        <strong>Selected Dishes:</strong>
        <ul class="dish-list">
            <?php
            $dish_ids = $catering_details['dishes'];

            if (!empty($dish_ids)) {
                // Build placeholders (?, ?, ?, ...)
                $placeholders = implode(',', array_fill(0, count($dish_ids), '?'));
                $dish_stmt = $pdo->prepare("SELECT name FROM catering_dishes WHERE id IN ($placeholders)");
                $dish_stmt->execute($dish_ids);
                $dishes = $dish_stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($dishes as $dish): ?>
                    <li><?php echo htmlspecialchars($dish['name']); ?></li>
                <?php endforeach;
            } else {
                echo "<li>No dishes selected</li>";
            }
            ?>
        </ul>
    </div>
<?php endif; ?>

                </div>
            <?php endif; ?>
        </div>
        
        <div class="total-amount">
            Total Amount: ₱<?php echo number_format($totalAmount, 2); ?>
        </div>

        <div class="payment-info">
            <h3>Payment Information</h3>
            <p><strong>Minimum Payment (20%):</strong> ₱<?php echo number_format($minimumPayment, 2); ?></p>
            <p><strong>Payment Method:</strong> GCash Only</p>
            <p>Please make your payment through GCash and enter the reference number below.</p>
        </div>

        <div class="qr-code-section">
            <h3>Scan to Pay via GCash</h3>
            <img src="../img/sample.png" alt="GCash QR Code">
            <p>Scan the QR code above to make your payment</p>
        </div>

        <form method="POST" action="payment.php?booking_id=<?php echo $booking_id; ?>" onsubmit="return validatePayment()">
            <div class="form-group">
                <label>Payment Type</label>
                <div class="payment-options">
                    <label class="payment-option" onclick="document.getElementById('full').checked = true; updatePaymentAmount()">
                        <input type="radio" id="full" name="payment_type" value="full" checked onchange="updatePaymentAmount()">
                        <h4>Full Payment</h4>
                        <p>Pay the full amount: ₱<?php echo number_format($totalAmount, 2); ?></p>
                    </label>
                    
                    <label class="payment-option" onclick="document.getElementById('partial').checked = true; updatePaymentAmount()">
                        <input type="radio" id="partial" name="payment_type" value="partial" onchange="updatePaymentAmount()">
                        <h4>Partial Payment</h4>
                        <p>Minimum: ₱<?php echo number_format($minimumPayment, 2); ?></p>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="payment_amount">Payment Amount (₱)</label>
                <input type="number" id="payment_amount" name="payment_amount" step="0.01" min="<?php echo $minimumPayment; ?>" 
                       value="<?php echo number_format($totalAmount, 2); ?>" required>
                <p class="info-text">Minimum partial payment: ₱<?php echo number_format($minimumPayment, 2); ?></p>
            </div>

            <div class="form-group">
                <label for="reference_number">GCash Reference Number</label>
                <input type="text" id="reference_number" name="reference_number" required 
                       placeholder="Enter your GCash reference number" pattern="[A-Za-z0-9-]+"
                       title="Please enter a valid GCash reference number">
                <p class="info-text">You can find this in your GCash transaction history</p>
            </div>

            <button type="submit" name="submit_payment" class="pay-now-btn">
                Confirm Payment
            </button>
        </form>
    </div>

    <script>
        // Initialize payment amount
        updatePaymentAmount();
    </script>
</body>
</html>