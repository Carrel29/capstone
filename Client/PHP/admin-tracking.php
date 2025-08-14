<?php
// admin-tracking.php
// Admin/employee interface for tracking and updating booking statuses
session_start();
include_once "../includes/dbh.inc.php";
include_once "../includes/userData.php"; // Contains session variables: $fullname, $email, $privilege, $user_id
include_once "../includes/allData.php";

// Check if user is logged in and has admin or employee privilege
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($privilege, ['admin', 'employee'])) {
    header('Location: login.php');
    exit();
}

// Handle manual status update
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_status'])) {
    $booking_id = intval($_POST['booking_id']);
    $new_status = $_POST['new_status'];

    if (in_array($new_status, ['Pending', 'Approved', 'Canceled'])) {
        try {
            $stmt = $pdo->prepare("UPDATE bookings SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $new_status, ':id' => $booking_id]);
            $message = "Status updated successfully.";
        } catch (PDOException $e) {
            $message = "Error updating status: " . $e->getMessage();
        }
    } else {
        $message = "Invalid status selected.";
    }
}

// Fetch all bookings with user info and payment data
$allBookings = $pdo->query("
    SELECT b.*, u.bt_first_name, u.bt_last_name, 
           COALESCE(SUM(s.AmountPaid), 0) as total_paid, 
           COALESCE(s.TotalAmount, 0) as total_expected
    FROM bookings b
    JOIN btonedatabase_users u ON b.btuser_id = u.btuser_id
    LEFT JOIN sales s ON b.id = s.booking_id
    GROUP BY b.id
    ORDER BY b.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/style.css" />
    <title>Booking Tracking - BTONE Admin</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .status-form {
            display: inline-flex;
            gap: 5px;
        }
        .btn-update {
            padding: 5px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-update:hover {
            background-color: #0056b3;
        }
        .message {
            text-align: center;
            color: green;
            margin: 10px 0;
        }
        .error {
            text-align: center;
            color: red;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <header>
        <h1 class="company-name">BTONE Admin</h1>
        <nav class="nav-bar">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="../includes/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="h-100 gap-20">
        <div>
            <h1 class="text-center">Booking Tracking</h1>
            <?php if ($message): ?>
                <p class="<?php echo strpos($message, 'Error') === false ? 'message' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php endif; ?>
        </div>
        <div class="card w-100">
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>User</th>
                        <th>Event</th>
                        <th>Schedule</th>
                        <th>Duration</th>
                        <th>Attendees</th>
                        <th>Services</th>
                        <th>Address</th>
                        <th>Message</th>
                        <th>Created At</th>
                        <th>Payment Status</th>
                        <th>Total Paid</th>
                        <th>Total Expected</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allBookings)): ?>
                        <tr>
                            <td colspan="15" class="text-center">No bookings found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allBookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['id']); ?></td>
                                <td><?php echo htmlspecialchars($booking['bt_first_name'] . ' ' . $booking['bt_last_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['btevent']); ?></td>
                                <td><?php echo htmlspecialchars($booking['btschedule']); ?></td>
                                <td><?php echo htmlspecialchars($booking['EventDuration']); ?></td>
                                <td><?php echo htmlspecialchars($booking['btattendees']); ?></td>
                                <td><?php echo htmlspecialchars($booking['btservices']); ?></td>
                                <td><?php echo htmlspecialchars($booking['btaddress']); ?></td>
                                <td><?php echo htmlspecialchars($booking['btmessage']); ?></td>
                                <td><?php echo htmlspecialchars($booking['created_at']); ?></td>
                                <td><?php echo htmlspecialchars($booking['payment_status']); ?></td>
                                <td>₱<?php echo number_format($booking['total_paid'], 2); ?></td>
                                <td>₱<?php echo number_format($booking['total_expected'], 2); ?></td>
                                <td><?php echo htmlspecialchars($booking['status']); ?></td>
                                <td>
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <select name="new_status">
                                            <option value="Pending" <?php echo $booking['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Approved" <?php echo $booking['status'] === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="Canceled" <?php echo $booking['status'] === 'Canceled' ? 'selected' : ''; ?>>Canceled</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn-update">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>