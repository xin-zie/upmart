<?php
session_start();
include '../db_connect.php';

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// Updated logic for notif_controller.php
if ($action === 'fetch') {
    // Include full_name to display "Name has a message..."
    $query = "SELECT n.*, u.full_name as sender_name 
              FROM notifications n 
              JOIN users u ON n.sender_id = u.user_id 
              WHERE n.user_id = ? 
              ORDER BY n.created_at DESC LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $status_class = $row['is_read'] ? '' : 'unread';
            // Injecting handleNotifClick with type, target_id, and name
            echo "<div class='notif-item $status_class' 
                       onclick='handleNotifClick(\"{$row['notif_type']}\", \"{$row['target_id']}\", \"{$row['sender_name']}\")' 
                       style='cursor:pointer;'>
                    <p>{$row['message']}</p>
                    <small>" . date('M d, h:i A', strtotime($row['created_at'])) . "</small>
                  </div>";
        }
    } else {
        echo "<p style='padding:15px; color:#888;'>No new notifications.</p>";
    }
}
?>