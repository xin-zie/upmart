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

        // 1. Mark user as Banned in the users table
        $conn->query("UPDATE users SET account_status = 'Banned' WHERE user_id = $seller_id");

        // 2. Hide the product from the shop (set to 'Deleted' or 'Hidden')
        $conn->query("UPDATE products SET status = 'Deleted' WHERE product_id = $product_id");

        // 3. REVISION: Keep the report but mark it as 'banned' so it stays in history
        $conn->query("UPDATE reports SET status = 'Resolved', decision = 'banned' WHERE report_id = $report_id");

        $info_stmt = $conn->prepare("SELECT u.up_email, u.full_name, r.reason 
                                    FROM users u 
                                    JOIN reports r ON r.reported_user_id = u.user_id 
                                    WHERE u.user_id = ? AND r.report_id = ?");
        // Note: ensure your reports table has 'reported_user_id' or use p.seller_id logic
        
        // Simple version using the variables you already have:
        $seller_res = $conn->query("SELECT up_email, full_name FROM users WHERE user_id = $seller_id");
        $report_res = $conn->query("SELECT reason FROM reports WHERE report_id = $report_id");
        
        $seller = $seller_res->fetch_assoc();
        $report = $report_res->fetch_assoc();

        if ($seller) {
            $to = $seller['up_email'];
            $subject = "Account Banned - UPMart Community Guidelines";
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: admin@upmart.com" . "\r\n";

            $message = "
            <html>
            <head><title>Account Status Update</title></head>
            <body>
                <h3>Account Suspension Notice</h3>
                <p>Dear " . htmlspecialchars($seller['full_name']) . ",</p>
                <p>Your account has been <b>permanently banned</b> from UPMart.</p>
                <p><b>Reason:</b> " . htmlspecialchars($report['reason']) . "</p>
                <p>As a result of this action, you can no longer log in, list products, or participate in the marketplace. Any active listings have been removed.</p>
                <p>Regards,<br>UPMart Admin Team</p>
            </body>
            </html>";

            mail($to, $subject, $message, $headers);
        }

        echo json_encode(['success' => true, 'message' => 'Seller banned and notified via email.']);
        exit();
    }

    if ($action === 'dismiss_report') {
        $report_id = intval($_POST['report_id']);

        // Set status to 'Resolved' and decision to 'dismissed'
        // This removes it from the 'Pending' list used in your main query
        $sql = "UPDATE reports SET status = 'Resolved', decision = 'dismissed' WHERE report_id = $report_id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Report dismissed and resolved.']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        exit();
    }
}
