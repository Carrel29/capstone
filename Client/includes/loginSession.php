<?php
require __DIR__ . "/dbh.inc.php";

$isSuccess = false;
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $password = isset($_POST['password']) ? htmlspecialchars($_POST["password"], ENT_QUOTES, 'UTF-8') : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email";
        return $message;
    }

    try {
        $query = "SELECT bt_password_hash, bt_privilege_id, bt_first_name, bt_last_name, bt_user_id FROM btuser WHERE bt_email = :email";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['bt_password_hash'])) {
            session_regenerate_id(true);
            $_SESSION["loggedin"] = true;
            $_SESSION["fullname"] = $user['bt_first_name'] . " " . $user['bt_last_name'];
            $_SESSION["email"] = $email;
            $_SESSION["privilege"] = $user['bt_privilege_id'];
            $_SESSION["user_id"] = $user['bt_user_id'];

            $isSuccess = true;
            if ($user['bt_privilege_id'] == 1) {
                header("Location: admin.php");
                exit;
            }

            header("Location: index.php");
            exit;
        } else {
            $message = "Invalid email or password";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}
