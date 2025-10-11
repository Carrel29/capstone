<?php
   use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
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
    $stmt = $pdo->prepare("SELECT b.*, u.bt_first_name, u.bt_last_name, u.bt_email 
                           FROM bookings b
                           JOIN btuser u ON b.btuser_id = u.bt_user_id
                           WHERE b.id = ?");
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

// ✅ SEND MOA EMAIL AFTER SUCCESSFUL PAYMENT
if ($booking) {
    $clientName   = $booking['bt_first_name'] . " " . $booking['bt_last_name'];
    $clientEmail  = $booking['bt_email'];
    $clientAddr   = $booking['btaddress'];
    $eventName    = $booking['btevent'];
    $eventDate    = date("F d, Y", strtotime($booking['btschedule']));
    $totalCost    = number_format($booking['total_cost'], 2);
    $downpayment  = number_format($booking['total_cost'] * 0.20, 2);
    $balance      = number_format($booking['total_cost'] - ($booking['total_cost'] * 0.20), 2);

    $moa = "
<h2 style='text-align:center;'>MEMORANDUM OF AGREEMENT</h2>

<p><b>Between</b> BTONE EVENTS PLACE <br> -and- <br> {$booking['bt_first_name']} {$booking['bt_last_name']}</p>

<h3>I. PARTIES</h3>
<p>
This Memorandum of Agreement (“MOA”) is entered into on this " . date("jS") . " day of " . date("F") . ", " . date("Y") . ", by and between:
</p>

<p>
BTONE Events Place, a duly registered company with principal office at Baras, Rizal, represented herein by its duly authorized representative, hereinafter referred to as the “Events Place Provider”;
</p>

<p>-and-</p>

<p>
{$booking['bt_first_name']} {$booking['bt_last_name']}, with principal office/residential address at {$booking['btaddress']}, represented herein by {$booking['bt_first_name']} {$booking['bt_last_name']}, hereinafter referred to as the “Client.”
</p>

<h3>II. PURPOSE</h3>
<p>The purpose of this MOA is to establish the terms and conditions for the reservation, use, and payment of facilities at BTONE Events Place in Baras, Rizal, booked through its online platform for the event of the Client.</p>

<h3>III. TERMS AND CONDITIONS</h3>

<ol>
<li><b>Downpayment and Booking</b>
   <ul>
     <li>The Client shall reserve the venue through the official BTONE Events Place online booking system.</li>
     <li>A 20% downpayment of the total rental fee is required to secure the booking.</li>
     <li>The downpayment shall be deductible from the total rental fee.</li>
   </ul>
</li>

<li><b>Use of Venue</b>
   <ul>
     <li>The Events Place Provider grants the Client the right to use the venue located at Baras, Rizal, on the agreed date(s): " . date("F d, Y", strtotime($booking['btschedule'])) . ".</li>
     <li>The Client shall use the venue only for lawful and agreed purposes.</li>
   </ul>
</li>

<li><b>Payment Terms</b>
   <ul>
     <li>Total rental fee: ₱" . number_format($booking['total_cost'], 2) . ".</li>
     <li>Downpayment: 20% of the total rental fee (non-refundable except as provided under cancellation terms).</li>
     <li>Balance payment of ₱" . number_format($booking['total_cost'] - ($booking['total_cost'] * 0.20), 2) . " shall be settled no later than 7 days before the event.</li>
   </ul>
</li>

<li><b>Obligations of BTONE Events Place</b>
   <ul>
     <li>Provide access to the venue and facilities as agreed.</li>
     <li>Ensure the venue is clean, safe, and in good condition prior to the event.</li>
     <li>Provide necessary support staff (if applicable and agreed upon).</li>
   </ul>
</li>

<li><b>Obligations of the Client</b>
   <ul>
     <li>Comply with venue rules and regulations.</li>
     <li>Be responsible for the conduct of guests and participants during the event.</li>
     <li>Shoulder any damages to the venue or facilities.</li>
     <li>In cases where items are broken, lost, or damaged due to the Client or their attendees, the Client agrees to shoulder the cost of repair or replacement. A corresponding fee will be determined and mutually agreed upon by both parties after the event.</li>
   </ul>
</li>

<li><b>Cancellations and Refunds</b>
   <ul>
     <li>If canceled at least 5 weeks before the event date, 100% of the total rental fee paid will be refunded.</li>
     <li>If canceled at least 4 weeks before the event date, 80% of the total rental fee paid will be refunded.</li>
     <li>If canceled 3 weeks before the event, 50% of the total rental fee paid will be refunded.</li>
     <li>If canceled 2 weeks before the event, no refund will be provided.</li>
   </ul>
</li>

<li><b>Liability and Force Majeure</b>
   <ul>
     <li>BTONE Events Place shall not be liable for any loss, accident, or damage to persons or property during the event, except when caused by gross negligence.</li>
     <li>Neither party shall be held liable for failure to perform obligations due to force majeure events such as natural disasters, government restrictions, or other unforeseen circumstances beyond control.</li>
   </ul>
</li>
</ol>

<h3>IV. EFFECTIVITY</h3>
<p>This MOA shall take effect on the date of confirmation via email by both parties and shall remain valid until the completion of the agreed event and full settlement of obligations.</p>

<h3>V. AMENDMENTS</h3>
<p>Any amendments to this MOA shall be made in writing and confirmed via email by both parties.</p>

<h3>VI. GOVERNING LAW</h3>
<p>This MOA shall be governed by and construed in accordance with the laws of the Republic of the Philippines.</p>

<h3>VII. CONFIRMATION VIA EMAIL</h3>
<p>As this MOA is transmitted electronically, no handwritten signatures are required. The parties agree that confirmation and acknowledgment via email shall constitute full and binding acceptance of this Agreement.</p>
";


    // --- PHPMailer setup ---
 
    require '../phpmailer/Exception.php';
    require '../phpmailer/PHPMailer.php';
    require '../phpmailer/SMTP.php';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'lanceaeronm@gmail.com'; // <-- change this
        $mail->Password   = 'ayafwvtojufvfzrf';   // <-- use Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('lanceaeronm@gmail.com', 'B-tone Events');
        $mail->addAddress($clientEmail, $clientName);

        $mail->isHTML(true);
        $mail->Subject = "Memorandum of Agreement - $eventName";
        $mail->Body    = $moa;
        $mail->AltBody = strip_tags($moa);

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
    }
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
        /* ✅ your CSS styles stay the same */
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
