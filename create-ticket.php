<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include 'navbar.php';
include 'config/config.php';
if ($db->connect_error) {
    die("Błąd połączenia z bazą danych: " . $db->connect_error);
}

$error_message = $success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $subject = $_POST['subject'];
        $category = $_POST['category'];
        $priority = $_POST['priority'];
        $description = $_POST['description'];

        if (empty($subject) || empty($category) || empty($priority) || empty($description)) {
            $error_message = 'Wszystkie pola formularza są wymagane.';
        } else {
            $user_id = $_SESSION['user_id'];
            $status = 'open';

            if (!isset($db)) {
                $error_message = 'Błąd połączenia z bazą danych.';
            } else {
                $sql = "INSERT INTO tickets (user_id, category, priority, status, subject, description) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);

                $stmt->bind_param("isssss", $user_id, $category, $priority, $status, $subject, $description);
                $stmt->execute();

                $success_message = 'Twój ticket został pomyślnie dodany.';
            }
        }
    } catch (PDOException $e) {
        $error_message = 'Błąd zapytania do bazy danych.';
    } catch (Exception $e) {
        $error_message = 'Wystąpił ogólny błąd.';
    }
}
include "themes/$theme/create-ticket.php";
?>
