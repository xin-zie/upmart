<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access.']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $report_id = intval($_POST['report_id'] ?? 0);
    $product_id = intval($_POST['product_id'] ?? 0);
    $seller_id = intval($_POST['seller_id'] ?? 0);
    $admin_id = $_SESSION['user_id'] ?? 0; // Better to use the actual logged-in admin ID

    // --- FORCE BAN LOGIC ---
    if ($action === 'ban_user') {
        // 1. Ban the user
        $conn->query("UPDATE users SET account_status = 'Banned' WHERE user_id = $seller_id");
        
        // 2. Handle the product (Only if it exists)
        if ($product_id > 0) {
            $conn->query("UPDATE products SET status = 'Deleted', approval_status = 'Rejected' WHERE product_id = $product_id");
        }

        // 3. Resolve the report    
        $conn->query("UPDATE reports SET status = 'Resolved', decision = 'banned' WHERE report_id = $report_id");

        // 4. Fetch reason for the notification
        $report_res = $conn->query("SELECT reason FROM reports WHERE report_id = $report_id");
        $report = $report_res->fetch_assoc();

        // 5. Internal Notification
        $banMsg = "Your account has been permanently banned. Reason: " . ($report['reason'] ?? 'Violation of community guidelines.');
        $admin_id = $_SESSION['user_id'] ?? 1; 

        $stmt = $conn->prepare("INSERT INTO notifications (user_id, sender_id, message, notif_type, target_id, is_read) VALUES (?, ?, ?, 'ban', ?, 0)");
        
        $stmt->bind_param("iisi", $seller_id, $admin_id, $banMsg, $product_id);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Seller banned and notified via dashboard.']);
        exit();
    }

   // --- WARNING / STRIKE LOGIC ---
    if ($action === 'warning') {
        if ($seller_id === 0) {
            die(json_encode(['success' => false, 'message' => 'Invalid Seller ID']));
        }

        // 1. Fetch Product Title and Report Reason first
        // We use a LEFT JOIN so we still get the reason even if the product was already deleted
        $details_stmt = $conn->prepare("
            SELECT r.reason, p.title 
            FROM reports r 
            LEFT JOIN products p ON r.product_id = p.product_id 
            WHERE r.report_id = ?
        ");
        $details_stmt->bind_param("i", $report_id);
        $details_stmt->execute();
        $details = $details_stmt->get_result()->fetch_assoc();

        $product_title = !empty($details['title']) ? "'" . $details['title'] . "'" : "your listing";
        $report_reason = $details['reason'] ?? 'Violation of community guidelines';

        // 2. Increment strike
        $conn->query("UPDATE users SET warning_count = warning_count + 1 WHERE user_id = $seller_id");

        // 3. Fetch new count
        $user_query = $conn->query("SELECT warning_count FROM users WHERE user_id = $seller_id");
        $user = $user_query->fetch_assoc();
        $new_count = $user['warning_count'];

        // 4. Prepare Notification Message & Update Tables
        if ($new_count >= 3) {
            $conn->query("UPDATE users SET account_status = 'Banned' WHERE user_id = $seller_id");
            $conn->query("UPDATE reports SET status = 'Resolved', decision = 'banned' WHERE report_id = $report_id");
            
            $warnMsg = "<b>Account Banned:</b> You reached warning 3/3 due to your post $product_title (Reason: $report_reason). Your account is now permanently banned.";
            $type = 'ban';
        } else {
            $conn->query("UPDATE reports SET status = 'Resolved', decision = 'warning' WHERE report_id = $report_id");
            
            $warnMsg = "<b>Warning:</b> You received warning #$new_count/3 regarding $product_title. Reason: $report_reason. Continued violations will result in a ban.";
            $type = 'warning';
        }

        // 5. Save to Notifications Table (Using Prepared Statement)
        // admin_id should be coming from your session: $admin_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, sender_id, message, notif_type, related_id, target_id, is_read) VALUES (?, ?, ?, ?, ?, ?, 0)");
        
        // We use $product_id for related_id and target_id so the user can see what triggered it
        $stmt->bind_param("iissii", $seller_id, $admin_id, $warnMsg, $type, $product_id, $product_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => ($new_count >= 3) ? 'User has been banned (3/3 strikes).' : 'Warning #'. $new_count .' issued.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Notification Error: ' . $stmt->error]);
        }
        exit();
    }
    
    // --- DISMISS LOGIC ---
    if ($action === 'dismiss_report') {
        $conn->query("UPDATE reports SET status = 'Resolved', decision = 'dismissed' WHERE report_id = $report_id");
        echo json_encode(['success' => true, 'message' => 'Report dismissed.']);
        exit();
    }
}