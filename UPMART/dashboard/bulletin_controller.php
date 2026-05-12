<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../db_connect.php'; 

// Turn on error reporting so we can see what's wrong in the Network Tab
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the action from either GET (for fetch) or POST (for post)
$action = $_REQUEST['action'] ?? '';

// --- ACTION 1: POST A MESSAGE ---
if ($action === 'post') {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $message = trim($_POST['message'] ?? '');
        
        if (!empty($message)) {
            // Using $conn from your db_connect.php
            $stmt = $conn->prepare("INSERT INTO bulletin_posts (user_id, message) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $message);
            
            if ($stmt->execute()) {
                echo "Success";
            } else {
                echo "SQL Error: " . $stmt->error;
            }
        } else {
            echo "Error: Message was empty";
        }
    } else {
        echo "Error: No user session found. Please log in again.";
    }
    exit(); 
}

// --- ACTION 2: FETCH MESSAGES ---
if ($action === 'fetch') {
    $query = "SELECT b.message, b.created_at, u.full_name 
              FROM bulletin_posts b 
              JOIN users u ON b.user_id = u.user_id 
              ORDER BY b.created_at DESC LIMIT 10";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<div class="post" style="border-left: 4px solid maroon; margin-bottom: 10px; padding-left: 10px; background: rgba(0,0,0,0.03); border-radius: 5px; padding: 8px;">';
            echo '<strong>' . htmlspecialchars($row['full_name']) . ':</strong> ';
            echo htmlspecialchars($row['message']);
            echo '<br><small style="color: #999; font-size: 0.7rem;">' . date('g:i A', strtotime($row['created_at'])) . '</small>';
            echo '</div>';
        }
    } else {
        echo '<div class="post">Welcome to the UPMart Bulletin! Keep it friendly.</div>';
    }
    exit();
}

// If no valid action was provided
echo "Invalid action provided.";
?>