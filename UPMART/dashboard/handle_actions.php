<?php
session_start(); 
include '../db_connect.php'; 

// Security check: Ensure user is logged in before processing any AJAX
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$me = $_SESSION['user_id'];

// --- DELETE LISTING ---
if (isset($_GET['delete_id'])) {
    $p_id = (int)$_GET['delete_id'];
    
    $res = $conn->query("SELECT image_path FROM media WHERE product_id = '$p_id'");
    if ($img = $res->fetch_assoc()) { 
        if(file_exists($img['image_path'])) unlink($img['image_path']); 
    }
    
    $conn->query("DELETE FROM media WHERE product_id = '$p_id'");
    $conn->query("DELETE FROM products WHERE product_id = '$p_id' AND seller_id = '$me'");
    header("Location: marketplace.php");
}

// --- CREATE OR UPDATE PRODUCT ---
if (isset($_POST['create_post']) || isset($_POST['update_post'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $price = $_POST['price'];
    $cat_id = $_POST['category_id'];
    $desc = mysqli_real_escape_string($conn, $_POST['description']);

    if (isset($_POST['update_post'])) {
        $p_id = $_POST['product_id'];
        $sql = "UPDATE products SET title='$title', price='$price', category_id='$cat_id', description='$desc' 
                WHERE product_id='$p_id' AND seller_id='$me'";
    } else {
        $sql = "INSERT INTO products (seller_id, category_id, title, price, description, status) 
                VALUES ('$me', '$cat_id', '$title', '$price', '$desc', 'Available')";
    }

    if ($conn->query($sql)) {
        $product_id = isset($_POST['update_post']) ? $_POST['product_id'] : $conn->insert_id;
        
        if (!empty($_FILES['product_image']['name'])) {
            $fileName = time() . "_" . basename($_FILES['product_image']['name']);
            $targetPath = "uploads/" . $fileName;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $targetPath)) {
                if (isset($_POST['update_post'])) { 
                    $conn->query("DELETE FROM media WHERE product_id='$product_id'"); 
                }
                $conn->query("INSERT INTO media (product_id, image_path) VALUES ('$product_id', '$targetPath')");
            }
        }
    }
    header("Location: marketplace.php");
}
// --- PLACE ORDER (Buy Item button) ---
// Called via AJAX (JSON response)
if (isset($_POST['place_order'])) {
    header('Content-Type: application/json');
    $p_id      = (int)$_POST['product_id'];
    $seller_id = (int)$_POST['seller_id'];
    $buyer_id  = (int)$me;

    // Prevent duplicate pending orders
    $existing = $conn->query("SELECT order_id FROM orders 
                               WHERE product_id='$p_id' AND buyer_id='$buyer_id' AND status='Pending'");
    if ($existing && $existing->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending order for this item.']);
        exit;
    }

    $ok = $conn->query("INSERT INTO orders (product_id, buyer_id, seller_id, status) 
                        VALUES ('$p_id', '$buyer_id', '$seller_id', 'Pending')");
    echo json_encode(['success' => (bool)$ok]);
    exit;
}

// --- CONFIRM DEAL (Seller marks order as completed) ---
// Called via AJAX (JSON response)
if (isset($_POST['confirm_deal'])) {
    header('Content-Type: application/json');
    $order_id  = (int)$_POST['order_id'];
    $seller_id = (int)$me;

    // Update order status
    $conn->query("UPDATE orders SET status='Completed' 
                  WHERE order_id='$order_id' AND seller_id='$seller_id'");

    // Mark the product as Sold
    $conn->query("UPDATE products SET status='Sold' 
                  WHERE product_id = (SELECT product_id FROM orders WHERE order_id='$order_id') 
                  AND seller_id='$seller_id'");

    echo json_encode(['success' => true]);
    exit;
}


// --- MESSAGING SYSTEM (AJAX) ---

// 1. GET CONVERSATION LIST
if (isset($_GET['get_conversations'])) {
    $query = "SELECT m.message_text as last_message, 
                     u.full_name as other_user_name, 
                     u.profile_pic, 
                     p.title as product_name, 
                     p.product_id,
                     CASE WHEN m.sender_id = $me THEN m.receiver_id ELSE m.sender_id END as other_id
              FROM messages m
              JOIN (
                  SELECT MAX(message_id) as max_id 
                  FROM messages 
                  WHERE sender_id = $me OR receiver_id = $me 
                  GROUP BY product_id
              ) latest ON m.message_id = latest.max_id
              JOIN users u ON u.user_id = (CASE WHEN m.sender_id = $me THEN m.receiver_id ELSE m.sender_id END)
              JOIN products p ON m.product_id = p.product_id
              ORDER BY m.created_at DESC";
    
    $res = $conn->query($query);
    $convos = [];
    while($row = $res->fetch_assoc()) { 
        // Logic to ensure the dashboard can find the images
        $row['profile_pic'] = (!empty($row['profile_pic'])) ? $row['profile_pic'] : '../images/profile.jpg';
        $convos[] = $row; 
    }
    echo json_encode($convos);
    exit();
}

// 2. GET MESSAGES (Mark notifications as read)
if (isset($_GET['get_messages'])) {
    $other = intval($_GET['other_user']);
    $p_id = intval($_GET['product_id']);

    // Mark notifications as read when Glendy or Natasha opens the chat
    $conn->query("UPDATE notifications SET is_read = 1 
                  WHERE user_id = $me AND sender_id = $other");

    $query = "SELECT * FROM messages 
              WHERE product_id = $p_id 
              AND ((sender_id = $me AND receiver_id = $other) 
              OR (sender_id = $other AND receiver_id = $me)) 
              ORDER BY created_at ASC";
    
    $res = $conn->query($query);
    $msgs = [];
    while($row = $res->fetch_assoc()) {
        $msgs[] = [
            'message' => $row['message_text'],
            'sent_at' => $row['created_at'],
            'is_mine' => ($row['sender_id'] == $me)
        ];
    }
    echo json_encode($msgs);
    exit();
}

// 3. SEND MESSAGE & CREATE NOTIFICATION
if (isset($_POST['send_message'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $product_id = intval($_POST['product_id']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    if (!empty($message)) {
        $insert = "INSERT INTO messages (product_id, sender_id, receiver_id, message_text) 
                   VALUES ($product_id, $me, $receiver_id, '$message')";
        
        if ($conn->query($insert)) {
            $sender_name = $_SESSION['full_name'];
            $notif_text = "<b>$sender_name</b> messaged: \"$message\"";
            
            // Insert notification with the correct sender_id
            $conn->query("INSERT INTO notifications (user_id, sender_id, message, is_read) 
                          VALUES ($receiver_id, $me, '$notif_text', 0)");
                        
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    }
    exit();
}

// 4. FETCH NOTIFICATIONS FOR BENTO CARDS
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
            'sender'  => $row['sender_name'],
            'msg'     => $row['message'],
            // Check pathing relative to where maindash.js is calling it
            'profile' => !empty($row['profile_pic']) ? $row['profile_pic'] : '../images/profile.jpg',
            'time'    => date('g:i A', strtotime($row['created_at'])),
            'days_ago'=> floor((time() - strtotime($row['created_at'])) / 86400),
            'is_read' => $row['is_read']
        ];
    }
    echo json_encode($list);
    exit();
}
?>