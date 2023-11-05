<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'vendor/autoload.php';

include 'navbar.php';
include 'config/config.php';
if ($db->connect_error) {
    die("Błąd połączenia z bazą danych: " . $db->connect_error);
}

$user_id = $_SESSION['user_id'];
$changePasswordSuccess = $changeUsernameSuccess = $change2FASuccess = $deleteAccountSuccess = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['changePassword'])) {
        $currentPassword = $_POST['currentPassword'];
        $newPassword = $_POST['newPassword'];

        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($db_password);
            $stmt->fetch();

            if (password_verify($currentPassword, $db_password)) {
                // Hasło jest poprawne, można zmienić na nowe
                $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $updateQuery = "UPDATE users SET password = ? WHERE id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bind_param("si", $newPasswordHash, $user_id);
                $updateStmt->execute();
                $changePasswordSuccess = "Hasło zostało zmienione.";
            } else {
                $changePasswordSuccess = "Nieprawidłowe obecne hasło. Hasło nie zostało zmienione.";
            }
        }
    } elseif (isset($_POST['changeUsername'])) {
        $currentPassword = $_POST['currentPassword'];
        $newUsername = $_POST['newUsername'];

        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($db_password);
            $stmt->fetch();

            if (password_verify($currentPassword, $db_password)) {
                $updateQuery = "UPDATE users SET username = ? WHERE id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bind_param("si", $newUsername, $user_id);
                $updateStmt->execute();
                $changeUsernameSuccess = "Nazwa użytkownika została zmieniona.";
            } else {
                $changeUsernameSuccess = "Nieprawidłowe obecne hasło. Nazwa użytkownika nie została zmieniona.";
            }
        }
    } elseif (isset($_POST['change2FA'])) {
        $is2FAEnabled = isset($_POST['is2FAEnabled']) ? 1 : 0;
        $updateQuery = "UPDATE users SET is_2fa_enabled = ? WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bind_param("ii", $is2FAEnabled, $user_id);
        $updateStmt->execute();

        $change2FASuccess = $is2FAEnabled ? "Autoryzacja dwuskładnikowa (2FA) została włączona." : "Autoryzacja dwuskładnikowa (2FA) została wyłączona.";
    } elseif (isset($_POST['deleteAccount'])) {
        $deleteToken = bin2hex(random_bytes(32));
        $insertQuery = "INSERT INTO delete_account_tokens (user_id, token) VALUES (?, ?)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bind_param("is", $user_id, $deleteToken);
        if ($insertStmt->execute()) {

            $query = "SELECT email FROM users WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows == 1) {
                $stmt->bind_result($user_email);
                $stmt->fetch();

                $mail = new PHPMailer\PHPMailer\PHPMailer();

                try {
                    $mail->isSMTP();
                    $mail->Host = 'mx1.mail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'noreply@mail.com';
                    $mail->Password = 'password';
                    $mail->SMTPSecure = 'ssl';
                    $mail->Port = 465;

                    $mail->CharSet = 'UTF-8';
                    $mail->Encoding = 'base64';

                    $mail->setFrom('noreply@mail.com', 'Mail');
                    $mail->addAddress($user_email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Usuwanie konta';
                    $deleteLink = 'https://site.com/delete-account.php?token=' . $deleteToken;
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
                                width: 300px;
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
                                    <p style='font-size: 16px;'>Aby usunąć konto, kliknij poniższy przycisk:</p>
                                    <a href='$deleteLink' class='button'>
                                        Usuń konto
                                    </a>
                                </div>
                            </div>
                        </body>
                        </html>
                    ";

                    if ($mail->send()) {
                        $deleteAccountSuccess = "E-mail z linkiem do usunięcia konta został wysłany na Twój adres e-mail.";
                    } else {
                        $deleteAccountSuccess = "Błąd podczas wysyłania e-maila z linkiem do usunięcia konta: " . $mail->ErrorInfo;
                    }
                } catch (Exception $e) {
                    $deleteAccountSuccess = "Błąd podczas konfiguracji PHPMailera: " . $e->getMessage();
                }
            }
        }
    }
}
include "themes/$theme/profile.php";
?>
