<?php
session_start();
include_once "../includes/loginSession.php";
include_once "../includes/userData.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $dbname = 'btonedatabase';
    $port = '3308';

    $conn = new mysqli($host, $user, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Get cart items
        $sql = "SELECT * FROM customer_inquiries WHERE status = 'In Cart' AND customer_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $fullname);
        $stmt->execute();
        $cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($cart_items as $item) {
            // Insert into bookings table
            $insert_booking = "INSERT INTO bookings (
                btuser_id, 
                btaddress, 
                btevent, 
                btschedule, 
                EventDuration,
                btattendees, 
                btservices, 
                btmessage,
                created_at,
                status,
                payment_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Pending', 'unpaid')";

            // Calculate EventDuration (24 hours from btschedule)
            $schedule = $item['event_date'] . ' ' . $item['event_time'];
            $duration = date('Y-m-d H:i:s', strtotime($schedule . ' +24 hours'));

            $stmt = $conn->prepare($insert_booking);
            $stmt->bind_param(
                "isssssss",
                $_SESSION['bt_user_id'],
                $item['location_type'],
                $item['event_package'],
                $schedule,
                $duration,
                $item['total_cost'], // Using total_cost as attendees for now
                $item['additional_details'],
                $item['additional_details']
            );
            $stmt->execute();

            $booking_id = $conn->insert_id;

            // Move equipment bookings if any
            $sql = "UPDATE booking_equipment SET booking_id = ? WHERE booking_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $booking_id, $item['id']);
            $stmt->execute();

            // Update status in customer_inquiries
            $update_sql = "UPDATE customer_inquiries SET status = 'Pending' WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("i", $item['id']);
            $stmt->execute();
        }

        $conn->commit();
        header("Location: booking_confirmation.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 20px;
        }
        h1 {
            color: #333;
        }
    </style>
</head>
<body>
    <h1>Processing your order...</h1>
    <p>Please wait while we process your booking...</p>
</body>
</html>