<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail($email, $code) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'lanceaeronm@gmail.com'; // Your Gmail
        $mail->Password = 'zjbllgynxudvqjqt'; // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Recipients
        $mail->setFrom('lanceaeronm@gmail.com', 'Btone Registration');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification Code';
        $mail->Body = "<h2>Welcome to Btone!</h2>
                      <p>Your verification code is: <strong>{$code}</strong></p>
                      <p>This code will expire in 10 minutes.</p>";
        $mail->AltBody = "Your verification code is: {$code}\nThis code will expire in 10 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>