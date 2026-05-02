<?php
session_start();
include '../db_connect.php';

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === 'fetch') {
    $query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $status_class = $row['is_read'] ? '' : 'unread';
            echo "<div class='notif-item $status_class'>
                    <p>{$row['message']}</p>
                    <small>" . date('M d, h:i A', strtotime($row['created_at'])) . "</small>
                  </div>";
        }
    } else {
        echo "<p style='padding:15px; color:#888;'>No new notifications.</p>";
    }
}
?>