<?php
session_start();
include '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $phone = $_POST['phone'];
    $social = $_POST['social'];
    $bio = $_POST['bio'];

    // Update the user record
    $sql = "UPDATE users SET phone_number = ?, social_link = ?, bio = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $phone, $social, $bio, $user_id);

    if ($stmt->execute()) {
        header("Location: mainweb.php"); // Refresh to show dashboard
    } else {
        echo "Error updating profile.";
    }
}
?>