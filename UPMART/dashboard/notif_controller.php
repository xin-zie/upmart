<?php
session_start();
include '../db_connect.php';

// Security check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    exit('Unauthorized');
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'] ?? 'user';
$action  = $_GET['action'] ?? '';

if ($action === 'fetch') {
    // 1. CLEANUP: Delete notifications older than 12 hours for all users   
    $conn->query("DELETE n1 FROM notifications n1
                INNER JOIN notifications n2 
                WHERE n1.user_id = n2.user_id 
                    AND n1.notif_type = n2.notif_type 
                    AND n1.target_id = n2.target_id
                    AND n1.notif_id < n2.notif_id
                    AND n1.notif_type IN ('approval', 'warning')");

    // 2. ADMIN VIEW: If the logged-in user is an admin, show system tasks
    if ($role === 'admin') {
        $output = '';

        // 1. Check for Pending Posts
        $pending_posts_res = $conn->query("SELECT COUNT(*) as total FROM products WHERE approval_status = 'Pending'");
        $pending_posts = $pending_posts_res->fetch_assoc()['total'] ?? 0;

        // 2. Check for Pending Reports
        $pending_reports_res = $conn->query("SELECT COUNT(*) as total FROM reports WHERE status = 'Pending'");
        $pending_reports = $pending_reports_res->fetch_assoc()['total'] ?? 0;

        // Build the output
        if ($pending_posts > 0) {
            $output .= "
            <div class='notif-item unread admin-task' onclick=\"window.location.href='admin_post.php'\">
                <div class='notif-icon'>📝</div>
                <div class='notif-content'>
                    <p><b>Approvals:</b> $pending_posts posts are waiting for review.</p>
                    <small>Action Required</small>
                </div>
            </div>";
        }

        if ($pending_reports > 0) {
            $output .= "
            <div class='notif-item unread admin-report' onclick=\"window.location.href='admin_report.php'\">
                <div class='notif-icon'>🚩</div>
                <div class='notif-content'>
                    <p><b>Reports:</b> $pending_reports items have been flagged.</p>
                    <small>Security Alert</small>
                </div>
            </div>";
        }

        // Final Display
        if (empty($output)) {
            echo "<div class='notif-empty'>✅ No pending admin tasks.</div>";
        } else {
            echo "<div class='admin-notif-header' style='margin-bottom: 10px; font-weight: bold;'>Pending Tasks</div>" . $output;
        }
    } else {
        // 3. USER VIEW: Show messages, order requests, and approval updates
        $query = "SELECT n.*, u.full_name as sender_name,
                        MAX(n.created_at) as latest_time
                FROM notifications n 
                LEFT JOIN users u ON n.sender_id = u.user_id 
                WHERE n.user_id = ? 
                GROUP BY n.notif_type, n.target_id, n.sender_id
                ORDER BY latest_time DESC 
                LIMIT 10";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $status_class = $row['is_read'] ? '' : 'unread';
                $sender = $row['sender_name'] ?? 'System Admin';
                $sender_id = $row['sender_id'] ?? 0;
                $type = $row['notif_type'] ?? 'general';

                $message_text = $row['message'];
                $item_label = "Item";

                if (preg_match("/'([^']+)'/", $message_text, $matches)) {
                    $item_label = $matches[1];
                }

                echo "
                <div class='notif-item $status_class' 
                    onclick='handleNotifClick(\"$type\", \"$sender_id\", \"$sender\", \"$item_label\")'
                    style='cursor:pointer;'>
                    <p>$message_text</p>
                    <small>" . date('g:i A', strtotime($row['created_at'])) . "</small>
                </div>";
            }
        } else {
            echo "<p style='padding:15px; color:#888;'>No new updates.</p>";
        }
    }
}
