<?php
include 'UPMART\db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['up_email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $is_setup_complete = $_POST['is_setup_complete'];
    $role = $_POST['user_role'];

    if (!str_ends_with($email, '@up.edu.ph')) {
        echo "<script>alert('Error: Use @up.edu.ph email!');</script>";
    } else {
        // Prepare statement
        $stmt = $conn->prepare("INSERT INTO Users (full_name, up_email, password, is_setup_complete, user_role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssss", $full_name, $email, $password, $is_setup_complete, $role);

        if ($stmt->execute()) {
            echo "Account created! <a href='login.php'>Login here</a>";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>