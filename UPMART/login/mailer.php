<?php

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    require __DIR__ . "/../vendor/autoload.php";

    $mail = new PHPMailer(true);

    // --- ADDED DEBUGGING CODE START ---
    // This will echo the full "conversation" between your code and Gmail.
    //$mail->SMTPDebug = SMTP::DEBUG_SERVER; 
    // --- ADDED DEBUGGING CODE END ---

    $mail->isSMTP();
    $mail->SMTPAuth = true;

    $mail->Host = "smtp.gmail.com";
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->Username = "libysshayzey@gmail.com"; // Ensure this is your real email
    $mail->Password = "rnmcdhinmjmilhsx"; // Ensure no spaces here

    $mail->isHtml(true);

    return $mail;
?>