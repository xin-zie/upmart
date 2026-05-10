<?php
session_start();
include '../db_connect.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id  = intval($_POST['product_id']);
    $reporter_id = $_SESSION['user_id'];    
    $reason      = $_POST['type'];
    $details     = $_POST['details'];

    // We leave reported_user_id as NULL for now because we are focusing on the product
    $stmt = $conn->prepare("INSERT INTO reports (reporter_id, product_id, reason, details, status) VALUES (?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("iiss", $reporter_id, $product_id, $reason, $details);

    if ($stmt->execute()) {
        echo "Success";
    } else {
        echo "Database Error: " . $stmt->error;
    }
    exit();
}