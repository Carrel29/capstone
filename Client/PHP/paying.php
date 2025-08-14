<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=btonedatabase;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Variables for form data and messages
$bookingId = $_POST['booking_id'] ?? '';
$customerName = $_POST['customer_name'] ?? '';
$totalCost = null;
$downPaymentRequired = null;
$error = '';
$success = '';

// Fetch booking details when ID and name are provided
if (!empty($bookingId) && !empty($customerName)) {
    $stmt = $pdo->prepare("SELECT b.id, b.btuser_id, b.btservices, s.TotalAmount, s.AmountPaid, b.payment_status 
                           FROM bookings b
                           LEFT JOIN sales s ON b.id = s.booking_id
                           WHERE b.id = ? AND u.bt_first_name = ?");
    $stmt->execute([$bookingId, $customerName]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($booking) {
        $totalCost = floatval($booking['TotalAmount']);
        $downPaymentRequired = $totalCost * 0.4; // 40% of total cost
    } else {
        $error = "Booking not found or customer name does not match. Please check your Booking ID and Name.";
    }
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment']) && $booking) {
    $downPaymentAmount = floatval($_POST['down_payment_amount']);
    $isFullPayment = isset($_POST['full_payment']) && $_POST['full_payment'] === 'on' ? true : false;
    $referenceNumber = isset($_POST['reference_number']) ? trim($_POST['reference_number']) : '';

    // Validate reference number
    if (empty($referenceNumber)) {
        $error = "Please enter a valid reference number.";
    } else {
        $currentPaid = floatval($booking['AmountPaid'] ?? 0);
        $totalPaid = $currentPaid + $downPaymentAmount;
        $totalExpected = floatval($booking['TotalAmount']);

        try {
            $pdo->beginTransaction();

            // Validate payment
            if ($totalPaid > $totalExpected) {
                throw new Exception("Payment amount exceeds total cost.");
            }
            if ($totalPaid < $totalExpected * 0.4 && $totalPaid > 0) {
                throw new PDOException("Down payment must be at least 40% of the total cost (₱" . number_format($totalExpected * 0.4, 2) . ").");
            }

            // Determine new payment status
            $newPaymentStatus = $totalPaid >= $totalExpected ? 'paid' : ($totalPaid >= $totalExpected * 0.4 ? 'partial' : 'pending');

            // Update customer_inquiries
            $stmt = $pdo->prepare("UPDATE customer_inquiries 
                               SET down_payment_status = ?,
                                   down_payment_amount = ?,
                                   payment_status = ?,
                                   updated_at = NOW(),
                                   last_updated = NOW(),
                                   payment_verified = 1,
                                   payment_notes = ?
                               WHERE id = ?");
            $stmt->execute([
                $newDownPaymentStatus,
                $newDownPaymentAmount,
                $newPaymentStatus,
                "Reference Number: " . $referenceNumber,
                $bookingId
            ]);

            // Insert into payment_status_log
            $stmt = $pdo->prepare("INSERT INTO payment_status_log 
                               (inquiry_id, old_down_payment_status, new_down_payment_status,
                                old_payment_status, new_payment_status, changed_by)
                               VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $bookingId,
                $booking['down_payment_status'],
                $newDownPaymentStatus,
                $booking['payment_status'],
                $newPaymentStatus,
                $customerName
            ]);

            $pdo->commit();

            // Set success message and redirect
            $_SESSION['payment_success'] = true;
            $_SESSION['payment_message'] = "Payment of ₱" . number_format($downPaymentAmount, 2) . " has been processed successfully!";

            header("Location: user_cart.php");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Payment Submission</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        input[type="checkbox"] {
            margin-right: 5px;
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        button:hover {
            background-color: #45a049;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }

        .success {
            color: green;
            text-align: center;
            margin-bottom: 15px;
        }

        .info {
            color: #555;
            margin-bottom: 15px;
        }

        .form-group img {
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 8px;
            background-color: white;
        }

        .form-group input[type="text"]#reference_number {
            font-family: monospace;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .form-group p {
            margin: 10px 0;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Submit Payment</h1>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="paying.php" id="paymentForm">
            <div class="form-group">
                <label for="booking_id">Booking ID:</label>
                <input type="text" id="booking_id" name="booking_id" value="<?php echo htmlspecialchars($bookingId); ?>"
                    required placeholder="Enter your booking ID">
            </div>
            <div class="form-group">
                <label for="customer_name">Customer Name:</label>
                <input type="text" id="customer_name" name="customer_name"
                    value="<?php echo htmlspecialchars($customerName); ?>" required placeholder="Enter your full name">
            </div>

            <?php if ($totalCost !== null): ?>
                <div class="info">
                    <p>Total Cost of Event: ₱<?php echo number_format($totalCost, 2); ?></p>
                    <p>Required Down Payment (40%): ₱<?php echo number_format($downPaymentRequired, 2); ?></p>
                    <?php if (isset($booking)): ?>
                        <p>Current Down Payment Status: <strong><?php echo ucfirst($booking['down_payment_status']); ?></strong>
                        </p>
                        <p>Current Down Payment Amount:
                            <strong>₱<?php echo number_format($booking['down_payment_amount'], 2); ?></strong></p>
                        <p>Overall Payment Status: <strong><?php echo ucfirst($booking['payment_status']); ?></strong></p>
                    <?php endif; ?>
                </div>

                <!-- QR Code Image -->
                <div class="form-group" style="text-align: center; margin: 20px 0;">
                    <img src="../Img/sample.png" alt="Payment QR Code" style="max-width: 200px; margin-bottom: 15px;">
                    <p style="color: #666; margin-bottom: 15px;">Scan the QR code to process your payment</p>
                </div>

                <!-- Reference Number Input -->
                <div class="form-group">
                    <label for="reference_number">Reference Number:</label>
                    <input type="text" id="reference_number" name="reference_number" required
                        placeholder="Enter your payment reference number" pattern="[A-Za-z0-9-]+"
                        title="Please enter a valid reference number">
                </div>

                <div class="form-group">
                    <label for="down_payment_amount">Down Payment Amount (₱):</label>
                    <input type="number" id="down_payment_amount" name="down_payment_amount" step="0.01" min="0"
                        value="<?php echo number_format($downPaymentRequired, 2); ?>" required placeholder="Enter amount">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="full_payment" name="full_payment"> Mark as Full Payment
                    </label>
                </div>
                <button type="submit" name="submit_payment">Submit Payment</button>
            <?php else: ?>
                <button type="submit" name="lookup">Lookup Booking</button>
            <?php endif; ?>
        </form>
    </div>

    <script>
        // Auto-submit form only for lookup, not after payment submission
        document.getElementById('booking_id').addEventListener('change', function () {
            if (!document.querySelector('[name="submit_payment"]')) {
                this.form.submit();
            }
        });
        document.getElementById('customer_name').addEventListener('change', function () {
            if (!document.querySelector('[name="submit_payment"]')) {
                this.form.submit();
            }
        });
    </script>
</body>

</html>