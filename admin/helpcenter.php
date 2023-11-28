<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require '../config/config.php';

$user_id = $_SESSION['user_id'];
$query = "SELECT role FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows !== 1) {
    header("Location: ../login.php");
    exit;
}

$stmt->bind_result($user_role);
$stmt->fetch();

if ($user_role !== 'admin') {
    header("Location: ../home.php");
    exit;
}

include 'navbar.php';

$error_message = $success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['open_ticket'])) {
            $ticket_id = $_POST['ticket_id'];
            $status = 'open';

            $sql = "UPDATE tickets SET status = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("si", $status, $ticket_id);
            $stmt->execute();

            $success_message = 'Ticket został otwarty.';
        } elseif (isset($_POST['close_ticket'])) {
            $ticket_id = $_POST['ticket_id'];
            $status = 'closed';

            $sql = "UPDATE tickets SET status = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("si", $status, $ticket_id);
            $stmt->execute();

            $success_message = 'Ticket został zamknięty.';
        } elseif (isset($_POST['delete_ticket'])) {
            $ticket_id = $_POST['ticket_id'];

            $sql_delete_messages = "DELETE FROM ticket_messages WHERE ticket_id = ?";
            $stmt_delete_messages = $db->prepare($sql_delete_messages);
            $stmt_delete_messages->bind_param("i", $ticket_id);
            $stmt_delete_messages->execute();

            $sql_delete_ticket = "DELETE FROM tickets WHERE id = ?";
            $stmt_delete_ticket = $db->prepare($sql_delete_ticket);
            $stmt_delete_ticket->bind_param("i", $ticket_id);
            $stmt_delete_ticket->execute();

            $success_message = 'Ticket został usunięty wraz z powiązanymi wiadomościami.';
        }
    } catch (PDOException $e) {
        $error_message = 'Błąd zapytania do bazy danych.';
    } catch (Exception $e) {
        $error_message = 'Wystąpił ogólny błąd.';
    }
}

$sql = "SELECT * FROM tickets";
$result = $db->query($sql);
include "../themes/$theme/admin/helpcenter.php";
?>
