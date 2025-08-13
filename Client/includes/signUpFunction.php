<?php 

require "../includes/dbh.inc.php";

$isSuccess = false;
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $password = htmlspecialchars($_POST["password"], ENT_QUOTES, 'UTF-8');
    $confirmPassword = htmlspecialchars($_POST["confirm-password"], ENT_QUOTES, 'UTF-8');
    $firstname = htmlspecialchars($_POST["firstname"], ENT_QUOTES, 'UTF-8');
    $lastname = htmlspecialchars($_POST["lastname"], ENT_QUOTES, 'UTF-8');
    $phonenumber = htmlspecialchars($_POST["Phonenumber"], ENT_QUOTES, 'UTF-8');

    if (empty($email) || empty($password) || empty($confirmPassword) || empty($firstname) || empty($lastname) || empty($phonenumber)) {
        $message = "All fields are required.";
    }

    if ($password != $confirmPassword) {
        $message = "Password does not match";
    } elseif (strlen($phonenumber) != 11) {
        $message = "Invalid phone number";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email";
    } elseif (isEmailExist($email)) {
        $message = "Email already exists";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

  try {
    $stmt = $pdo->prepare("
        INSERT INTO client_users (first_name, last_name, email, password, phone_number, is_verified, created_at)
        VALUES (:firstname, :lastname, :email, :password, :phonenumber, 0, NOW())
    ");
    $stmt->bindParam(':firstname', $firstname);
    $stmt->bindParam(':lastname', $lastname);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':phonenumber', $phonenumber);
    $stmt->execute();

    $isSuccess = true;
    $message = "Signup successful";

} catch (PDOException $e) {
    $message = "Error: " . $e->getMessage();
}
    }
}

function isEmailExist($emailParam) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM client_users WHERE email = :email");
        $stmt->bindParam(':email', $emailParam, PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count > 0;


    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}
?>