<?php
session_start();

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

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['booking_id']) || isset($_GET['booking_id'])) {
    $booking_id = $_POST['booking_id'] ?? $_GET['booking_id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        // Delete booking equipment first (due to foreign key constraints)
        $stmt = $pdo->prepare("DELETE FROM booking_equipment WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        
        // Delete the booking
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ? AND btuser_id = ?");
        $stmt->execute([$booking_id, $user_id]);
        
        $_SESSION['success_message'] = "Booking removed successfully";
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error removing booking: " . $e->getMessage();
    }
}

header("Location: user_cart.php");
exit();
?>