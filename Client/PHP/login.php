<?php
session_start();
require "../includes/loginSession.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../CSS/style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Comfortaa:wght@400;700&family=M+PLUS+Rounded+1c:wght@400;700&display=swap" rel="stylesheet">
    <title>Login</title>
</head>

<body>
    <main class="combo-display-flex h-100">
        <div class="card w-35">
            <div class="card-content w-100">
                <div class="combo-display-flex flex-column header">
                    <img src="../Img/Login/boy.png" alt="">
                    <h1>Login</h1>
                </div>
                <form action="login.php" method="post">
                    <div class="combo-display-flex-column-start">
                        <label for="email">Email:</label>
                        <input type="text" name="email" id="email" autocomplete="off" placeholder="example@sample.com" required>
                    </div>
                    <div class="combo-display-flex-column-start">
                        <label for="password">Password:</label>
                        <input type="password" name="password" id="password" autocomplete="off" placeholder="************" required>
                    </div>
                    <div class="combo-display-flex-space-between">
                        <input type="submit" value="Login" class="w-20">
                        <a href="signup.php">Don't have account?</a>
                    </div>
                </form>
            </div>
        </div>
    </main>


    <!-- toast start -->
    <div class="toast d-none <?php echo $isSuccess ? 'bg-green' : 'bg-red'; ?>">
        <div class="toast-header">
            <strong>Error</strong>
        </div>
        <div class="toast-body">
            <?php echo $message; ?>
        </div>
    </div>

    <!-- toast end -->
    <script>
        const message = "<?php echo $message ?>";
    </script>
    <script src="../JS/toast.js"></script>
</body>

</html>