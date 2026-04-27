<?php
include 'db_connect.php';

// --- DELETE LISTING ---
if (isset($_GET['delete_id'])) {
    $p_id = (int)$_GET['delete_id'];
    $seller_id = $_SESSION['user_id'];
    
    // First remove image from folder and database
    $res = $conn->query("SELECT image_path FROM media WHERE product_id = '$p_id'");
    if ($img = $res->fetch_assoc()) { unlink($img['image_path']); }
    
    $conn->query("DELETE FROM media WHERE product_id = '$p_id'");
    $conn->query("DELETE FROM products WHERE product_id = '$p_id' AND seller_id = '$seller_id'");
    header("Location: marketplace.php");
}

// --- CREATE OR UPDATE PRODUCT ---
if (isset($_POST['create_post']) || isset($_POST['update_post'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $price = $_POST['price'];
    $cat_id = $_POST['category_id'];
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $seller_id = $_SESSION['user_id'];

    if (isset($_POST['update_post'])) {
        $p_id = $_POST['product_id'];
        $sql = "UPDATE products SET title='$title', price='$price', category_id='$cat_id', description='$desc' 
                WHERE product_id='$p_id' AND seller_id='$seller_id'";
    } else {
        $sql = "INSERT INTO products (seller_id, category_id, title, price, description, status) 
                VALUES ('$seller_id', '$cat_id', '$title', '$price', '$desc', 'Available')";
    }

    if ($conn->query($sql)) {
        $product_id = isset($_POST['update_post']) ? $_POST['product_id'] : $conn->insert_id;
        
        if (!empty($_FILES['product_image']['name'])) {
            $fileName = time() . "_" . basename($_FILES['product_image']['name']);
            $targetPath = "uploads/" . $fileName;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $targetPath)) {
                // If updating, clear old image record
                if (isset($_POST['update_post'])) { 
                    $conn->query("DELETE FROM media WHERE product_id='$product_id'"); 
                }
                // Save path to image_path column
                $conn->query("INSERT INTO media (product_id, image_path) VALUES ('$product_id', '$targetPath')");
            }
        }
    }
    header("Location: marketplace.php");
}

// --- HANDLE ADD TO CART ---
if (isset($_POST['add_to_cart'])) {
    $p_id = $_POST['product_id'];
    $b_id = $_SESSION['user_id'];
    $conn->query("INSERT INTO transactions (product_id, buyer_id, status) VALUES ('$p_id', '$b_id', 'Pending')");
    header("Location: marketplace.php");
}
?>