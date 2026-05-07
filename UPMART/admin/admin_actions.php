<?php
session_start();
include '../db_connect.php';

// SECURITY: Ensure only the admin can run these actions
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access.']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $report_id = intval($_POST['report_id'] ?? 0);
    $product_id = intval($_POST['product_id'] ?? 0);
    $seller_id = intval($_POST['seller_id'] ?? 0);

    if ($action === 'ban_user') {
        $seller_id = intval($_POST['seller_id']);
        $product_id = intval($_POST['product_id']);
        $report_id = intval($_POST['report_id']);

        // 1. Delete the offensive product (Keep the marketplace clean)
        $conn->query("DELETE FROM products WHERE product_id = $product_id");

        // 2. Update the User Status to 'Banned' instead of deleting
        $conn->query("UPDATE users SET account_status = 'Banned' WHERE user_id = $seller_id");

        // 3. Remove the report so it's cleared from the Admin Dashboard
        $conn->query("DELETE FROM reports WHERE report_id = $report_id");

        echo json_encode(['success' => true, 'message' => 'Seller has been banned and the listing removed.']);
    }

   if ($action === 'dismiss_report') {
        $report_id = intval($_POST['report_id']);

        // Change DELETE to UPDATE
        $stmt = $conn->prepare("UPDATE reports SET status = 'Resolved' WHERE report_id = ?");
        $stmt->bind_param("i", $report_id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Report marked as Resolved.'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Error updating status: ' . $conn->error
            ]);
        }
        exit();
    }
}

