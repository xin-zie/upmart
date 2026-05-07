<?php
include '../db_connect.php';
session_start();

if (!isset($conn)) {
    die("Database connection failed. Check your variable name in db_connect.php");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $reporter_id = $_SESSION['user_id'];
    $reason = $conn->real_escape_string($_POST['type']);
    $details = $conn->real_escape_string($_POST['details']);    

    // 1. Save the report
    $query = "INSERT INTO reports (product_id, reporter_id, reason, details, status, created_at) 
              VALUES ($product_id, $reporter_id, '$reason', '$details', 'Pending', NOW())";
    
    if ($conn->query($query)) {
        // 2. Create a notification for the Admin (assuming Admin ID is 1)
        $msg = "ALERT: A listing has been reported for $reason.";
        $conn->query("INSERT INTO notifications (user_id, message, notif_type) 
                      VALUES (1, '$msg', 'admin_alert')");
        
        echo "Success";
    } else {
        echo "Error";
    }
    exit();
}