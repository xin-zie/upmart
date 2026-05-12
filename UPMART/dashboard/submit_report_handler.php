<?php
// 1. Error Reporting (Keep this ON during testing to see errors in F12 Network tab)
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');   
include '../db_connect.php';
session_start();

// Ensure the user is actually logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit();
}

$reporter_id = $_SESSION['user_id'];
$reported_name = trim($_POST['reported_name']); 
$reason = $_POST['reason'] ?? '';
$details = $_POST['details'] ?? '';

if (empty($reported_name)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a user name.']);
    exit();
}

// 1. Find the user_id that matches the full_name
$stmt = $conn->prepare("SELECT user_id FROM users WHERE full_name = ? LIMIT 1");
$stmt->bind_param("s", $reported_name);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    $target_id = $user['user_id'];
    
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $type = ($product_id > 0) ? 'product' : 'user';
    $insert = $conn->prepare("INSERT INTO reports (reporter_id, reported_user_id, report_type, product_id, reason, details) VALUES (?, ?, ?, ?, ?, ?)");
    $insert->bind_param("iisiss", $reporter_id, $target_id, $type, $product_id, $reason, $details);
    
    if ($insert->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $insert->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'User "' . $reported_name . '" not found.']);
}
?>  