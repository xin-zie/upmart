<?php
session_start();
include 'db_connect.php'; // Correct since both are in root

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $bio = trim($_POST['bio']);
    $phone_number = trim($_POST['phone_number']);

    $image_name = "profile_default.jpg";

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $target_dir = "dashboard/uploads/";
        $file_ext = pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION);
        $image_name = "user_" . $user_id . "_" . time() . "." . $file_ext;
        
        move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_dir . $image_name);
    }

    // Prepare and bind
    $sql = "UPDATE users SET profile_pic = ?, bio = ?, phone_number = ?, is_setup_complete = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    
    // Check if preparation failed
    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param("sssi", $image_name, $bio, $phone_number, $user_id);

    if ($stmt->execute()) {
        header("Location: dashboard/mainweb.php");
        exit(); 
    } else {
        echo "Execution Error: " . $stmt->error;
    }
}
?>