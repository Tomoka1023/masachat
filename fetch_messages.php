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
    // $diff = $now->getTimestamp() - $time->getTimestamp();

    // if ($diff < 60) return 'たった今';
    // elseif ($diff < 3600) return floor($diff / 60) . '分前';
    // elseif ($diff < 86400) return floor($diff / 3600) . '時間前';
    // elseif ($diff < 172800) return '昨日';
    // elseif ($diff < 604800) return floor($diff / 86400) . '日前';
    // elseif ($now->format('Y') === $time->format('Y')) return $time->format('n月j日');
    // else return $time->format('Y年n月j日');
}

foreach ($messages as $msg) :
    $is_self = $msg['from_user'] == $user_id;
    $class = $is_self ? 'self' : 'other';

    $stmt = $pdo->prepare("SELECT display_name, icon FROM users WHERE id = ?");
    $stmt->execute([$msg['from_user']]);
    $user = $stmt->fetch();
    $icon = $user['icon'] ? '/chat_app/' . $user['icon'] : 'chat_app/uploads/default.png';
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
