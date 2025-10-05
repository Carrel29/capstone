<?php
session_start();
require "../includes/signUpFunction.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../CSS/style.css" />
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Comfortaa:wght@400;700&family=M+PLUS+Rounded+1c:wght@400;700&display=swap"
        rel="stylesheet">
    <title>Sign up - BTone Events</title>
</head>

<body>
    <main class="combo-display-flex h-100">
        <div class="card w-35">
            <div class="card-content w-100">
                <div class="combo-display-flex flex-column header">
                    <img src="../Img/Login/boy.png" alt="BTone Events">
                    <h1>Create Your Account</h1>
                    <p style="color: #666; text-align: center; margin-top: 10px;">Join BTone Events and start planning your perfect occasion!</p>
                </div>

                <?php if (!isset($_SESSION['signup_otp'])): ?>
                <!-- Initial Signup Form -->
                <form action="signup.php" method="post">
                    <input type="hidden" name="signup" value="1">
                    
                    <div class="combo-display-flex-column-start">
                        <label for="email">Email Address:</label>
                        <input type="email" name="email" id="email" autocomplete="off" placeholder="example@sample.com"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    <div class="combo-display-flex-column-start">
                        <label for="password">Password:</label>
                        <input type="password" name="password" id="password" autocomplete="off"
                            placeholder="Create a strong password" required>
                    </div>
                    <div class="combo-display-flex-column-start">
                        <label for="confirm-password">Confirm Password:</label>
                        <input type="password" name="confirm-password" id="confirm-password" autocomplete="off"
                            placeholder="Re-enter your password" required>
                    </div>
                    <div class="combo-display-flex-column-start">
                        <label for="firstname">First Name:</label>
                        <input type="text" name="firstname" id="firstname" autocomplete="off" placeholder="John"
                            value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>" required>
                    </div>
                    <div class="combo-display-flex-column-start">
                        <label for="lastname">Last Name:</label>
                        <input type="text" name="lastname" id="lastname" autocomplete="off" placeholder="Doe"
                            value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>" required>
                    </div>
                    <div class="combo-display-flex-column-start">
                        <label for="Phonenumber">Phone Number:</label>
                        <input type="text" name="Phonenumber" id="Phonenumber" autocomplete="off"
                            placeholder="ex. 09292304196" pattern="\d{11}" title="Please enter 11-digit phone number"
                            value="<?php echo isset($_POST['Phonenumber']) ? htmlspecialchars($_POST['Phonenumber']) : ''; ?>" required>
                    </div>
                    <div class="combo-display-flex-space-between">
                        <input type="submit" value="Send Verification Code" class="w-20">
                        <a href="login.php">Already have an account?</a>
                    </div>
                </form>

                <?php else: ?>
                <!-- OTP Verification Form -->
                <form action="signup.php" method="post">
                    <input type="hidden" name="verify_otp" value="1">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['signup_otp_email']); ?>">
                    
                    <div class="combo-display-flex-column-start" style="text-align: center; margin-bottom: 20px;">
                        <h3 style="color: #667eea;">📧 Email Verification Required</h3>
                        <p>We've sent a 6-digit verification code to:<br>
                        <strong><?php echo htmlspecialchars($_SESSION['signup_otp_email']); ?></strong></p>
                    </div>
                    
                    <div class="combo-display-flex-column-start">
                        <label for="otp">Enter Verification Code:</label>
                        <input type="text" name="otp" id="otp" autocomplete="off" placeholder="Enter 6-digit code"
                            pattern="[0-9]{6}" title="Please enter 6-digit code" maxlength="6" required
                            style="text-align: center; font-size: 18px; letter-spacing: 8px;">
                    </div>
                    
                    <div class="combo-display-flex-space-between">
                        <input type="submit" value="Verify & Create Account" class="w-20">
                        <a href="signup.php?cancel=1" style="color: #ff6b6b;">Cancel</a>
                    </div>
                    
                    <div style="text-align: center; margin-top: 15px; font-size: 14px; color: #666;">
                        Didn't receive the code?<br>
                        Check your spam folder or <a href="signup.php?resend=1">Resend Code</a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Toast Notification -->
    <?php if ($message): ?>
    <div class="toast <?php echo $isSuccess ? 'bg-green' : 'bg-red'; ?>">
        <div class="toast-body">
            <?php echo htmlspecialchars($message); ?>
        </div>
    </div>
    <?php endif; ?>

    <script src="../JS/toast.js"></script>
    <script src="../JS/global.js"></script>
    <script>
        // Auto-hide toast after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.querySelector('.toast');
            if (toast) {
                setTimeout(() => {
                    toast.classList.add('d-none');
                }, 5000);
            }
            
            // Auto-focus OTP input
            const otpInput = document.getElementById('otp');
            if (otpInput) {
                otpInput.focus();
            }
        });
    </script>
</body>

</html>