<?php
require_once "../includes/dbh.inc.php";

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = filter_input(INPUT_POST, 'btuser_id', FILTER_SANITIZE_NUMBER_INT);
    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_SANITIZE_NUMBER_INT);
    $paymentAmount = filter_input(INPUT_POST, 'paymentAmount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $ref = filter_input(INPUT_POST, 'ref', FILTER_SANITIZE_STRING);

    if (strlen($ref) < 13 || strlen($ref) > 15) {
        $message = "Reference number must be between 13 and 15 characters.";
        echo $message;
        exit;
    }

    if (!empty($userId) && !empty($bookingId) && !empty($paymentAmount) && !empty($ref)) {
        try {
            $pdo->beginTransaction();

            // Insert payment into sales table
            $stmt = $pdo->prepare("INSERT INTO sales (btuser_id, booking_id, GcashReferenceNo, TotalAmount, AmountPaid, Status) 
                                   VALUES (:userId, :bookingId, :ref, :totalAmount, :amountPaid, 1)");
            $stmt->execute([
                ':userId' => $userId,
                ':bookingId' => $bookingId,
                ':ref' => $ref,
                ':totalAmount' => $paymentAmount,
                ':amountPaid' => $paymentAmount
            ]);

            // Calculate total paid for this booking
            $stmt = $pdo->prepare("SELECT SUM(AmountPaid) as total_paid, TotalAmount 
                                   FROM sales 
                                   WHERE booking_id = :bookingId");
            $stmt->execute([':bookingId' => $bookingId]);
            $paymentData = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalPaid = floatval($paymentData['total_paid']);
            $totalExpected = floatval($paymentData['TotalAmount']);

            // Determine payment_status and booking status
            $paymentStatus = 'unpaid';
            $bookingStatus = 'Pending';
            if ($totalPaid >= $totalExpected) {
                $paymentStatus = 'paid';
                $bookingStatus = 'Approved';
            } elseif ($totalPaid > 0) {
                $paymentStatus = 'partial';
                $bookingStatus = 'Pending';
            }

            // Update bookings table
            $stmt = $pdo->prepare("UPDATE bookings 
                                   SET payment_status = :paymentStatus, status = :bookingStatus 
                                   WHERE id = :bookingId");
            $stmt->execute([
                ':paymentStatus' => $paymentStatus,
                ':bookingStatus' => $bookingStatus,
                ':bookingId' => $bookingId
            ]);

            $pdo->commit();
            header("Location: ../PHP/index.php?success=Payment successful");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "Error: " . $e->getMessage();
            echo $message;
        }
    } else {
        $message = "All fields are required.";
        echo $message;
    }
}