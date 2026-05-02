<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Grabbing the requested attributes
    $bio = $_POST['bio'];
    $phone_number = $_POST['phone_number'];

    // Handling the 'profile_pic' upload
    $image_name = "profile_default.jpg"; 
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $target_dir = "images/";
        $file_ext = pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION);
        $image_name = "user_" . $user_id . "_" . time() . "." . $file_ext;
        
        move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_dir . $image_name);
    }

    // Database Update
    $sql = "UPDATE users SET profile_pic = ?, bio = ?, phone_number = ?, is_setup_complete = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    
    // Binding the 4 strings and 1 integer
    $stmt->bind_param("sssi", $image_name, $bio, $phone_number, $user_id);
    
    if ($stmt->execute()) {
        // Redirect back to dashboard
        header("Location: dashboard/mainweb.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>