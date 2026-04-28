<?php
session_start();
include '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        die("Error: Login required.");
    }

    $user_id = $_SESSION['user_id'];
    $reason = $_POST['type'] ?? '';
    $details = trim($_POST['details'] ?? '');

    if (!empty($reason) && !empty($details)) {
        $stmt = $conn->prepare("INSERT INTO reports (user_id, reason, details) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $reason, $details);

        if ($stmt->execute()) {
            echo "Success";
        } else {
            echo "Error: " . $stmt->error;
        }
    } else {
        echo "Error: All fields are required.";
    }
    exit();
}
?>