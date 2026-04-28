<?php
include 'db_connect.php';
include 'functions.php';
session_start();

// Security: Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    die("Access Denied.");
}

$user_id = $_SESSION['user_id'];

// We use $_REQUEST so it catches 'action' from both POST forms and GET links
$action = $_REQUEST['action'] ?? '';

// --- 1. CREATE ACTION ---
if ($action == 'create' && $_SERVER["REQUEST_METHOD"] == "POST") {
    $target_dir = "uploads/";
    $file_name = time() . "_" . basename($_FILES["product_image"]["name"]);
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
        product_add_image(
            $conn, 
            $user_id, 
            $_POST['category_id'], 
            $_POST['title'], 
            $_POST['description'], 
            $_POST['price'], 
            $_POST['condition'], 
            $target_file
        );
        header("Location: my_listings.php?msg=created");
        exit();
    }
}

// --- 2. EDIT ACTION ---
elseif ($action == 'edit' && $_SERVER["REQUEST_METHOD"] == "POST") {
    product_update_listing(
        $conn, 
        $_POST['product_id'], 
        $user_id, 
        $_POST['category_id'], 
        $_POST['title'], 
        $_POST['description'], 
        $_POST['price'], 
        $_POST['condition'], 
        $_POST['status']
    );
    header("Location: my_listings.php?msg=updated");
    exit();
}

// --- 3. DELETE ACTION ---
elseif ($action == 'delete' && isset($_GET['id'])) {
    $product_id = $_GET['id'];
    if (product_delete($conn, $product_id, $user_id)) {
        header("Location: my_listings.php?msg=deleted");
        exit();
    }
}

// --- 4. STATUS UPDATE ACTION ---
elseif ($action == 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $product_id = $_GET['id'];
    $new_status = $_GET['status'];
    if (product_update_status($conn, $product_id, $user_id, $new_status)) {
        header("Location: my_listings.php?msg=status_changed");
        exit();
    }
}

// --- DEFAULT REDIRECT ---
else {
    header("Location: my_listings.php");
    exit();
}

?>