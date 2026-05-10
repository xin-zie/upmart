<?php
include '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["up_email"]);

    // 1. Generate secure tokens
    $token = bin2hex(random_bytes(16)); // This goes in the email link
    $token_hash = hash("sha256", $token); // This goes in the database
    $expiry = date("Y-m-d H:i:s", time() + 60 * 30); // Valid for 30 minutes

    // 2. Update the Database
    $sql = "UPDATE users SET reset_code = ?, reset_expires = ? WHERE up_email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $token_hash, $expiry, $email);
    $stmt->execute();

    // 3. Send Email only if the user exists
    if ($conn->affected_rows > 0) {
        // Load your mailer (Make sure path is correct based on our last fix!)
        $mail = require __DIR__ . "/mailer.php";

        try {
            $mail->setFrom("upmart-admin@up.edu.ph", "UPMart Admin");
            $mail->addAddress($email);
            $mail->Subject = "Reset Your UPMart Password";
            
            // Link matches your dash-separated filename
            $reset_link = "http://localhost/UPMART/login/reset_pass.php?token=$token";
            
            $mail->Body = "
                <h3>UPMart Password Reset</h3>
                <p>We received a request to reset your password. Click the link below to proceed:</p>
                <p><a href='$reset_link'>$reset_link</a></p>
                <p>This link will expire in 30 minutes. If you did not request this, please ignore this email.</p>
            ";

            $mail->send();
            // REDIRECT TO SUCCESS PAGE
            header("Location: success_reset.php");
            exit();

        } catch (Exception $e) {
            echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        // We show the same message for security so hackers don't know which emails exist
        echo "If an account exists, a reset link has been sent.";
    }
} else {
    header("Location: forgot-pass.php");
    exit();
}