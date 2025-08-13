<?php
require_once "../includes/dbh.inc.php";

$message = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    $userId = filter_input(INPUT_POST, 'btuser_id', FILTER_SANITIZE_NUMBER_INT);
    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_SANITIZE_NUMBER_INT);
    $paymentAmount = filter_input(INPUT_POST, 'paymentAmount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $ref = $_POST['ref'] ?? '';
    if (strlen($ref) < 13 || strlen($ref) > 15) {
        $message = "Reference number must be between 13 and 15 characters.";
        echo $message;
    }

    if (!empty($userId) && !empty($bookingId) && !empty($paymentAmount) && !empty($ref)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO sales (btuser_id, booking_id, GcashReferenceNo, TotalAmount) VALUES (:userId, :bookingId, :ref, :paymentAmount)");
            $stmt->bindParam(':userId', $userId);
            $stmt->bindParam(':bookingId', $bookingId);
            $stmt->bindParam(':paymentAmount', $paymentAmount);
            $stmt->bindParam(':ref', $ref);
            $stmt->execute();

            header("Location: ../PHP/index.php?success=Payment successful");
            exit;
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    } else {
        $message = "All fields are required.";
    }


}