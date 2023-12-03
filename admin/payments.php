<?php
session_start();

require '../config/config.php';
require 'navbar.php';

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

$query = "SELECT wt.transaction_id, wt.amount, wt.transaction_date, u.username, u.email, ps.setting_value AS currency
          FROM wallet_transactions wt
          JOIN users u ON wt.user_id = u.id
          JOIN panel_settings ps ON ps.setting_name = 'currency'
          ORDER BY wt.transaction_date DESC";

$result = $db->query($query);

if (!$result) {
    die("Błąd przy pobieraniu płatności z bazy: " . $db->error);
}
include "../themes/$theme/admin/payments.php";
?>

<?php
$result->free();
?>
