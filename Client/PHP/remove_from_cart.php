<?php
session_start();
include_once "../includes/loginSession.php";
include_once "../includes/userData.php";

// Set JSON content type header
header('Content-Type: application/json');

// Database Connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'btonedatabase';
$port = '3308';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

// Get customer name from session
$customer_name = isset($_SESSION['login']) ? $_SESSION['login'] :
    (isset($_SESSION['fullname']) ? $_SESSION['fullname'] : '');

$data = json_decode(file_get_contents('php://input'), true);
$item_id = isset($data['item_id']) ? (int) $data['item_id'] : 0;

if ($item_id > 0 && !empty($customer_name)) {
    // Start transaction
    $conn->begin_transaction();

    try {
        // First delete related equipment entries
        $sql = "DELETE FROM booking_equipment WHERE booking_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $item_id);
        $stmt->execute();

        // Then delete the cart item
        $sql = "DELETE FROM customer_inquiries WHERE id = ? AND status = 'In Cart' AND customer_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $item_id, $customer_name);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['status' => 'success']);
        } else {
            throw new Exception('No item found to delete');
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid item ID or user not logged in']);
}

$conn->close();
?>