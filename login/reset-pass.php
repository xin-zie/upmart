<?php
// 1. Database Connection
include '../db_connect.php';

// 2. Get the token from the URL (e.g., ?token=123...)
$token = $_GET["token"] ?? "";

if (empty($token)) {
    die("Token is missing. Please check your email link.");
}

// 3. Hash the token to compare with the DB
$token_hash = hash("sha256", $token);

// 4. Look for the user with this token
$sql = "SELECT * FROM users WHERE reset_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// 5. Validation Gates
if ($user === null) {
    die("Invalid token. This link may have already been used or is incorrect.");
}

if (strtotime($user["reset_expires"]) <= time()) {
    die("This reset link has expired. Please request a new one.");
}

// If it passes all these, the HTML form below will load!
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password | UPMart</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="loginpanel.css"> 
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0;">
    
    <div class="main-container" style="display: flex; justify-content: center; align-items: center; width: 100%;">
        
        <div style="padding: 40px; background: white; border-radius: 15px; 
                    box-shadow: 0 15px 50px rgba(0,0,0,0.3); 
                    width: 100%; max-width: 400px; text-align: center; position: relative; z-index: 1;">
            
            <div class="brand">
                <img src="../images/logo.png" style="width: 80px; margin-bottom: 20px;" alt="UPMart Logo">
            </div>

            <h1 style="color: #1a1a2e; margin-bottom: 10px;">Reset Password</h1>
            <p style="color: #666; margin-bottom: 25px;">Enter your new password for UPMart.</p>

            <?php if (isset($_GET['error']) && $_GET['error'] == 'match'): ?>
                <div style="background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 0.85rem; border: 1px solid #ef9a9a;">
                    <strong>Error:</strong> Passwords do not match. Please try again.
                </div>
            <?php endif; ?>

            <form method="post" action="process-reset-pass.php">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="input-group" style="text-align: left; margin-bottom: 20px;">
    
                    <?php if (isset($_GET['error'])): ?>
                        <div style="background-color: #ffebee; color: #c62828; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; border: 1px solid #ef9a9a; text-align: center;">
                            <strong>Wait!</strong> 
                            <?php 
                                if ($_GET['error'] == 'match') echo "Passwords do not match.";
                                if ($_GET['error'] == 'short') echo "Password must be at least 8 characters.";
                                if ($_GET['error'] == 'format') echo "Password must include both letters and numbers.";
                            ?>
                        </div>
                    <?php endif; ?>

                    <label for="password" style="display: block; margin-bottom: 5px; font-weight: bold;">New password</label>
                    <input type="password" id="password" name="password" 
                        minlength="8" maxlength="72" required 
                        style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px;"
                    >

                    <label for="password_confirmation" style="display: block; margin-bottom: 5px; font-weight: bold;">Repeat password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" 
                        minlength="8" maxlength="72" required
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"
                    >
                    
                </div>

                <button class="login-btn" onclick="this.innerHTML='Processing...'; this.style.opacity='0.7';">Update Password</button>
            </form>
        </div>

    </div>
</body>
</html>