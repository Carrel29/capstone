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
    exit();}
}

// Get all users for the dropdown
$users = [];
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("
    SELECT bt_user_id, bt_email, bt_first_name, bt_last_name FROM btuser
    ");

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Connection failed: " . $e->getMessage();
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
       

        // All users go to Dashboard
        header("Location: dashboard.php");
        exit();
        
        }else{
        $error = "invalid credentials";
    }
        }catch(PDOException $e) {
        $error = "Connection Failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Admin Login</title>
    <link rel="stylesheet" href="assets_css/Login.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {

            background-size: 100% 95%;  /* Changed from 'cover' to '100% 100%' */
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .login-container {
            background-color: #A08963;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            position: relative;
        }

        .login-container h2 {
            color: white;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #715c42;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button[type="submit"]:hover {
            background-color: #5d4c37;
        }

        .input-container {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 10;
            display: none;
        }
        
        .user-dropdown.active {
            display: block;
        }
        
        .user-option {
            padding: 10px;
            cursor: pointer;
        }
        
        .user-option:hover {
            background-color: #f0f0f0;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" id="loginForm">
            <div class="input-container">
                <input type="email" id="email" name="email" placeholder="Email" required autocomplete="off">
                <div class="user-dropdown" id="userDropdown">
                    <?php foreach ($users as $user): ?>
                        <div class="user-option" data-email="<?php echo htmlspecialchars($user['email']); ?>" data-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>">
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> 
                            <br><small><?php echo htmlspecialchars($user['email']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <input type="password" id="password" name="password" placeholder="Password" required>
            <input type="hidden" id="direct_login" name="direct_login" value="false">
            <button type="submit">Login</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const directLoginInput = document.getElementById('direct_login');
            const userDropdown = document.getElementById('userDropdown');
            const userOptions = document.querySelectorAll('.user-option');
            
            // Show dropdown when email input is focused
            emailInput.addEventListener('focus', function() {
                userDropdown.classList.add('active');
            });
            
            // Hide dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!emailInput.contains(event.target) && !userDropdown.contains(event.target)) {
                    userDropdown.classList.remove('active');
                }
            });
            
            // When user option is clicked
            userOptions.forEach(function(option) {
                option.addEventListener('click', function() {
                    const email = this.getAttribute('data-email');
                    emailInput.value = email;
                    directLoginInput.value = 'true';
                    document.getElementById('loginForm').submit();
                    userDropdown.classList.remove('active');
                });
            });
            
            // Reset direct login when typing password
            passwordInput.addEventListener('input', function() {
                directLoginInput.value = 'false';
            });
            
            // Filter dropdown and reset direct login when typing email
            emailInput.addEventListener('input', function() {
                const value = this.value.toLowerCase();
                userOptions.forEach(function(option) {
                    const email = option.getAttribute('data-email').toLowerCase();
                    const name = option.getAttribute('data-name').toLowerCase();
                    if (email.includes(value) || name.includes(value)) {
                        option.style.display = 'block';
                    } else {
                        option.style.display = 'none';
                    }
                });
                directLoginInput.value = 'false';
            });
        });
    </script>
</body>

</html>