<?php
    include '../db_connect.php';    
    session_start();

    $error_message = "";
    $success_message = "";

    // Cookie Check (Remember Me)
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About | UPMart</title>
    <link rel="stylesheet" href="index-panel.css">
</head>
<body>
    <div class="main-container">
        <div class="form-container">
            <div class="forms-slider" id="formsSlider">
               <form class="form-section" action="about.php" method="POST">
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
                <a href="about.php" class="nav-link" style="opacity: 1; border: 1px solid white; padding: 4px 12px; border-radius: 4px;">ABOUT</a>
                <a href="contact.php" class="nav-link">CONTACT</a>
                <a href="../index.php" class="nav-link">SIGN UP</a>
            </nav>

            <div class="content-row">
                <div class="about-text">
                    <h1>What is UPMart?</h1>
                    <p style="margin-right: 110px;">UP Mindanao students currently navigate a disorganized Facebook-based marketplace that makes finding essential goods difficult and necessitates expensive trips to downtown Davao. The UPMART project will resolve this by launching a dedicated web application featuring categorized listings and dynamic filters to create a streamlined, efficient, and exclusive campus trade experience.</p>
                </div>
                <div class="cart-wrapper">
                    <img src=../images/cart-icon.png alt="Shopping Cart" class="cart-image">
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