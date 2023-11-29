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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    try {
        $announcement_text = $_POST['announcement_text'];
        $announcement_color = $_POST['announcement_color'];

        // Sprawdzenie, czy już istnieje aktywne ogłoszenie
        $sql_check_active = "SELECT * FROM announcements WHERE is_active = 1";
        $result_check_active = $db->query($sql_check_active);

        if ($result_check_active->num_rows === 0) {
            $sql_add_announcement = "INSERT INTO announcements (text, color) VALUES (?, ?)";
            $stmt_add_announcement = $db->prepare($sql_add_announcement);
            $stmt_add_announcement->bind_param("ss", $announcement_text, $announcement_color);
            $stmt_add_announcement->execute();

            $success_message = 'Announcement added successfully.';
        } else {
            $error_message = 'An active announcement already exists. Deactivate it first before adding a new one.';
        }
    } catch (Exception $e) {
        $error_message = 'Error adding announcement.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activate_announcement'])) {
    try {
        $announcement_id = $_POST['announcement_id'];

        $sql_deactivate_all = "UPDATE announcements SET is_active = 0";
        $db->query($sql_deactivate_all);

        $sql_activate_announcement = "UPDATE announcements SET is_active = 1 WHERE id = ?";
        $stmt_activate_announcement = $db->prepare($sql_activate_announcement);
        $stmt_activate_announcement->bind_param("i", $announcement_id);
        $stmt_activate_announcement->execute();

        $success_message = 'Announcement activated successfully.';
    } catch (Exception $e) {
        $error_message = 'Error activating announcement.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deactivate_announcement'])) {
    try {
        $announcement_id = $_POST['announcement_id'];

        $sql_deactivate_announcement = "UPDATE announcements SET is_active = 0 WHERE id = ?";
        $stmt_deactivate_announcement = $db->prepare($sql_deactivate_announcement);
        $stmt_deactivate_announcement->bind_param("i", $announcement_id);
        $stmt_deactivate_announcement->execute();

        $success_message = 'Announcement deactivated successfully.';
    } catch (Exception $e) {
        $error_message = 'Error deactivating announcement.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_form'])) {
    try {
        $edited_announcement_id = $_POST['edited_announcement_id'];
        $edited_text = $_POST['edited_text'];
        $edited_color = $_POST['edited_color'];

        // Edycja wybranego ogłoszenia
        $sql_edit_announcement = "UPDATE announcements SET text = ?, color = ? WHERE id = ?";
        $stmt_edit_announcement = $db->prepare($sql_edit_announcement);
        $stmt_edit_announcement->bind_param("ssi", $edited_text, $edited_color, $edited_announcement_id);
        $stmt_edit_announcement->execute();

        $success_message = 'Announcement edited successfully.';
    } catch (Exception $e) {
        $error_message = 'Error editing announcement.';
    }
}

$sql_get_announcements = "SELECT * FROM announcements";
$result_get_announcements = $db->query($sql_get_announcements);
include "../themes/$theme/admin/announcements.php";
?>
