<?php
session_start();
include 'db_connect.php'; // Path depends on where this file is

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $details = $_POST['details'];
    $user_id = $_SESSION['user_id'] ?? 0;

    $stmt = $conn->prepare("INSERT INTO reports (reason, details, status, created_at) VALUES (?, ?, 'Pending', NOW())");
    $stmt->bind_param("ss", $type, $details);

    if ($stmt->execute()) {
        echo "Success";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
