<?php
session_start();
include '../db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$action = $_REQUEST['action'] ?? '';

// --- ACTION: ADD A NEW WISH ---
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $user_id = $_SESSION['user_id'];
    
    // Get the name from the <select>
    $category_name = $_POST['category'];

    // --- CATEGORY LOOKUP MAP ---
    $category_map = [
        "Dorm Essentials" => 1,
        "Arki Mats"       => 2,
        "Lab Essentials"  => 3,
        "Fashion"         => 4,
        "Books"           => 5,
        "Services"        => 6,
        "Foods"           => 7,
        "School Supplies" => 8,
        "Art Materials"   => 9,
        "Others"          => 10
    ];

    // Convert the name to an ID. Default to 10 (Others) if not found.
    $category_id = $category_map[$category_name] ?? 10;

    // --- INSERT INTO DATABASE ---
    $stmt = $conn->prepare("INSERT INTO wishlist (user_id, item_name, category_id) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $user_id, $item_name, $category_id);

    if ($stmt->execute()) {
        echo "Success";
    } else {
        echo "Error: " . $conn->error;
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
    // 1. Clear any previous output (warnings/notices) to prevent breaking JSON
    if (ob_get_length()) ob_clean();
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
        $requester= $wish['full_name'];
        $item_name = $wish['item_name'];

        // 1. Insert the Notification (Current Logic)
        $notif_msg = "<b>{$_SESSION['full_name']}</b> has the item: '$item_name'!";
        // In wishlist_controller.php:
        $notif_sql = "INSERT INTO notifications (user_id, sender_id, message, notif_type, target_id, is_read) 
                    VALUES (?, ?, ?, 'wish_match', ?, 0)";
        $notif_stmt = $conn->prepare($notif_sql);
        $notif_stmt->bind_param("iisi", $requester_id, $seller_id, $notif_msg, $wish_id);
        $notif_stmt->execute();

        // 2. NEW: Insert the actual message into the database
        // This ensures the message is stored and visible to both users
        $init_msg = "Hi! I have the '$item_name' you're looking for.";
        $msg_sql = "INSERT INTO messages (product_id, sender_id, receiver_id, message_text) VALUES (0, ?, ?, ?)";
        $msg_stmt = $conn->prepare($msg_sql);
        $msg_stmt->bind_param("iis", $seller_id, $requester_id, $init_msg);

       if ($msg_stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'item_name' => $item_name, 
                'requester' => $wish['full_name']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
    }else {
        echo json_encode(['success' => false, 'message' => 'Wish not found.']);
    }
    exit();
}
?>
