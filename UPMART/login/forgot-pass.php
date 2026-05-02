<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | UPMart</title>
    <link rel="stylesheet" href="index-panel.css">
    
    <style>
        /* Internal CSS to center the layout specifically for this page */
        body, html {
            height: 100%;
            width: 100%;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background: radial-gradient(at top left, #dfe378, transparent),
                radial-gradient(at bottom right, #9a0000, transparent),
                radial-gradient(at center, #f8f8f3, #310a26);
            font-family: 'Inter', 'Montserrat', sans-serif;
            overflow: hidden;
        }

        /* Overriding the massive widths from the external file */
        .main-container {
            width: 90% !important;
            max-width: 450px !important; 
            height: auto !important;
            min-height: 400px;
            background: #ffffff;
            border-radius: 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
            animation: fadeInPage 0.4s ease-out;
        }

        @keyframes fadeInPage {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-card-content {
            width: 100%;
            text-align: center;
        }

        .logo-image {
            width: 120px;
            height: auto;
            margin-bottom: 20px;
            /* Removed the scale: 2.3 to prevent blurring */
        }

        h2 {
            color: #1a1a2e;
            margin-bottom: 15px;
            font-size: 1.8rem;
        }

        .description {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 25px;
        }

        /* Styling the inputs inside the internal block */
        .input-group input {
            width: 100%;
            padding: 14px 20px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 25px;
            font-size: 1rem;
            outline: none;
        }

        .input-group input:focus {
            border-color: #310a26;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: #1a1a2e;
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1rem;
            transition: background 0.3s ease;
        }

        .login-btn:hover {
            background: #310a26;
        }

        .footer-link-text {
            margin-top: 25px;
            font-size: 0.9rem;
            color: #333;
        }

        .footer-link-text a {
            color: #1a1a2e;
            text-decoration: none;
            font-weight: 700;
        }

        .footer-link-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="main-container">
        <div class="form-card-content">
            <div class="brand">
                <img src="../images/logo.png" class="logo-image" alt="UPMart Logo">
            </div>
            
            <h2>Forgot Password?</h2>
            <p class="description">
                Enter your UP email and we'll send you a secure link to reset your password.
            </p>
            
            <form method="post" action="send-password-reset.php">
                <div class="input-group">
                    <input 
                        type="email" 
                        name="up_email" 
                        placeholder="UP Email (@up.edu.ph)" 
                        pattern=".+@up\.edu\.ph" 
                        required
                    >
                </div>
                <button type="submit" class="login-btn">Send Reset Link</button>
            </form>

            <p class="footer-link-text">
                Remembered it? <a href="../index.php">Back to Login</a>
            </p>
        </div>
    </div>

</body>
</html>