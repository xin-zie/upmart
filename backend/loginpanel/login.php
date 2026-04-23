<?php
include '../db_connect.php';
session_start();

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
    
$error_message = "";
$success_message = "";

// If the user isn't logged in via Session, check if they have a "Remember Me" cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];
    $_SESSION['full_name'] = $_COOKIE['user_name'];
   header("Location: ../includes/mainweb.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST"){
    // 1. LOGIN LOGIC
    if (isset($_POST['action']) && $_POST['action'] == 'login') {
        $email = trim($_POST['up_email']);
        $password = trim($_POST['password']);

        $stmt = $conn->prepare("SELECT user_id, full_name, password FROM users WHERE up_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            // Case A: Email does not exist in the database
            $error_message = "This account is not registered. Pls do signin first.";
        } elseif (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];

            // --- REMEMBER ME LOGIC START ---
            if (isset($_POST['remember'])) {
                setcookie("user_id", $user['user_id'], time() + (86400 * 30), "/", "", false, true);
                setcookie("user_name", $user['full_name'], time() + (86400 * 30), "/", "", false, true);
            }
            // --- REMEMBER ME LOGIC END ---

            header("Location: ../includes/mainweb.php");
            exit();
        } else {
            // Case C: Email exists but password is wrong
            $error_message = "Invalid password. Please try again.";
        }
    }

    // 2. SIGNUP LOGIC
    if (isset($_POST['action']) && $_POST['action'] == 'signup') {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['up_email']);
        $plain_password = $_POST['password'];
        $confirm_password = $_POST['password_confirmation']; 

        if (empty($full_name)) {
            $error_message = "Full Name is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Valid email is required.";
        } elseif (!str_ends_with($email, '@up.edu.ph')) {
            $error_message = "Must use a @up.edu.ph email!";
        } elseif (strlen($plain_password) < 8) {
            $error_message = "Password is too short! It must be at least 8 characters.";
        } elseif (strlen($plain_password) > 72) {
            $error_message = "Password is too long! Max limit is 72 characters.";
        } elseif (!preg_match("/[a-z]/i", $plain_password)) {
            $error_message = "Password must contain at least one letter.";
        } elseif (!preg_match("/[0-9]/", $plain_password)) {
            $error_message = "Password must contain at least one number.";
        } elseif ($plain_password !== $confirm_password) {
            $error_message = "Passwords do not match!";
        } else {
            // --- ALL CHECKS PASSED, PROCEED TO DATABASE ---
            try {
                $password_hashed = password_hash($plain_password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO users (full_name, up_email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $full_name, $email, $password_hashed);
                
                if ($stmt->execute()) {
                    $success_message = "Account created! You can now login.";
                }
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) {
                    $alert_trigger = "exists";
                } else {
                    $error_message = "Error: " . $e->getMessage();
                }
            }   
        } 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="loginpanel.css">
    <title>UPMart - Login & Signup</title>
</head>
<body>
    <div class="main-container">
        <div class="form-container">
            
            <?php if (isset($_GET['reset']) && $_GET['reset'] == 'complete'): ?>
                <div style="background-color: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a5d6a7; text-align: center;">
                    <strong>Password Updated!</strong><br>
                    Your new password is ready. You can now log in.
                </div>
            <?php endif; ?>

            <div class="forms-slider" id="formsSlider">
                
                <form class="form-section" action="login.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="brand">
                        <img src="../images/logo.png" class="logo-image" alt="UPMart Logo">
                    </div>

                    <?php if($error_message && $_POST['action'] == 'login') echo "<p style='color:red; font-size:0.8rem;'>$error_message</p>"; ?>
                    <?php if($success_message) echo "<p style='color:green; font-size:0.8rem;'>$success_message</p>"; ?>

                    <div class="input-group">
                        <input 
                            type="email" 
                            name="up_email" 
                            placeholder="UP Email (@up.edu.ph)" 
                            pattern=".+@up\.edu\.ph" 
                            title="Please use your official UP email address (e.g., name@up.edu.ph)"
                            required
                        >
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <button type="submit" class="login-btn">LOGIN</button>    

                    <div class="form-footer">
                        <label class="remember-me">
                            <input type="checkbox" name="remember"> Remember me
                        </label><br><br>
                        <a href="forgotpass.php" class="forgot-link">Forgot password?</a>
                    </div>
                </form>

                <form class="form-section" action="login.php" method="POST">
                    <input type="hidden" name="action" value="signup">

                    <div class="brand">
                        <img src="../images/logo.png" class="logo-image" alt="UPMart Logo">
                    </div>

                     <?php if ($error_message && $_POST['action'] == 'signup'): ?>
                        <div style="background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.85rem; border: 1px solid #ef9a9a;">
                          <strong>Error:</strong> <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <div class="input-group">
                        <h2 style="margin-bottom: 10px; color: #1a1a2e; text-align: center;">Create Account</h2>
                        <input type="text" name="full_name" placeholder="Full Name" required>
                        <input 
                            type="email" 
                            name="up_email" 
                            placeholder="UP Email (@up.edu.ph)" 
                            pattern=".+@up\.edu\.ph" 
                            title="Please use your official UP email address (e.g., name@up.edu.ph)"
                            required
                        >
                        <input 
                            type="password" 
                            name="password" 
                            placeholder="Create Password" 
                            minlength="8" 
                            maxlength="72" 
                            required
                        >
                        <input 
                            type="password" 
                            name="password_confirmation" 
                            placeholder="Confirm Password" 
                            minlength="8" 
                            maxlength="72" 
                            required
                        >   
                    </div>
                    <button type="submit" class="login-btn">SIGN UP</button>

                    <p style="color: #333; font-size: 1rem; margin-top: 20px;">
                        Already a member? <button type="button" class="switch-btn" onclick="toggleForm()">Login</button>
                    </p>
                </form>

            </div>
        </div>

        <div class="visual-container">
            <nav class="top-nav">
                <a href="about.php" class="nav-link">ABOUT</a>
                <a href="contact.php" class="nav-link">CONTACT</a>
                <button onclick="toggleForm()" class="nav-link" style="background:none; border: 1px solid white; padding: 4px 12px; border-radius: 4px; cursor:pointer; color:white;">SIGN UP</button>
            </nav>

            <div class="content-row">
                <div class="welcome-text">
                    <h1>Welcome.</h1>
                    <p>Ready to elevate your business? Join the UPMart today.</p>
                    <p>Not a member? <button class="switch-btn" onclick="toggleForm()" style="color:white; background:none; border:none; text-decoration:underline; cursor:pointer;">Sign up now</button></p>
                </div>
                <div class="cart-wrapper">
                    <img src="../images/cart-icon.png" alt="Cart" class="cart-image">
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleForm() {
            const slider = document.getElementById('formsSlider');
            slider.classList.toggle('slide-active');
        }

        // NEW: Auto-slide to Signup if there's a signup error
        <?php if ($error_message && $_POST['action'] == 'signup'): ?>
        window.onload = function() {
            const slider = document.getElementById('formsSlider');
            slider.classList.add('slide-active');
        };
        <?php endif; ?>
        
    </script>
</body>
</html>
