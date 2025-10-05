<?php
// Don't start session here - it's already started in signup.php

// Include Composer autoloader (recommended approach)
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Database connection
$host = '127.0.0.1:3306';
$dbname = 'btonedatabase';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ==================== EMAIL CONFIGURATION ====================
$smtp_host = 'smtp.gmail.com';
$smtp_username = 'lanceaeronm@gmail.com';     // Your Gmail
$smtp_password = 'ayafwvtojufvfzrf';          // Your App Password (without spaces)
$smtp_port = 587;
$from_email = 'lanceaeronm@gmail.com';        // Your Gmail
$from_name = 'BTone Events';
// ==================== END CONFIGURATION ====================

$message = "";
$isSuccess = false;

// Handle OTP verification
if (isset($_POST['verify_otp'])) {
    $email = $_POST['email'];
    $entered_otp = $_POST['otp'];
    
    // Retrieve stored OTP from session
    if (isset($_SESSION['signup_otp']) && 
        isset($_SESSION['signup_otp_email']) && 
        $_SESSION['signup_otp_email'] === $email &&
        $_SESSION['signup_otp'] === $entered_otp) {
        
        // OTP verified, create the user account
        $firstName = $_SESSION['signup_data']['firstname'];
        $lastName = $_SESSION['signup_data']['lastname'];
        $phoneNumber = $_SESSION['signup_data']['phonenumber'];
        $hashedPassword = $_SESSION['signup_data']['password'];
        
        try {
            $insertQuery = "INSERT INTO btuser (bt_first_name, bt_last_name, bt_email, bt_phone_number, bt_password_hash, bt_privilege_id) 
                           VALUES (?, ?, ?, ?, ?, 2)";
            
            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->execute([$firstName, $lastName, $email, $phoneNumber, $hashedPassword]);
            
            if ($insertStmt->rowCount() > 0) {
                $message = "Account created successfully! Welcome to BTone Events!";
                $isSuccess = true;
                
                // Clear session data
                unset($_SESSION['signup_otp']);
                unset($_SESSION['signup_otp_email']);
                unset($_SESSION['signup_data']);
                
                // Redirect to login after successful verification
                header("Refresh: 3; url=login.php");
            }
        } catch (PDOException $e) {
            $message = "Error creating account: " . $e->getMessage();
            $isSuccess = false;
        }
    } else {
        $message = "Invalid OTP code. Please try again.";
        $isSuccess = false;
    }
}

// Handle cancel and resend requests
if (isset($_GET['cancel'])) {
    unset($_SESSION['signup_otp']);
    unset($_SESSION['signup_otp_email']);
    unset($_SESSION['signup_data']);
    header("Location: signup.php");
    exit;
}

if (isset($_GET['resend'])) {
    if (isset($_SESSION['signup_otp_email']) && isset($_SESSION['signup_data'])) {
        $email = $_SESSION['signup_otp_email'];
        $fullname = $_SESSION['signup_data']['firstname'] . ' ' . $_SESSION['signup_data']['lastname'];
        $otp = $_SESSION['signup_otp'];
        
        if (sendOtpEmail($email, $otp, $fullname)) {
            $message = "Verification code resent to your email!";
            $isSuccess = true;
        } else {
            $message = "Failed to resend OTP. Please try again.";
            $isSuccess = false;
        }
    }
}

