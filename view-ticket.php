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

$ticket = null;
if (isset($_GET['id'])) {
    $ticket_id = $_GET['id'];

    $sql_ticket = "SELECT * FROM tickets WHERE id = ?";
    $stmt_ticket = $db->prepare($sql_ticket);
    $stmt_ticket->bind_param("i", $ticket_id);
    $stmt_ticket->execute();
    $result_ticket = $stmt_ticket->get_result();

    if ($result_ticket->num_rows > 0) {
        $ticket = $result_ticket->fetch_assoc();

        if ($ticket['status'] === 'closed') {
            $error_message = 'Ticket jest zamknięty. Nie można dodawać nowych wiadomości.';
        }

        if ($_SESSION['user_id'] !== $ticket['user_id']) {
            $error_message = 'Nie masz dostępu do tego ticketa.';
            $ticket = null;
        }
    } else {
        $error_message = 'Ticket o podanym ID nie istnieje.';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_message']) && $ticket && $ticket['status'] !== 'closed') {
        try {
            $message_content = $_POST['message_content'];

            if (empty($message_content)) {
                $error_message = 'Pole wiadomości nie może być puste.';
            } else {
                $user_id = $_SESSION['user_id'];

                $sql_message = "INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES (?, ?, ?)";
                $stmt_message = $db->prepare($sql_message);
                $stmt_message->bind_param("iis", $ticket_id, $user_id, $message_content);
                $stmt_message->execute();

                $success_message = 'Wiadomość została dodana.';
            }
        } catch (PDOException $e) {
            $error_message = 'Błąd zapytania do bazy danych.';
        } catch (Exception $e) {
            $error_message = 'Wystąpił ogólny błąd.';
        }
    }

    if ($ticket && $_SESSION['user_id'] === $ticket['user_id']) {
        $sql_messages = "SELECT tm.*, u.username FROM ticket_messages tm
                        JOIN users u ON tm.user_id = u.id
                        WHERE tm.ticket_id = ?";
        $stmt_messages = $db->prepare($sql_messages);
        $stmt_messages->bind_param("i", $ticket_id);
        $stmt_messages->execute();
        $result_messages = $stmt_messages->get_result();
    }

} else {
    $error_message = 'Brak identyfikatora ticketu.';
}
include "themes/$theme/view-ticket.php";
?>
