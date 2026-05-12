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
        
        if (!empty($_FILES['product_images']['name'][0])) {
            if (isset($_POST['update_post'])) {
                $old = $conn->query("SELECT image_path FROM media WHERE product_id='$product_id'");
                while ($r = $old->fetch_assoc()) {
                    if (file_exists($r['image_path'])) unlink($r['image_path']);
                }
                $conn->query("DELETE FROM media WHERE product_id='$product_id'");
            }
            $upload_dir = 'uploads/';
            foreach ($_FILES['product_images']['tmp_name'] as $i => $tmp) {
                if ($_FILES['product_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $fileName   = time() . '_' . $i . '_' . basename($_FILES['product_images']['name'][$i]);
                $targetPath = $upload_dir . $fileName;
                if (move_uploaded_file($tmp, $targetPath)) {
                    $safe = mysqli_real_escape_string($conn, $targetPath);
                    $conn->query("INSERT INTO media (product_id, image_path) VALUES ('$product_id', '$safe')");
                }
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
    
    // 1. Get the buyer's name from the session
    $buyer_name = $_SESSION['full_name']; 

    // 2. Fetch the product title so we can say "your [product] product"
    $product_query = $conn->query("SELECT title FROM products WHERE product_id = '$p_id'");
    $product_data  = $product_query->fetch_assoc();
    $product_title = $product_data['title'];

    // Prevent duplicate pending orders
    $existing = $conn->query("SELECT order_id FROM orders 
                               WHERE product_id='$p_id' AND buyer_id='$buyer_id' AND status='Pending'");
    if ($existing && $existing->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending order for this item.']);
        exit;
    }

    // 3. Insert the order
    $ok = $conn->query("INSERT INTO orders (product_id, buyer_id, seller_id, status) 
                        VALUES ('$p_id', '$buyer_id', '$seller_id', 'Pending')");
    
    if ($ok) {
        // 4. INSERT NOTIFICATION FOR THE SELLER
        $notif_text = "<b>$buyer_name</b> wants to buy your <b>$product_title</b> product";
        
        $conn->query("INSERT INTO notifications (user_id, sender_id, message, notif_type, target_id, is_read) 
                      VALUES ('$seller_id', '$buyer_id', '$notif_text', 'order', '$p_id', 0)");
    }

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
// 1. GET CONVERSATION LIST (inbox)
if (isset($_GET['get_conversations'])) {
    try{
        $query = "SELECT m.message_text as last_msg, 
                        u.full_name as other_user_name, 
                        u.profile_pic, 
                        p.title as prod_title, 
                        m.product_id,
                        CASE WHEN m.sender_id = $me THEN m.receiver_id ELSE m.sender_id END as other_id
                FROM messages m
                JOIN (
                    SELECT MAX(message_id) as max_id 
                    FROM messages 
                    WHERE sender_id = $me OR receiver_id = $me 
                    GROUP BY COALESCE(product_id, 0), 
                            CASE WHEN sender_id = $me THEN receiver_id ELSE sender_id END
                ) latest ON m.message_id = latest.max_id
                JOIN users u ON u.user_id = (CASE WHEN m.sender_id = $me THEN m.receiver_id ELSE m.sender_id END)
                LEFT JOIN products p ON m.product_id = p.product_id
                ORDER BY m.created_at DESC";
        
        $res = $conn->query($query);
        $convos = [];
        while ($row = $res->fetch_assoc()) {
            // --- PATH LOGIC: Check if it's a default pic or an upload ---
            $raw_pic = $row['profile_pic'];
            if (empty($raw_pic) || $raw_pic == 'profile.jpg') {
                $profile_pic = 'assets/profile.jpg'; // Path from Marketplace root
            } else {
                // Remove 'uploads/' prefix if it's already there to prevent doubling
                $clean_pic = str_replace('uploads/', '', $raw_pic);
                $profile_pic = 'uploads/' . $clean_pic;
            }

            $display_title = $row['prod_title'] ?? "Wishlist Match";

            $convos[] = [
                'other_id'        => $row['other_id'],
                'other_user_name' => $row['other_user_name'],
                'profile_pic'     => $profile_pic,
                'product_id'      => $row['product_id'] ?? 0,
                'prod_title'      => $display_title,
                'last_msg'        => $row['last_msg']
            ];
        }
        echo json_encode($convos);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// 2. GET MESSAGES (Mark notifications as read)
if (isset($_GET['get_messages'])) {
    $other = intval($_GET['other_user']);
    $p_id = intval($_GET['product_id']);

    // Mark notifications as read
    $conn->query("UPDATE notifications SET is_read = 1 
                  WHERE user_id = $me AND sender_id = $other");

    // --- CRITICAL FIX: Handle Wishlist matches (NULL) vs Products (ID) ---
    if ($p_id === 0) {
        $product_clause = "(product_id = 0 OR product_id IS NULL)";
    } else {
        $product_clause = "product_id = $p_id";
    }

    $query = "SELECT * FROM messages 
          WHERE $product_clause 
          AND ((sender_id = $me AND receiver_id = $other) 
          OR (sender_id = $other AND receiver_id = $me)) 
          ORDER BY created_at ASC";
    
    $res = $conn->query($query);
    $msgs = [];
    while($row = $res->fetch_assoc()) {
        $msgs[] = [
            'message' => $row['message_text'],
            // Formatting the time makes it look better in the chat bubble
            'sent_at' => date('g:i A', strtotime($row['created_at'])),
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
    
    // Convert 0 to NULL so it stores correctly in the messages table
    // (Ensure your 'messages' table allows NULL for product_id)
    $db_product_id = ($product_id === 0) ? "NULL" : $product_id;

    if (!empty($message)) {
        $insert = "INSERT INTO messages (product_id, sender_id, receiver_id, message_text) 
                   VALUES ($db_product_id, $me, $receiver_id, '$message')";
        
        if ($conn->query($insert)) {
            $sender_name = $_SESSION['full_name'];
            
            // LOGIC FOR WISHLIST MATCHES (product_id 0)
            if ($product_id === 0) {
                $notif_text = "<b>$sender_name</b> sent you a message about your <b>Wish</b>.";
                $notif_type = 'wish_match';
                $target_id = 0; // Or link back to the specific wish_id if you have it
            } 
            // LOGIC FOR REGULAR PRODUCTS
            else {
                $p_res = $conn->query("SELECT title FROM products WHERE product_id = $product_id");
                $p_row = $p_res ? $p_res->fetch_assoc() : null;
                $product_title = $p_row['title'] ?? 'Product';

                $notif_text = "<b>$sender_name</b> messaged on your <b>$product_title</b> product";
                $notif_type = 'message';
                $target_id = $product_id;
            }

            // Insert notification for the receiver
            $conn->query("INSERT INTO notifications (user_id, sender_id, message, notif_type, target_id, is_read) 
                          VALUES ($receiver_id, $me, '$notif_text', '$notif_type', $target_id, 0)");
                        
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
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