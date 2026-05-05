<?php
session_start(); 
include '../db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$me = $_SESSION['user_id'];

// --- PLACE ORDER (Buy Item button) ---
if (isset($_POST['place_order'])) {
    header('Content-Type: application/json');
    $p_id      = (int)$_POST['product_id'];
    $seller_id = (int)$_POST['seller_id'];

    $ok = $conn->query("INSERT INTO orders (product_id, buyer_id, seller_id, status) 
                        VALUES ('$p_id', '$me', '$seller_id', 'Pending')");
    echo json_encode(['success' => (bool)$ok]);
    exit;
}

// --- MESSAGING SYSTEM ---

// 1. GET CONVERSATION LIST (Fixed with LEFT JOIN for Wish Matches)
if (isset($_GET['get_conversations'])) {
    $query = "SELECT m.message as last_message, 
                     u.full_name as other_user_name, 
                     u.profile_pic, 
                     COALESCE(p.title, 'Wishlist Match') as product_name, 
                     m.product_id,
                     CASE WHEN m.sender_id = $me THEN m.receiver_id ELSE m.sender_id END as other_id
              FROM messages m
              JOIN (
                  SELECT MAX(message_id) as max_id 
                  FROM messages 
                  WHERE sender_id = $me OR receiver_id = $me 
                  GROUP BY product_id, 
                           CASE WHEN sender_id = $me THEN receiver_id ELSE sender_id END
              ) latest ON m.message_id = latest.max_id
              JOIN users u ON u.user_id = (CASE WHEN m.sender_id = $me THEN m.receiver_id ELSE m.sender_id END)
              LEFT JOIN products p ON m.product_id = p.product_id
              ORDER BY m.created_at DESC";
    
    $res = $conn->query($query);
    $convos = [];
    while($row = $res->fetch_assoc()) { 
        $row['profile_pic'] = (!empty($row['profile_pic'])) ? $row['profile_pic'] : '../images/profile.jpg';
        $convos[] = $row; 
    }
    echo json_encode($convos);
    exit();
}

// 2. GET MESSAGES (Revised for History)
if (isset($_GET['get_messages'])) {
    $other = intval($_GET['other_user']);
    $p_id = intval($_GET['product_id']);

    // Mark notifications as read when opening history
    $conn->query("UPDATE notifications SET is_read = 1 
                  WHERE user_id = $me AND sender_id = $other");

    // Using "message_text" to match your database screenshot
    $query = "SELECT message_text, sender_id, created_at FROM messages 
              WHERE product_id = $p_id 
              AND ((sender_id = $me AND receiver_id = $other) 
              OR (sender_id = $other AND receiver_id = $me)) 
              ORDER BY created_at ASC";
    
    $res = $conn->query($query);
    $msgs = [];
    while($row = $res->fetch_assoc()) {
        $msgs[] = [
            'message' => $row['message_text'],       
            'sent_at' => date('M d, h:i A', strtotime($row['created_at'])),
            'is_mine' => ($row['sender_id'] == $me)
        ];
    }
    echo json_encode($msgs);
    exit();
}

// 3. SEND MESSAGE & CREATE NOTIFICATION (Standardized type/target)
if (isset($_POST['send_message'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $product_id = intval($_POST['product_id']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    if (!empty($message)) {
        $sql = "INSERT INTO messages (product_id, sender_id, receiver_id, message) 
                VALUES ($product_id, $me, $receiver_id, '$message')";
        
        if ($conn->query($sql)) {
            $sender_name = $_SESSION['full_name'];
            $notif_text = "<b>$sender_name</b> sent you a message.";
            
            // Added target_id and notif_type for redirection
            $conn->query("INSERT INTO notifications (user_id, sender_id, message, notif_type, target_id, is_read) 
                          VALUES ($receiver_id, $me, '$notif_text', 'message', $me, 0)");
            echo json_encode(['success' => true]);
        }
    }
    exit();
}

// 4. FETCH NOTIFICATIONS FOR BENTO CARDS (Summary View)
if (isset($_GET['fetch_notifications_list'])) {
    $query = "SELECT n.*, u.full_name as sender_name, u.profile_pic 
              FROM notifications n
              JOIN users u ON n.sender_id = u.user_id
              WHERE n.user_id = $me 
              ORDER BY n.created_at DESC LIMIT 10";
    
    $res = $conn->query($query);
    $list = [];
    while($row = $res->fetch_assoc()) {
        $list[] = [
            'sender'   => $row['sender_name'],
            'msg'      => $row['message'],
            'type'     => $row['notif_type'],   // Added for redirection from Bento
            'target'   => $row['target_id'],    // Added for redirection from Bento
            'profile'  => !empty($row['profile_pic']) ? $row['profile_pic'] : '../images/profile.jpg',
            'time'     => date('g:i A', strtotime($row['created_at'])),
            'days_ago' => floor((time() - strtotime($row['created_at'])) / 86400),
            'is_read'  => $row['is_read']
        ];
    }
    echo json_encode($list);
    exit();
}
?>