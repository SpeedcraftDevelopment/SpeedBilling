<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'config/config.php';

$user_id = $_SESSION['user_id'];

$query = "SELECT username FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 1) {
    $stmt->bind_result($usrname);
    $stmt->fetch();
} else {
    echo "Nie znaleziono użytkownika o podanym ID.";
    exit;
}
include "themes/$theme/home.php";
?>
