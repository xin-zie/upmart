<?php
session_start();
$conn = new mysqli("localhost", "root", "", "upmart");

// For this demo, let's assume Diane is logged in (User ID 18 based on your SQL)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 18; 
    $_SESSION['user_name'] = "Diane Mahusay";
}

if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
?>