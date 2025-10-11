<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_destroy();
    session_unset();
    header("Location: index.php");
    exit();
}

// Database Configuration
$host = 'localhost';
$dbname = 'btonedatabase'; 
$username = 'root';
$password = '';

// If user is already logged in, redirect based on role
if (isset($_SESSION['bt_user_id'])) {
    if ($_SESSION['role'] == 'ADMIN') {
        header("Location: dashboard.php");
        exit();
    } elseif ($_SESSION['role'] == 'USER') {
        header("Location: employee_dashboard.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $email = $_POST['email'];
        $password = $_POST['password'];

        // Fetch user with privilege
        $stmt = $pdo->prepare("
        SELECT u.*, p.bt_privilege_name
        FROM btuser u
        JOIN btuserprivilege p ON u.bt_privilege_id = p.bt_privilege_id
        WHERE u.bt_email = :email
        ");
        
       $stmt->execute(['email' => $email]);
       $user = $stmt->fetch(PDO::FETCH_ASSOC);
    

        // Verify password using bcrypt

        if ($user && isset($user['bt_password_hash']) && password_verify($password, $user['bt_password_hash'])){
            $_SESSION['user_logged_in'] = true;
            $_SESSION['bt_user_id'] = $user['bt_user_id'];
            $_SESSION['role'] = $user['bt_privilege_name'];
       
            // Redirect based on role
            if ($_SESSION['role'] == 'ADMIN') {
                header("Location: dashboard.php");
            } else {
                header("Location: employee_dashboard.php");
            }
            exit();
        
        } else {
        $error = "Invalid email or password";
    }
        } catch(PDOException $e) {
        $error = "Connection Failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>BTONE - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #eae7de;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 8px 25px rgba(107, 65, 30, 0.15);
            width: 100%;
            max-width: 420px;
            border: 1px solid #d7ccc8;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #A08963;
        }

        .logo h2 {
            color: #422b0d;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .logo p {
            color: #8a745a;
            font-size: 14px;
            font-weight: 500;
        }

        .input-container {
            position: relative;
            margin-bottom: 20px;
        }

        .input-container input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #d7ccc8;
            border-radius: 8px;
            font-size: 15px;
            background: #faf9f7;
            transition: all 0.3s ease;
            outline: none;
            color: #422b0d;
        }

        .input-container input:focus {
            border-color: #A08963;
            background: white;
            box-shadow: 0 0 0 3px rgba(160, 137, 99, 0.1);
        }

        .input-container input::placeholder {
            color: #8a745a;
        }

        .password-container {
            position: relative;
            margin-bottom: 25px;
        }

        .password-container input {
            width: 100%;
            padding: 14px 45px 14px 16px;
            border: 2px solid #d7ccc8;
            border-radius: 8px;
            font-size: 15px;
            background: #faf9f7;
            transition: all 0.3s ease;
            outline: none;
            color: #422b0d;
        }

        .password-container input:focus {
            border-color: #A08963;
            background: white;
            box-shadow: 0 0 0 3px rgba(160, 137, 99, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #8a745a;
            font-size: 15px;
            transition: color 0.3s ease;
            padding: 4px;
            border-radius: 4px;
        }

        .toggle-password:hover {
            color: #6b411e;
            background: rgba(160, 137, 99, 0.1);
        }

        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background: #6b411e;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        button[type="submit"]:hover {
            background: #8a745a;
            transform: translateY(-1px);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }

        .error-message {
            background: #f8d7da;
            border: 1px solid #f1aeb5;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
        }

        .login-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            margin-top: 20px;
            border-left: 4px solid #A08963;
        }

        .login-info strong {
            display: block;
            color: #422b0d;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .login-info p {
            color: #6b411e;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
        }

        .role-badge {
            display: inline-block;
            background: #A08963;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-right: 5px;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #8a745a;
            font-size: 15px;
        }

        .input-container.with-icon input {
            padding-left: 45px;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 25px;
                margin: 10px;
            }
            
            body {
                padding: 10px;
            }
            
            .logo h2 {
                font-size: 28px;
            }
        }

        /* Loading state */
        button[type="submit"].loading {
            pointer-events: none;
            opacity: 0.8;
        }

        button[type="submit"].loading::after {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="logo">
            <h2>BTONE</h2>
            <p>Event Management System</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="loginForm">
            <div class="input-container with-icon">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" id="email" name="email" placeholder="Enter your email" required autocomplete="off">
            </div>
            
            <div class="password-container">
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <button type="button" class="toggle-password" id="togglePassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            
            <button type="submit" id="loginButton">
                <i class="fas fa-sign-in-alt"></i> Log In
            </button>
        </form>
        
        <div class="login-info">
            <strong>Role-based Access</strong>
            <p>
                <span class="role-badge">Admin</span> Full system access<br>
                <span class="role-badge">Employee</span> Calendar view only
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('togglePassword');
            const loginButton = document.getElementById('loginButton');
            const loginForm = document.getElementById('loginForm');
            
            // Toggle password visibility
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Change icon
                const icon = togglePassword.querySelector('i');
                icon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
            });
            
            // Add loading state to form submission
            loginForm.addEventListener('submit', function() {
                loginButton.classList.add('loading');
                loginButton.innerHTML = '<i class="fas fa-spinner"></i> Signing In...';
            });
        });
    </script>
</body>

</html>