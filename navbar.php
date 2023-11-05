<?php
require 'vendor/autoload.php';
use Gravatar\Gravatar;

include 'config/config.php';
if ($db->connect_error) {
    die("Błąd połączenia z bazą danych: " . $db->connect_error);
}

$user_id = $_SESSION['user_id'];
$email = "";

if (!empty($user_id)) {
    $query = "SELECT email FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($email);
        $stmt->fetch();
    }
}
$gurl1 = Gravatar::image($email);
include "themes/$theme/navbar.php";
?>
