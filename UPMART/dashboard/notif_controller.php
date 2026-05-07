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
    $cleanup_query = "DELETE FROM notifications 
                      WHERE created_at < DATE_SUB(NOW(), INTERVAL 12 HOUR)";
    $conn->query($cleanup_query);

    // 2. ADMIN VIEW: If the logged-in user is an admin, show system tasks
    if ($role === 'admin') {
        $output = '';

        // Fetch count of posts needing approval
        $pending_posts_res = $conn->query("SELECT COUNT(*) as total FROM products WHERE approval_status = 'Pending'");
        $pending_posts = $pending_posts_res->fetch_assoc()['total'] ?? 0;

        // Fetch count of pending reports
        $pending_reports_res = $conn->query("SELECT COUNT(*) as total FROM reports WHERE status = 'Pending'");
        $pending_reports = $pending_reports_res->fetch_assoc()['total'] ?? 0;

        if ($pending_posts > 0) {
            $output .= "
            <div class='notif-item unread' onclick=\"window.location.href='admin_post.php'\" style='cursor:pointer;'>
                <p><b>Pending Approvals:</b> You have $pending_posts new posts to review.</p>
                <small>System Update</small>
            </div>";
        }

        if ($pending_reports > 0) {
            $output .= "
            <div class='notif-item unread' onclick=\"window.location.href='admin_report.php'\" style='cursor:pointer;'>
                <p><b>New Reports:</b> You have $pending_reports items flagged by users.</p>
                <small>Security Update</small>
            </div>";
        }

        if (empty($output)) {
            echo "<p style='padding:15px; color:#888;'>No pending admin tasks.</p>";
        } else {
            echo $output;
        }

    } else {
        // 3. USER VIEW: Show messages, order requests, and approval updates
        $query = "SELECT n.*, u.full_name as sender_name 
                  FROM notifications n 
                  LEFT JOIN users u ON n.sender_id = u.user_id 
                  WHERE n.user_id = ? 
                  ORDER BY n.created_at DESC LIMIT 10";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $status_class = $row['is_read'] ? '' : 'unread';
                $sender = $row['sender_name'] ?? 'System Admin';
                $sender_id = $row['sender_id'] ?? 0;
                
                echo "
                <div class='notif-item $status_class' 
                    onclick='handleNotifClick(\"wish_match\", \"{$row['sender_id']}\", \"$sender\", \"Wishlist Match\")'
                    style='cursor:pointer;'>
                    <p>{$row['message']}</p>
                    <small>" . date('g:i A', strtotime($row['created_at'])) . "</small>
                </div>";
            }
        } else {
            echo "<p style='padding:15px; color:#888;'>No new updates.</p>";
        }
    }
}
?>