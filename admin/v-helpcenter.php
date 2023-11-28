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

if (!isset($_GET['id'])) {
    header("Location: helpcenter.php");
    exit;
}

$ticket_id = $_GET['id'];

$sql = "SELECT * FROM tickets WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error_message = 'Ticket not found.';
}

$ticket = $result->fetch_assoc();

$sql_messages = "SELECT tm.id, tm.user_id, tm.message, tm.created_at, u.username
                FROM ticket_messages tm
                JOIN users u ON tm.user_id = u.id
                WHERE tm.ticket_id = ?
                ORDER BY tm.created_at";
$stmt_messages = $db->prepare($sql_messages);
$stmt_messages->bind_param("i", $ticket_id);
$stmt_messages->execute();
$result_messages = $stmt_messages->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['new_message'])) {
            $new_message = $_POST['new_message'];

            if (empty($new_message)) {
                $error_message = 'Message cannot be empty.';
            } else {
                $user_id = $_SESSION['user_id'];
                $admin_text = '(ADMIN) ';
                $full_message = $admin_text . $new_message;

                $sql_insert_message = "INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES (?, ?, ?)";
                $stmt_insert_message = $db->prepare($sql_insert_message);
                $stmt_insert_message->bind_param("iis", $ticket_id, $user_id, $full_message);
                $stmt_insert_message->execute();

                $success_message = 'Message added successfully.';
                header("Location: v-helpcenter.php?id={$ticket_id}");
                exit;
            }
        }
    } catch (PDOException $e) {
        $error_message = 'Error adding message.';
    } catch (Exception $e) {
        $error_message = 'General error.';
    }
}



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
            header("Location: v-helpcenter.php?id={$ticket_id}");
            exit;
        } elseif (isset($_POST['close_ticket'])) {
            $ticket_id = $_POST['ticket_id'];
            $status = 'closed';

            $sql = "UPDATE tickets SET status = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("si", $status, $ticket_id);
            $stmt->execute();

            $success_message = 'Ticket został zamknięty.';
            header("Location: v-helpcenter.php?id={$ticket_id}");
            exit;
        }
    } catch (PDOException $e) {
        $error_message = 'Błąd zapytania do bazy danych.';
    } catch (Exception $e) {
        $error_message = 'Wystąpił ogólny błąd.';
    }
}
include "../themes/$theme/admin/v-helpcenter.php";
?>
