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
        }
    } catch (PDOException $e) {
        $error_message = 'Błąd zapytania do bazy danych.';
    } catch (Exception $e) {
        $error_message = 'Wystąpił ogólny błąd.';
    }
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM tickets WHERE user_id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
include "themes/$theme/ticket.php";
?>
