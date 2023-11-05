<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}

include 'config/config.php';
if ($db->connect_error) {
    die("Błąd połączenia z bazą danych: " . $db->connect_error);
}

$errorMsg = "";

$successMsg = "";

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $countQuery = "SELECT COUNT(*) as count FROM users";
    $countResult = $db->query($countQuery);
    $countData = $countResult->fetch_assoc();
    $nextUserID = $countData['count'] + 1; // Nowe ID będzie kolejnym dostępnym numerem

    $checkEmailQuery = "SELECT email FROM users WHERE email = ?";
    $checkEmailStmt = $db->prepare($checkEmailQuery);
    $checkEmailStmt->bind_param("s", $email);
    $checkEmailStmt->execute();
    $checkEmailStmt->store_result();

    $checkUsernameQuery = "SELECT username FROM users WHERE username = ?";
    $checkUsernameStmt = $db->prepare($checkUsernameQuery);
    $checkUsernameStmt->bind_param("s", $username);
    $checkUsernameStmt->execute();
    $checkUsernameStmt->store_result();

    if ($checkEmailStmt->num_rows > 0) {
        $errorMsg = "Adres e-mail już istnieje w bazie. Proszę użyć innego adresu e-mail.";
    } elseif ($checkUsernameStmt->num_rows > 0) {
        $errorMsg = "Nazwa użytkownika jest już zajęta. Proszę wybrać inną nazwę.";
    } else {
        $query = "INSERT INTO users (id, username, password, email) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param("isss", $nextUserID, $username, $hashedPassword, $email);

        if ($stmt->execute()) {
            $successMsg = "Rejestracja udana. Możesz się teraz zalogować.";
        } else {
            $errorMsg = "Błąd rejestracji: " . $stmt->error;
        }

        $stmt->close();
    }

    $checkEmailStmt->close();
    $checkUsernameStmt->close();
}
include "themes/$theme/register.php";
?>
