<?php
include '../db_connect.php';

// 1. Get data from the form
$token = $_POST["token"];
$plain_password = $_POST["password"];
$confirm_password = $_POST["password_confirmation"];

$token_hash = hash("sha256", $token);

// 2. Find the user by token hash
$sql = "SELECT * FROM users WHERE reset_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// 3. Validation Gatekeepers
if ($user === null) {
    die("Invalid request. Token not found.");
}

if (strtotime($user["reset_expires"]) <= time()) {
    die("Link has expired.");
}

/// Check for Password Length
if (strlen($plain_password) < 8) {
    header("Location: reset_password.php?token=$token&error=short");
    exit();
} 

// Check for Letter/Number requirement
if (!preg_match("/[a-z]/i", $plain_password) || !preg_match("/[0-9]/", $plain_password)) {
    header("Location: reset_password.php?token=$token&error=format");
    exit();
}

// Check for Mismatch
if ($plain_password !== $confirm_password) {
    header("Location: reset_password.php?token=$token&error=match");
    exit();
}

// 4. Update the user password and CLEAR the token
$password_hashed = password_hash($plain_password, PASSWORD_DEFAULT);

$sql = "UPDATE users 
        SET password = ?, 
            reset_code = NULL, 
            reset_expires = NULL 
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $password_hashed, $user["user_id"]);

if ($stmt->execute()) {
    // SUCCESS: Send them back to login with a success flag
    header("Location: login.php?reset=complete");
    exit();
} else {
    echo "An error occurred. Please try again.";
}