// Handle initial signup form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];
    $firstName = trim($_POST['firstname']);
    $lastName = trim($_POST['lastname']);
    $phoneNumber = trim($_POST['Phonenumber']);

    // Validate passwords match
    if ($password !== $confirmPassword) {
        $message = "Passwords do not match!";
        $isSuccess = false;
    } 
    // Validate phone number (11 digits)
    elseif (!preg_match('/^\d{11}$/', $phoneNumber)) {
        $message = "Please enter a valid 11-digit phone number!";
        $isSuccess = false;
    }
    // Validate email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address!";
        $isSuccess = false;
    }
    else {
        try {
            // Check if email already exists
            $checkEmailQuery = "SELECT bt_email FROM btuser WHERE bt_email = ?";
            $checkStmt = $pdo->prepare($checkEmailQuery);
            $checkStmt->execute([$email]);
            
            if ($checkStmt->rowCount() > 0) {
                $message = "Email already exists! Please use a different email address.";
                $isSuccess = false;
            } else {
                // Generate OTP (6-digit code)
                $otp = sprintf("%06d", mt_rand(1, 999999));
                
                // Store OTP and user data in session
                $_SESSION['signup_otp'] = $otp;
                $_SESSION['signup_otp_email'] = $email;
                $_SESSION['signup_data'] = [
                    'firstname' => $firstName,
                    'lastname' => $lastName,
                    'phonenumber' => $phoneNumber,
                    'password' => password_hash($password, PASSWORD_DEFAULT)
                ];
                
                // Send OTP email
                $fullname = $firstName . ' ' . $lastName;
                if (sendOtpEmail($email, $otp, $fullname)) {
                    $message = "OTP verification code sent to your email!";
                    $isSuccess = true;
                } else {
                    $message = "Failed to send OTP. Please check your email configuration.";
                    $isSuccess = false;
                    // Clear session if email fails
                    unset($_SESSION['signup_otp']);
                    unset($_SESSION['signup_otp_email']);
                    unset($_SESSION['signup_data']);
                }
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $isSuccess = false;
        }
    }
}

// Function to send OTP email using PHPMailer
function sendOtpEmail($email, $otp, $fullname) {
    global $smtp_host, $smtp_username, $smtp_password, $smtp_port, $from_email, $from_name;
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtp_port;
        
        // Enable verbose debug output (optional - remove in production)
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        
        // Recipients
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($email, $fullname);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'BTone Events - Email Verification Code';
        $mail->Body = getEmailTemplate($otp, $fullname);
        $mail->AltBody = "Your BTone Events verification code is: $otp\n\nEnter this code to complete your registration.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Function to generate email template
function getEmailTemplate($otp, $fullname) {
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Email Verification</title>
        <style>
            body {
                font-family: 'Arial', sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 30px;
                text-align: center;
                color: white;
            }
            .header h1 {
                margin: 0;
                font-size: 28px;
                font-weight: bold;
            }
            .content {
                padding: 40px 30px;
                text-align: center;
            }
            .otp-code {
                font-size: 48px;
                font-weight: bold;
                color: #667eea;
                letter-spacing: 8px;
                margin: 30px 0;
                padding: 20px;
                background-color: #f8f9fa;
                border-radius: 8px;
                border: 2px dashed #667eea;
            }
            .welcome-text {
                font-size: 18px;
                color: #333;
                margin-bottom: 20px;
                line-height: 1.6;
            }
            .instructions {
                background-color: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                margin: 25px 0;
                text-align: left;
            }
            .instructions h3 {
                color: #667eea;
                margin-top: 0;
            }
            .footer {
                background-color: #f8f9fa;
                padding: 20px;
                text-align: center;
                color: #666;
                font-size: 14px;
            }
            .highlight {
                color: #667eea;
                font-weight: bold;
            }
            .note {
                background-color: #fff3cd;
                border: 1px solid #ffeaa7;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
                color: #856404;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Welcome to BTone Events! 🎉</h1>
            </div>
            
            <div class='content'>
                <div class='welcome-text'>
                    Hello <span class='highlight'>$fullname</span>,<br>
                    Thank you for choosing BTone Events for your special occasions!
                </div>
                
                <div class='note'>
                    <strong>Important:</strong> To complete your registration and start planning your perfect event, please verify your email address using the code below:
                </div>
                
                <div class='otp-code'>$otp</div>
                
                <div class='instructions'>
                    <h3>📝 How to Verify Your Email:</h3>
                    <ol>
                        <li>Copy the 6-digit verification code above</li>
                        <li>Return to the BTone Events registration page</li>
                        <li>Enter the code in the OTP verification field</li>
                        <li>Click 'Verify & Create Account' to complete your registration</li>
                    </ol>
                </div>
                
                <div style='color: #666; font-size: 14px; margin-top: 25px;'>
                    <strong>This code will expire in 10 minutes</strong><br>
                    If you didn't request this verification, please ignore this email.
                </div>
            </div>
            
            <div class='footer'>
                <p>✨ <strong>BTone Events</strong> - Creating Unforgettable Moments ✨</p>
                <p>📍 Event Planning & Catering Services</p>
                <p>📧 If you need assistance, contact our support team</p>
                <p style='margin-top: 15px; font-size: 12px; color: #999;'>
                    This is an automated message. Please do not reply to this email.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>