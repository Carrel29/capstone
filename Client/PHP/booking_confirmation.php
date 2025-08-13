<?php
session_start();
include_once "../includes/loginSession.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            text-align: center;
        }

        .confirmation-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .success-icon {
            color: #28a745;
            font-size: 48px;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }

        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="success-icon">✓</div>
        <h1>Booking Confirmed!</h1>
        <p>Your bookings have been successfully processed.</p>
        <p>You can view your bookings in your account dashboard.</p>
        <a href="index.php" class="btn">Return to Home</a>
    </div>
</body>
</html>