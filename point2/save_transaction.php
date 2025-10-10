<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli("localhost", "root", "", "cafe_pos");
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => "Connection failed: " . $conn->connect_error]));
}

// Log incoming data for debugging
$rawData = file_get_contents('php://input');
error_log("Received data: " . $rawData);

$data = json_decode($rawData, true);

if ($data) {
    try {
        // Validate data
        if (!isset($data['paymentMethod']) || !isset($data['total']) || !isset($data['cart'])) {
            throw new Exception("Missing required fields");
        }

        // Convert values to appropriate types
        $payment_code = strval($data['paymentMethod']); // This is the specific code like 'CASH', 'GCASH', 'MAYA'
        $total = floatval($data['total']);
        
        // Handle cash payment differently
        if ($payment_code === 'CASH') {
            $cashReceived = isset($data['cashReceived']) ? floatval($data['cashReceived']) : 0.00;
            $change = isset($data['change']) ? floatval($data['change']) : 0.00;
            $referenceNumber = null;
        } else {
            $cashReceived = 0.00;
            $change = 0.00;
            $referenceNumber = isset($data['referenceNumber']) ? strval($data['referenceNumber']) : '';
        }

        $cartJson = json_encode($data['cart']);
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Carrel29';

        // Prepare SQL - store only in payment_code column
        $sql = "INSERT INTO transactions (
            transaction_date,
            payment_code,
            total_amount,
            cash_received,
            cash_change,
            reference_number,
            cart_items,
            username,
            created_at
        ) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // Bind parameters
        $stmt->bind_param(
            "sdddsss",
            $payment_code,    // payment_code
            $total,
            $cashReceived,
            $change,
            $referenceNumber,
            $cartJson,
            $username
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Transaction saved successfully',
            'transaction_id' => $conn->insert_id
        ]);

    } catch (Exception $e) {
        error_log("Transaction error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'data_received' => $data
        ]);
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        $conn->close();
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid data received',
        'raw_data' => $rawData
    ]);
}