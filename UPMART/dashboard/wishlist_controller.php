<?php
session_start();
include '../db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$action = $_REQUEST['action'] ?? '';

// --- ACTION: ADD A NEW WISH ---
if ($action === 'add') {
    if (!isset($_SESSION['user_id'])) {
        die("Error: Login required.");
    }

    $user_id = $_SESSION['user_id'];
    $item_name = trim($_POST['item_name'] ?? '');
    $category = $_POST['category'] ?? 'Other';

    if (!empty($item_name)) {
        $stmt = $conn->prepare("INSERT INTO wishlist (user_id, item_name, category) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $item_name, $category);
        
        if ($stmt->execute()) {
            echo "Success";
        } else {
            echo "SQL Error: " . $stmt->error;
        }
    } else {
        echo "Error: Item name cannot be empty.";
    }
    exit();
}

// --- ACTION: FETCH WISHES ---
if ($action === 'fetch') {
    $query = "SELECT w.*, u.full_name FROM wishlist w 
              JOIN users u ON w.user_id = u.user_id 
              ORDER BY w.created_at DESC LIMIT 10";
    
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $is_mine = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['user_id']);
            $card_style = $is_mine ? 'style="border: 1px solid maroon; background: #fff9f9;"' : '';

            echo '
            <div class="wish-card" ' . $card_style . '>
                <div class="wish-info">
                    <span class="category-tag">' . htmlspecialchars($row['category']) . '</span>
                    <h4>' . htmlspecialchars($row['item_name']) . '</h4>
                    <p>Requested by: <strong>' . ($is_mine ? "Me" : htmlspecialchars($row['full_name'])) . '</strong></p>
                </div>
                ' . (!$is_mine ? '<button class="match-btn" onclick="handleMatch(' . $row['wish_id'] . ')">I have this!</button>' : '<small>Your Wish</small>') . '
            </div>';
        }
    } else {
        echo '<p style="padding: 20px; color: #888;">No wishlist matches yet.</p>';
    }
    exit();
}

// --- ACTION: MATCH A WISH (COMBINED & SECURE) ---
if ($action === 'match_wish') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Login required']);
        exit();
    }

    $wish_id = intval($_POST['wish_id']);
    $seller_id = $_SESSION['user_id'];
    $seller_name = $_SESSION['full_name'];

    // 1. Fetch wish details to find the owner/requester
    $query = "SELECT w.user_id, w.item_name, u.full_name 
              FROM wishlist w 
              JOIN users u ON w.user_id = u.user_id 
              WHERE w.wish_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $wish_id);
    $stmt->execute();
    $wish = $stmt->get_result()->fetch_assoc();

    if ($wish) {
        $requester_id = $wish['user_id'];
        $requester_name = $wish['full_name'];
        $item_name = $wish['item_name'];
        
        // 2. Insert notification for the Requester
        $notif_msg = "<b>$seller_name</b> has the item you are looking for: '$item_name'!";
        
        $notif_sql = "INSERT INTO notifications (user_id, sender_id, message, is_read) 
                      VALUES (?, ?, ?, 0)";
        $notif_stmt = $conn->prepare($notif_sql);
        $notif_stmt->bind_param("iis", $requester_id, $seller_id, $notif_msg);
        
        if ($notif_stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'requester_id' => $requester_id, 
                'requester_name' => $requester_name,
                'item_name' => $item_name
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Wish not found.']);
    }
    exit();
}
?>