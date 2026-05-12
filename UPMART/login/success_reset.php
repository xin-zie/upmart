<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Sent | UPMart</title>
    <link rel="stylesheet" href="index-panel.css">
    <style>
        .success-box {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 90%;
        }
        .success-icon {
            font-size: 50px;
            color: #2e7d32;
            margin-bottom: 20px;
        }
        .btn-back {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 30px;
            background-color: #1a1a2e;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: 0.3s;
        }
        .btn-back:hover {
            background-color: #16213e;
            transform: translateY(-2px);
        }
    </style>
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color:  #310a26;; margin: 0; font-family: 'Poppins', sans-serif;">

    <div class="success-box">
        <div class="brand">
            <img src="../images/logo.png" style="width: 80px; scale: 1.9;" alt="UPMart Logo">
        </div>
        
        <div class="success-icon" style="font-weight: bold; font-size: 1.8rem; margin-bottom: 40px;">Check your Inbox! 📩</div>
        
        <h2 style="color: #1a1a2e; margin-bottom: 10px;">Reset Link Sent</h2>
        
        <p style="color: #666; line-height: 1.6;">
            We've sent a secure password reset link to your <strong>UP email address</strong>. 
            Please check your inbox and follow the instructions to regain access.
        </p>

        <p style="font-size: 0.85rem; color: #888; margin-top: 15px;">
            Don't see it? Check your <strong>Spam folder</strong> just in case!
        </p>

        <a href="../index.php" class="btn-back">Back to Login</a>
    </div>

</body>
</html>