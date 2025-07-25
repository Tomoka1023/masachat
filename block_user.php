<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['block_id'])) {
    header('Location: home.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$block_id = (int)$_POST['block_id'];

// すでに友達関係がある場合は更新
$stmt = $pdo->prepare("UPDATE friends SET status = 'blocked' WHERE user_id = ? AND friend_id = ?");
$stmt->execute([$user_id, $block_id]);

// なければ新規作成（片方向でOKなら）
if ($stmt->rowCount() === 0) {
    $stmt = $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'blocked')");
    $stmt->execute([$user_id, $block_id]);
}

header('Location: home.php');
exit;
?>