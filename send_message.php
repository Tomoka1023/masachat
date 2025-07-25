<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit;
}

$user_id = $_SESSION['user_id'];
$to_user_id = (int)$_POST['to'];
$message = trim($_POST['message']);

if ($message !== '') {
    $stmt = $pdo->prepare("INSERT INTO messages (from_user, to_user, message) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $to_user_id, $message]);
}
?>
