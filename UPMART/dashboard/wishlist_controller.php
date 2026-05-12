<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Error: Login required.");
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

$action = $_REQUEST['action'] ?? '';

// --- ACTION: ADD A NEW WISH ---
if ($action === 'add') {
    // 1. Updated map to match your NEW 10-category database
    $category_map = [
        "Dorm Essentials" => 1,
        "Electronics"     => 2,
        "Lab Essentials"  => 3,
        "Fashion"         => 4,
        "Books"           => 5,
        "Services"        => 6,
        "Foods"           => 7,
        "School Supplies" => 8,
        "Arki Materials"  => 9,
        "Others"          => 10
    ];

    $user_id = $_SESSION['user_id'] ?? null;
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $category_name = $_POST['category'] ?? 'Others';

    // 2. Convert the name to the ID number
    $category_id = $category_map[$category_name] ?? 10;

    if ($user_id && !empty($item_name)) {
        // 3. IMPORTANT: Changed 'category' (text) to 'category_id' (number)
        $stmt = $conn->prepare("INSERT INTO wishlist (user_id, item_name, category_id) VALUES (?, ?, ?)");

        // 4. Changed "iss" to "isi" (integer, string, integer)
        $stmt->bind_param("isi", $user_id, $item_name, $category_id);

        if ($stmt->execute()) {
            echo "Success";
        } else {
            echo "SQL Error: " . $stmt->error;
        }
    } else {
        echo "Error: Missing user session or item name.";
    }
    exit();
}

// --- ACTION: FETCH WISHES ---
if ($action === 'fetch') {
    // We JOIN with the categories table to get the category_name
    $query = "SELECT w.*, u.full_name, c.category_name 
                FROM wishlist w 
                JOIN users u ON w.user_id = u.user_id 
                JOIN categories c ON w.category_id = c.category_id
                ORDER BY w.created_at DESC LIMIT 10";

    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $is_mine = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['user_id']);

            echo '
                <div class="wish-card">
                    <div class="wish-info">
                        <span class="category-tag">' . htmlspecialchars($row['category_name']) . '</span>
                        <h4>' . htmlspecialchars($row['item_name']) . '</h4>
                        <p>Requested by: <strong>' . ($is_mine ? "Me" : htmlspecialchars($row['full_name'])) . '</strong></p>
                    </div>
                    ' . (!$is_mine ? '<button class="match-btn" onclick="handleMatch(' . $row['wish_id'] . ')">I have this!</button>' : '<small>Your Wish</small>') . '
                </div>';
        }
    }
    exit();
}

if ($action === 'match_wish') {
    ob_start();
    header('Content-Type: application/json');

    $wish_id = intval($_POST['wish_id']);
    $seller_id = $_SESSION['user_id'];
    $seller_name = $_SESSION['full_name'] ?? 'A Seller';

    // 1. UPDATED: Get wish details AND the requester's name using a JOIN
    $stmt = $conn->prepare("SELECT w.user_id, w.item_name, u.full_name 
                                FROM wishlist w 
                                JOIN users u ON w.user_id = u.user_id 
                                WHERE w.wish_id = ?");
    $stmt->bind_param("i", $wish_id);
    $stmt->execute();
    $wish = $stmt->get_result()->fetch_assoc();

    if ($wish) {
        $requester_id = $wish['user_id'];
        $requester_name = $wish['full_name']; // Now we have the name!
        $item_name = $wish['item_name'];

        // 2. Insert Notification
        $notif_msg = "<b>$seller_name</b> has the item: '$item_name'!";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, sender_id, message, notif_type, target_id) VALUES (?, ?, ?, 'wish_match', ?)");
        $notif_stmt->bind_param("iisi", $requester_id, $seller_id, $notif_msg, $wish_id);
        $notif_stmt->execute();

        // 3. Insert Message
        $init_msg = "Hi! I have the '$item_name' you're looking for.";
        $msg_stmt = $conn->prepare("INSERT INTO messages (product_id, sender_id, receiver_id, message_text) VALUES (0, ?, ?, ?)");
        $msg_stmt->bind_param("iis", $seller_id, $requester_id, $init_msg);

        if ($msg_stmt->execute()) {
            ob_clean();
            // 4. UPDATED: Added requester_name to the JSON response
            echo json_encode([
                'success' => true,
                'item_name' => $item_name,
                'requester_name' => $requester_name,
                'requester_id' => $requester_id
            ]);
        } else {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Chat error: ' . $conn->error]);
        }
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Wish not found']);
    }
    exit();
}
