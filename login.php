<?php
session_start();

if (isset($_SESSION['user_id']) || isset($_SESSION['2fa_login'])) {
    header("Location: home.php");
    exit;
}

include 'config/config.php';
if ($db->connect_error) {
    die("Błąd połączenia z bazą danych: " . $db->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT id, username, password, email, is_2fa_enabled FROM users WHERE username = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $db_username, $db_password, $user_email, $is_2fa_enabled);
        $stmt->fetch();

        if (password_verify($password, $db_password)) {
            if ($is_2fa_enabled == 1) {
                $email_query = "SELECT email FROM users WHERE id = ?";
                $email_stmt = $db->prepare($email_query);
                $email_stmt->bind_param("i", $user_id);
                $email_stmt->execute();
                $email_stmt->bind_result($user_email);
                $email_stmt->fetch();

                $_SESSION['2fa_login'] = $user_id;
                $_SESSION['2fa_email'] = $user_email; // Dodaj zmienną 2fa_email
                header("Location: 2fa.php");
                exit;
            } else {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_email'] = $user_email;
                header("Location: home.php");
                exit;
            }
        } else {
            $error = "Nieprawidłowe hasło";
        }
    } else {
        $error = "Nieprawidłowa nazwa użytkownika";
    }
}
include "themes/$theme/login.php";
?>
