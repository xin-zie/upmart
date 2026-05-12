<?php
session_start();

// Check if a session actually exists before trying to destroy it
if (isset($_SESSION['user_id'])) {
    // 1. Clear session data
    $_SESSION = array();

    // 2. Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // 3. Destroy the session
    session_destroy();
    
    // We don't redirect; we just let the script continue to the HTML below
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out | UPMart</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            /* We use a single background property and set it to cover the whole screen */
            background: radial-gradient(at top left, #dfe378, transparent),
                        radial-gradient(at bottom right, #9a0000, transparent),
                        radial-gradient(at center, #f8f8f3, #310a26);
            background-size: cover;
            background-attachment: fixed; /* This prevents the 'tiling' effect */
            font-family: 'Inter', sans-serif;
            overflow: hidden;
        }

        .logout-card {
            background: white;
            padding: 40px;
            border-radius: 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 400px;
            width: 90%;
            position: relative; /* Ensures it stays on top of the background */
            z-index: 10;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            background: #f8d7da;
            color: #9a0000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .icon-circle span { font-size: 40px; }

        h2 { color: #1a1a2e; margin-bottom: 10px; }
        p { color: #666; margin-bottom: 30px; line-height: 1.6; }

        .login-btn {
            display: inline-block;
            text-decoration: none;
            background: #1a1a2e;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: bold;
            transition: 0.3s;
        }

        .login-btn:hover {
            background: #9a0000;
            transform: scale(1.05);
        }

        .auto-redirect {
            margin-top: 20px;
            font-size: 0.8rem;
            color: #999;
        }
    </style>
</head>
<body>

    <div class="logout-card">
        <div class="icon-circle">
            <span class="material-icons">lock_reset</span>
        </div>
        <h2>Successfully Logged Out</h2>
        <p>Your session has been securely ended. Thank you for using UPMart!</p>
        
        <a href="../index.php" class="login-btn">Login Again</a>

        <div class="auto-redirect">
            Redirecting to login in <span id="timer">5</span> seconds...
        </div>
    </div>

    <script>
        let timeLeft = 5;
        const timerElement = document.getElementById('timer');
        
        setInterval(() => {
            timeLeft--;
            if (timeLeft >= 0) {
                timerElement.textContent = timeLeft;
            }
            if (timeLeft === 0) {
                // Ensure this path matches your folder structure
                window.location.href = "../index.php";
            }
        }, 1000);
    </script>
</body>
</html>