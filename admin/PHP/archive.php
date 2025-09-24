<?php
// archive.php
session_start();
include '../includes/dp_connect.php'; // adjust path if needed

if (!isset($_GET['table'], $_GET['id'])) {
    die('Invalid request.');
}

$table = $_GET['table'];
$id = intval($_GET['id']);
$reason = isset($_GET['reason']) ? trim($_GET['reason']) : null;

// Define allowed tables and primary keys
$allowedTables = [
    'package' => ['table' => 'packages', 'pk' => 'id', 'archived_column' => 'archived_at'],
    'catering_package' => ['table' => 'catering_packages', 'pk' => 'id', 'archived_column' => 'archived_at'],
    'service' => ['table' => 'services', 'pk' => 'services_id', 'archived_column' => 'archived_at'],
    'equipment' => ['table' => 'inventory', 'pk' => 'id', 'archived_column' => 'archived_at'],
    'catering_dish' => ['table' => 'catering_dishes', 'pk' => 'id', 'archived_column' => 'archived_at'],
    'catering_addon' => ['table' => 'catering_addons', 'pk' => 'id', 'archived_column' => 'archived_at'],
];

if (!array_key_exists($table, $allowedTables)) {
    die('Invalid table.');
}

$tableInfo = $allowedTables[$table];
$dbTable = $tableInfo['table'];
$pk = $tableInfo['pk'];
$archivedColumn = $tableInfo['archived_column'];

// Set the archive timestamp
$archivedAt = date('Y-m-d H:i:s');

// Prepare the query
if ($reason) {
    $stmt = $conn->prepare("UPDATE $dbTable SET status = 'archived', $archivedColumn = ?, reason = ? WHERE $pk = ?");
    $stmt->bind_param("ssi", $archivedAt, $reason, $id);
} else {
    $stmt = $conn->prepare("UPDATE $dbTable SET status = 'archived', $archivedColumn = ? WHERE $pk = ?");
    $stmt->bind_param("si", $archivedAt, $id);
}

if ($stmt->execute()) {
    $_SESSION['success'] = ucfirst(str_replace('_', ' ', $table)) . " archived successfully.";
} else {
    $_SESSION['error'] = "Failed to archive " . str_replace('_', ' ', $table) . ".";
}

$stmt->close();
$conn->close();

// Redirect back to admin management
header("Location: admin_management.php");
exit;
