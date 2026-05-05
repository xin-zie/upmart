<?php
include '../db_connect.php';
session_start();

if (isset($_POST['product_id'])) {
    $p_id = intval($_POST['product_id']);

    // 1. Update the product status to 'Available'
    $update_query = "UPDATE products SET status = 'Available' WHERE product_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $p_id);

    if ($stmt->execute()) {
        // 2. Optional: Notify the seller
        $res = $conn->query("SELECT seller_id, title FROM products WHERE product_id = $p_id");
        $product = $res->fetch_assoc();
        $msg = "Great news! Your post '" . $product['title'] . "' has been approved.";
        
        $conn->query("INSERT INTO notifications (user_id, message) VALUES ({$product['seller_id']}, '$msg')");
        
        echo "success";
    } else {
        echo "error";
    }
}
?>