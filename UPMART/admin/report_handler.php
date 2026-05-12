<?php
include '../db_connect.php';
session_start();

if (!isset($conn)) {
    die("Database connection failed.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $reporter_id = $_SESSION['user_id'];
    $reason = $conn->real_escape_string($_POST['type']);
    $details = $conn->real_escape_string($_POST['details']);    

    // 1. Fetch the Product Title and First Image for the Admin Notification
    $prod_info_query = "SELECT p.title, 
                       (SELECT image_path FROM media WHERE product_id = p.product_id LIMIT 1) as main_img 
                       FROM products p WHERE p.product_id = $product_id";
    $info_res = $conn->query($prod_info_query);
    $prod_data = $info_res->fetch_assoc();
    
    $prod_title = $prod_data['title'] ?? 'Unknown Item';
    $main_img = $prod_data['main_img'] ?? 'default.png';

    // 2. Save the report
    $query = "INSERT INTO reports (product_id, reporter_id, reason, details, status, decision, created_at) 
              VALUES ($product_id, $reporter_id, '$reason', '$details', 'Pending', 'none', NOW())";
    
    if ($conn->query($query)) {
        // 3. Create a notification for the Admin
        // We include the product title and image path in the message for the notification drawer
        $admin_msg = "<b>REPORT:</b> $prod_title reported for $reason.";
        
        // Use the 'main_img' as the thumbnail for the notification
        $notif_query = "INSERT INTO notifications (user_id, message, notif_type, related_id) 
                        VALUES (1, '$admin_msg', 'admin_alert', $product_id)";
        
        $conn->query($notif_query);
        
        echo "Success";
    } else {
        echo "Error";
    }
    exit();
}