<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit;
}

$user_id = $_SESSION['user_id'];
$to_user_id = (int)$_GET['to'];

$stmt = $pdo->prepare("
    SELECT * FROM messages 
    WHERE (from_user = ? AND to_user = ?) OR (from_user = ? AND to_user = ?)
    ORDER BY created_at ASC
");
$stmt->execute([$user_id, $to_user_id, $to_user_id, $user_id]);
$messages = $stmt->fetchAll();

function formatTimestamp($datetime) {
    if (!$datetime) return '';
    $now = new DateTime();
    $time = new DateTime($datetime);
    return $time->format('n/j H:i');
}

foreach ($messages as $msg) :
    $is_self = $msg['from_user'] == $user_id;
    $class = $is_self ? 'self' : 'other';

    $stmt = $pdo->prepare("SELECT display_name, icon FROM users WHERE id = ?");
    $stmt->execute([$msg['from_user']]);
    $user = $stmt->fetch();
    $icon = $user['icon'] ? $user['icon'] : 'uploads/default.png';
    $formattedTime = formatTimestamp($msg['created_at']);

    echo "
    <div class='message {$class}'>
        <img src='{$icon}' class='icon'>
        <div class='bubble'>
            <p>" . nl2br(htmlspecialchars($msg['message'])) . "</p>
            <small>{$formattedTime}</small>
        </div>
    </div>
    ";
endforeach;
?>
