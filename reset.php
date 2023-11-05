<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'config/config.php';
if ($db->connect_error) {
    die("Błąd połączenia z bazą danych: " . $db->connect_error);
}

$errorMsg = "";

$successMsg = "";

if (isset($_POST['reset'])) {
    $email = $_POST['email'];

    $checkUserQuery = "SELECT id FROM users WHERE email = ?";
    $checkUserStmt = $db->prepare($checkUserQuery);
    $checkUserStmt->bind_param("s", $email);
    $checkUserStmt->execute();
    $checkUserResult = $checkUserStmt->get_result();

    if ($checkUserResult->num_rows > 0) {
        $userData = $checkUserResult->fetch_assoc();
        $userId = $userData['id'];

        $token = bin2hex(random_bytes(16));
        $expiration = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $insertTokenQuery = "INSERT INTO password_reset (user_id, token, expiration) VALUES (?, ?, ?)";
        $insertTokenStmt = $db->prepare($insertTokenQuery);
        $insertTokenStmt->bind_param("iss", $userId, $token, $expiration);
        $insertTokenStmt->execute();
        $insertTokenStmt->close();

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'mx1.mail.com'; // Adres serwera SMTP
            $mail->SMTPAuth = true;
            $mail->Username = 'noreply@mail.com'; // Twój login SMTP
            $mail->Password = 'password'; // Twoje hasło SMTP
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->CharSet = 'UTF-8'; // Ustawić kodowanie na UTF-8
            $mail->Encoding = 'base64'; // Ustawić kodowanie treści na base64

            $mail->setFrom('noreply@mail.com', 'Mail');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Reset hasła';
            $mail->Body = "
            <html>
            <head>
            <style>
                .button {
                    background-color: #2563eb;
                    color: #ffffff;
                    text-decoration: none;
                    text-align: center;
                    padding: 10px 20px;
                    display: inline-block;
                    margin-top: 20px;
                    border-radius: 5px;
                }
                .center-logo {
                    text-align: center;
                    background-color: #151921;
                    border-top-left-radius: 5px;
                    border-top-right-radius: 5px;
                }
                .logo {
                    width: 300px; /* Dostosuj szerokość loga */
                    display: inline-block;
                }
            </style>
            </head>
            <body style='background-color: #151921; font-family: Arial, sans-serif; padding: 20px;'>
                <div style='background-color: #ffffff; max-width: 600px; margin: 0 auto; border-radius: 5px; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);'>
                <div class='center-logo'>
                    <img src='logo.png' alt='Logo' class='logo'>
                </div>
                    <div style='padding: 20px;'>
                        <p style='font-size: 16px;'>Aby zresetować hasło, kliknij poniższy przycisk:</p>
                        <a href='https://site.com/reset.php?token=$token' class='button'>
                            Zresetuj hasło
                        </a>
                    </div>
                </div>
            </body>
            </html>
            ";
            $mail->send();
            $successMsg = "Na Twój adres e-mail został wysłany link do resetowania hasła.";
        } catch (Exception $e) {
            $errorMsg = "Błąd podczas wysyłania e-maila: " . $mail->ErrorInfo;
        }
    } else {
        $errorMsg = "Użytkownik o podanym adresie e-mail nie istnieje.";
    }

    $checkUserStmt->close();
}

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $currentTime = date("Y-m-d H:i:s");

    $selectTokenQuery = "SELECT * FROM password_reset WHERE token = ? AND expiration > ?";
    $selectTokenStmt = $db->prepare($selectTokenQuery);
    $selectTokenStmt->bind_param("ss", $token, $currentTime);
    $selectTokenStmt->execute();
    $selectTokenResult = $selectTokenStmt->get_result();

    if ($selectTokenResult->num_rows > 0) {
        $resetPassword = true;

        $tokenData = $selectTokenResult->fetch_assoc();
        $userId = $tokenData['user_id'];

        $expireTokenQuery = "UPDATE password_reset SET expiration = ? WHERE token = ?";
        $expireTokenStmt = $db->prepare($expireTokenQuery);
        $expireTokenStmt->bind_param("ss", $currentTime, $token);
        $expireTokenStmt->execute();
        $expireTokenStmt->close();
    } else {
        $errorMsg = "Nieprawidłowy lub wygasły token resetu hasła.";
    }
}
if (isset($_POST['new_password'])) {
    $newPassword = $_POST['new_password'];
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    if (isset($_POST['token'])) {
        $token = $_POST['token'];

        $updatePasswordQuery = "UPDATE users SET password = ? WHERE id = (SELECT user_id FROM password_reset WHERE token = ?)";
        $updatePasswordStmt = $db->prepare($updatePasswordQuery);
        $updatePasswordStmt->bind_param("ss", $hashedPassword, $token);

        if ($updatePasswordStmt->execute()) {
            $successMsg = 'Twoje hasło zostało zresetowane. Możesz się teraz <a href="login.php">zalogować</a>.';
        } else {
            $errorMsg = "Błąd podczas resetowania hasła: " . $updatePasswordStmt->error;
        }
    }
}
include "themes/$theme/reset.php";
?>
