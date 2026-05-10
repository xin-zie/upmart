<?php
include '../db_connect.php';
session_start();

$error_message = "";
$success_message = "";

if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    $c_id = $_COOKIE['user_id'];
    
    // Check if the user is still active in the database
    $stmt = $conn->prepare("SELECT user_id, full_name, role, account_status FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $c_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Only allow if user exists AND is NOT banned
    if ($user && $user['account_status'] !== 'Banned') {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        if ($_SESSION['role'] === 'admin') {
            header("Location: admin/admin_main.php");
        } else {
            header("Location: dashboard/mainweb.php");
        }
        exit();
    } else {
        // Clear cookies if banned or user not found
        setcookie("user_id", "", time() - 3600, "/");
        if($user && $user['account_status'] === 'Banned') {
            $error_message = "Your session has expired because your account is banned.";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. LOGIN LOGIC (For the sidebar)
    if (isset($_POST['action']) && $_POST['action'] == 'login') {
        $email = trim($_POST['up_email']);
        $password = trim($_POST['password']);

        // --- 1. UPDATED LOGIN QUERY (Added account_status) ---
        $stmt = $conn->prepare("SELECT user_id, full_name, password, role, account_status FROM users WHERE up_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user['account_status'] === 'Banned') {
            $error_message = "Access Denied: Your account has been permanently banned.";
        } elseif (!$user) {
            $error_message = "No account found with that email.";
        } else {
            // --- 3. ADDED BAN CHECK ---
            if (!$user) {
                $error_message = "This account is not registered.";
            } else {
                // 2. CHECK if the user is banned BEFORE verifying password or setting sessions
                if ($user['account_status'] === 'Banned') {
                    $error_message = "Access Denied: Your account has been permanently banned.";
                } 
                elseif (password_verify($password, $user['password'])) {
                    // Proceed with login...
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header("Location: admin/admin_main.php");
                    } else {
                        header("Location: dashboard/mainweb.php");
                    }
                    exit();
                } else {
                    $error_message = "Invalid password.";
                }
            }
        }
    }
    
    // 2. CONTACT FORM LOGIC (For the message form on the right)
    if (isset($_POST['contact_name'])) {
        // Here you could save the message to a 'Contacts' table
        $success_message = "Thank you, " . htmlspecialchars($_POST['contact_name']) . "! Your message has been sent.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'send_inquiry') {
    $name = $_POST['contact_name'];
    $email = $_POST['contact_email'];
    $msg = $_POST['contact_message'];

    $stmt = $conn->prepare("INSERT INTO inquiries (name, email, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $msg);

    if ($stmt->execute()) {
        $success_message = "Your inquiry has been sent to the admin!";
    } else {
        $error_message = "Something went wrong. Please try again.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact | UPMart</title>
    <link rel="stylesheet" href="index-panel.css">   
</head>
<body>
    <div class="main-container">
        <div class="form-container">
            <div class="forms-slider" id="formsSlider">
               <form class="form-section" action="login.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="brand">
                        <img src="../images/logo.png" class="logo-image" alt="UPMart Logo">
                    </div>

                    <?php if($error_message): ?>
                        <p style="color:red; font-size:0.8rem; text-align:center;"><?php echo $error_message; ?></p>
                    <?php endif; ?>

                    <?php if($success_message): ?>
                        <p style="color:green; font-weight:bold;"><?php echo $success_message; ?></p>
                    <?php endif; ?>

                    <div class="input-group">
                        <input type="email" name="up_email" placeholder="UP Email (@up.edu.ph)" required>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <button type="submit" class="login-btn">LOGIN</button>    

                    <div class="form-footer">
                        <label class="remember-me"><input type="checkbox"> Remember me</label><br><br>
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="visual-container">
            <nav class="top-nav">
                <a href="about.php" class="nav-link">ABOUT</a>
                <a href="contact.php" class="nav-link" style="opacity: 1; border: 1px solid white; padding: 4px 12px; border-radius: 4px;">CONTACT</a>
                <a href="../index.php" class="nav-link">SIGN UP</a>
            </nav>

            <div class="content-row">
                <div class="about-text">
                    <h1>Contact Us</h1>
                    <p>If you have any questions or feedback, feel free to reach out to us using the information below:</p>
                    <ul>
                        <li>Email: pcmahusay@up.edu.ph</li>
                        <li>Phone: 09758365262</li>
                        <li>Address: University of the Philippines Mindanao, Brgy. Mintal, Davao City</li>
                    </ul>
                </div>
                <div class="cart-wrapper">
                    <form action="contact.php" method="POST">
                        <p style="margin-bottom: 20px;">Or you can fill out the contact form below:</p>
                        <input type="text" name="contact_name" placeholder="Your Name" style="width: 100%; padding: 10px; margin-bottom: 10px; border-radius: 5px; border: 1px solid #333;" required>
                        <input type="email" name="contact_email" placeholder="Your Email" style="width: 100%; padding: 10px; margin-bottom: 10px; border-radius: 5px; border: 1px solid #333;" required>
                        <textarea name="contact_message" placeholder="Your Message" style="width: 100%; padding: 10px; margin-bottom: 10px; border-radius: 5px; border: 1px solid #333;" required></textarea>
                        <input type="hidden" name="action" value="send_inquiry">
                        <button type="submit" style="padding: 10px 20px; background-color: #1a1a2e; color: white; border-radius: 5px; cursor: pointer;">Send Message</button>
                    </form> 
                </div>
            </div>
        </div>
    </div>
    <script>
        function toggleForm() {
            const slider = document.getElementById('formsSlider');
            slider.classList.toggle('slide-active');
        }
    </script>
</body>
</html>