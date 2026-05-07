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
        // Updated to match your exact phpMyAdmin screenshot
        $category_map = [
            "Dorm Essentials" => 1,
            "Arki Mats"       => 2,
            "Lab Essentials"  => 3,
            "Others"          => 4, // Your DB has Others at 4
            "Books"           => 5,
            "Services"        => 6,
            "Foods"           => 7,
            "School Supplies" => 8,
            "Art Materials"   => 9, 
            "Fashion"         => 4  // Mapping Fashion to 'Others' since it's missing from DB
        ];

        $user_id = $_SESSION['user_id'] ?? null;
        $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
        $category_name = $_POST['category'] ?? 'Others';

        if (!empty($item_name)) {
            $stmt = $conn->prepare("INSERT INTO wishlist (user_id, item_name, category) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $item_name, $category_name);
            
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
        // No WHERE clause here means total public visibility
        $query = "SELECT w.*, u.full_name FROM wishlist w 
                JOIN users u ON w.user_id = u.user_id 
                ORDER BY w.created_at DESC LIMIT 10";
        
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Check if the post belongs to the current user to style it differently
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

    if ($action === 'match_wish') {
        $wish_id = $_POST['wish_id'];
        $sender_id = $_SESSION['user_id'];

        // 1. Find out who owns the wish
        $owner_query = "SELECT user_id, item_name FROM wishlist WHERE wish_id = ?";
        $stmt = $conn->prepare($owner_query);
        $stmt->bind_param("i", $wish_id);
        $stmt->execute();
        $wish = $stmt->get_result()->fetch_assoc();
        
        $owner_id = $wish['user_id'];
        $item_name = $wish['item_name'];

        // 2. Create the notification message
        $sender_name = $_SESSION['full_name']; // Ensure this is in your session!
        $notif_msg = "$sender_name has the '$item_name' you're looking for!";

        // 3. Insert into notifications table
        $insert_notif = "INSERT INTO notifications (user_id, sender_id, message) VALUES (?, ?, ?)";
        $notif_stmt = $conn->prepare($insert_notif);
        $notif_stmt->bind_param("iis", $owner_id, $sender_id, $notif_msg);
        
        if ($notif_stmt->execute()) {
            echo "Success";
        }
        exit();
    }
?>
