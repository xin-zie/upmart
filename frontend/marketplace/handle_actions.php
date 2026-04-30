<?php
include 'db_connect.php';

// --- DELETE LISTING ---
if (isset($_GET['delete_id'])) {
    $p_id     = (int)$_GET['delete_id'];
    $seller_id = (int)$_SESSION['user_id'];

    $res = $conn->query("SELECT image_path FROM media WHERE product_id = '$p_id'");
    if ($img = $res->fetch_assoc()) {
        if (file_exists($img['image_path'])) unlink($img['image_path']);
    }
    $conn->query("DELETE FROM media    WHERE product_id = '$p_id'");
    $conn->query("DELETE FROM products WHERE product_id = '$p_id' AND seller_id = '$seller_id'");
    header("Location: marketplace.php");
    exit;
}

// --- CREATE OR UPDATE PRODUCT ---
if (isset($_POST['create_post']) || isset($_POST['update_post'])) {
    $title     = mysqli_real_escape_string($conn, $_POST['title']);
    $price     = (float)$_POST['price'];
    $cat_id    = (int)$_POST['category_id'];
    $desc      = mysqli_real_escape_string($conn, $_POST['description']);
    $seller_id = (int)$_SESSION['user_id'];

    if (isset($_POST['update_post'])) {
        $p_id = (int)$_POST['product_id'];
        $sql  = "UPDATE products 
                 SET title='$title', price='$price', category_id='$cat_id', description='$desc' 
                 WHERE product_id='$p_id' AND seller_id='$seller_id'";
    } else {
        // New listing always starts as Pending approval
        $sql = "INSERT INTO products (seller_id, category_id, title, price, description, status, approval_status) 
                VALUES ('$seller_id', '$cat_id', '$title', '$price', '$desc', 'Available', 'Pending')";
    }

    if ($conn->query($sql)) {
        $product_id = isset($_POST['update_post']) ? (int)$_POST['product_id'] : $conn->insert_id;

        if (!empty($_FILES['product_image']['name'])) {
            $fileName   = time() . "_" . basename($_FILES['product_image']['name']);
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
    exit;
}

// --- PLACE ORDER (Buy Item button) ---
// Called via AJAX (JSON response)
if (isset($_POST['place_order'])) {
    header('Content-Type: application/json');
    $p_id      = (int)$_POST['product_id'];
    $seller_id = (int)$_POST['seller_id'];
    $buyer_id  = (int)$_SESSION['user_id'];

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
    $seller_id = (int)$_SESSION['user_id'];

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

// --- SEND MESSAGE ---
// Called via AJAX (JSON response)
if (isset($_POST['send_message'])) {
    header('Content-Type: application/json');
    $receiver_id = (int)$_POST['receiver_id'];
    $product_id  = (int)$_POST['product_id'];
    $message     = mysqli_real_escape_string($conn, trim($_POST['message']));
    $sender_id   = (int)$_SESSION['user_id'];

    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Empty message.']);
        exit;
    }

    $ok = $conn->query("INSERT INTO messages (sender_id, receiver_id, product_id, message) 
                        VALUES ('$sender_id', '$receiver_id', '$product_id', '$message')");
    echo json_encode(['success' => (bool)$ok]);
    exit;
}

// --- GET MESSAGES ---
// Called via AJAX (JSON response)
if (isset($_GET['get_messages'])) {
    header('Content-Type: application/json');
    $other_user = (int)$_GET['other_user'];
    $product_id = (int)$_GET['product_id'];
    $me         = (int)$_SESSION['user_id'];

    $result = $conn->query("SELECT m.*, u.full_name as sender_name 
                            FROM messages m 
                            JOIN users u ON m.sender_id = u.user_id 
                            WHERE m.product_id = '$product_id'
                            AND ((m.sender_id = '$me' AND m.receiver_id = '$other_user') 
                              OR (m.sender_id = '$other_user' AND m.receiver_id = '$me'))
                            ORDER BY m.sent_at ASC");

    $msgs = [];
    while ($row = $result->fetch_assoc()) {
        $msgs[] = [
            'message_id'  => $row['message_id'],
            'sender_id'   => $row['sender_id'],
            'sender_name' => $row['sender_name'],
            'message'     => $row['message'],
            'sent_at'     => $row['sent_at'],
            'is_mine'     => ($row['sender_id'] == $me),
        ];
    }
    echo json_encode($msgs);
    exit;
}

// --- GET CONVERSATIONS LIST ---
if (isset($_GET['get_conversations'])) {
    header('Content-Type: application/json');
    $me = (int)$_SESSION['user_id'];

    // Get unique conversations: latest message per product+partner
    $result = $conn->query("
        SELECT m.product_id, p.title as product_name,
               IF(m.sender_id = '$me', m.receiver_id, m.sender_id) as other_user_id,
               u.full_name as other_user_name,
               m.message as last_message,
               m.sent_at
        FROM messages m
        JOIN products p ON m.product_id = p.product_id
        JOIN users u ON u.user_id = IF(m.sender_id = '$me', m.receiver_id, m.sender_id)
        WHERE m.message_id IN (
            SELECT MAX(message_id) FROM messages
            WHERE sender_id = '$me' OR receiver_id = '$me'
            GROUP BY product_id, LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)
        )
        ORDER BY m.sent_at DESC
    ");

    $convos = [];
    while ($row = $result->fetch_assoc()) {
        $convos[] = $row;
    }
    echo json_encode($convos);
    exit;
}
?>