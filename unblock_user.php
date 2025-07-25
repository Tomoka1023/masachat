<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['unblock_id'])) {
    header("Location: home.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$unblock_id = (int)$_POST['unblock_id'];

// ステータスを "approved" に戻す場合は↓こっち
// $stmt = $pdo->prepare("UPDATE friends SET status = 'approved' WHERE user_id = ? AND friend_id = ? AND status = 'blocked'");

$stmt = $pdo->prepare("DELETE FROM friends WHERE user_id = ? AND friend_id = ? AND status = 'blocked'");
$stmt->execute([$user_id, $unblock_id]);

header("Location: home.php");
exit;
?>