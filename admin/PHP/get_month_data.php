<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check session
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'ADMIN') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Database Configuration
$host = 'localhost';
$dbname = 'btonedatabase';
$username = 'root';
$password = '';

// Database Connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

// Get month and year from POST data
$month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
$year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');

try {
    // Get month details
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total_bookings,
            SUM(CASE WHEN b.status = 'Approved' THEN 1 ELSE 0 END) as confirmed_bookings,
            SUM(CASE WHEN b.status = 'Pending' THEN 1 ELSE 0 END) as pending_bookings,
            GROUP_CONCAT(DISTINCT btevent) as popular_packages
        FROM bookings b
        WHERE MONTH(b.btschedule) = ? AND YEAR(b.btschedule) = ?
        AND b.status NOT IN ('Canceled', 'Completed')
    ");
    $stmt->execute([$month, $year]);
    $monthDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'total_bookings' => $monthDetails['total_bookings'] ?? 0,
        'confirmed_bookings' => $monthDetails['confirmed_bookings'] ?? 0,
        'pending_bookings' => $monthDetails['pending_bookings'] ?? 0,
        'popular_packages' => $monthDetails['popular_packages'] ?? 'N/A',
        'month' => $month,
        'year' => $year
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>