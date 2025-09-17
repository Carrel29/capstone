<?php
session_start();
header('Content-Type: application/json');

// Check admin session
if (
    !isset($_SESSION['user_logged_in']) || 
    !isset($_SESSION['bt_user_id']) ||
    $_SESSION['role'] !== 'ADMIN') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['status'])) {
    $host = 'localhost';
    $db   = 'btonedatabase';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    $allowed_statuses = ['Pending', 'Approved', 'Canceled', 'Completed'];
    if (!in_array($_POST['status'], $allowed_statuses)) {
        echo json_encode(['success' => false, 'error' => 'Invalid status value']);
        exit;
    }
    if (!is_numeric($_POST['id'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid booking ID']);
        exit;
    }

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // THIS IS WHERE YOU ADD YOUR CODE:
        $stmt = $pdo->prepare("UPDATE bookings SET status=? WHERE id=?");
        $success = $stmt->execute([$_POST['status'], $_POST['id']]);

        if ($success && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database update failed or no rows changed']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}
?>