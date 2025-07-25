<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$to_user_id = isset($_GET['to']) ? (int)$_GET['to'] : 0;
$user_id = $_SESSION['user_id'];

// 相手の情報取得
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$to_user_id]);
$to_user = $stmt->fetch();

if (!$to_user_id) {
    echo "相手が見つかりません。";
    exit;
}

// 相手が自分をブロックしているか確認
$stmt = $pdo->prepare("SELECT * FROM friends WHERE user_id = ? AND friend_id = ? AND status = 'blocked'");
$stmt->execute([$to_user_id, $user_id]);
$is_blocked = $stmt->fetch() ? true : false;

// chat.php の最初の方でブロック状態チェック
$stmt = $pdo->prepare("SELECT * FROM friends WHERE user_id = ? AND friend_id = ? AND status = 'blocked'");
$stmt->execute([$user_id, $to_user_id]);
if ($stmt->fetch()) {
    echo "<p>このユーザーはブロックされています。</p>";
    exit;
}

// 自分と相手のIDを取得
$from_user = $_SESSION['user_id'];
// $to_user = (int)($_GET['to'] ?? 0);

// 未読メッセージを既読に更新
$stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE from_user = ? AND to_user = ? AND is_read = 0");
$stmt->execute([$to_user_id, $from_user]);


// メッセージ送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (from_user, to_user, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $to_user_id, $message]);
    }
}

// チャット履歴を取得（送受信両方）
$stmt = $pdo->prepare("
    SELECT * FROM messages 
    WHERE (from_user = ? AND to_user = ?) OR (from_user = ? AND to_user = ?)
    ORDER BY created_at ASC
");
$stmt->execute([$user_id, $to_user_id, $to_user_id, $user_id]);
$messages = $stmt->fetchAll();
?>

<head>
  <meta charset="UTF-8">
  <title>チャット</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <link rel="icon" href="favicon.ico" type="image/ico">
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DotGothic16&family=Hachi+Maru+Pop&family=Kaisei+Decol&family=M+PLUS+Rounded+1c&display=swap" rel="stylesheet">
</head>

<h2><?php echo htmlspecialchars($to_user['display_name'] ?? '???'); ?>さんとのチャット</h2>

<div id="chat-box" style="border: 1px solid #ccc; padding: 10px; height: 300px; overflow-y: scroll;">
    <!-- メッセージはここにAjaxで読み込み -->
</div>

<?php if ($is_blocked): ?>
    <p style="color:red;">このユーザーからブロックされています。メッセージを送信できません。</p>
<?php else: ?>
    <form method="post" id="chat-form" class="send-form">
        <input type="text" name="message" id="message" required>
        <button type="submit" class="send-btn">👆️</button>
        <input type="hidden" id="to_user_id" value="<?php echo $to_user_id; ?>">
    </form>
<?php endif; ?>

<p><a href="home.php" class="home-btn">🏠️</a></p>

<script>
function fetchMessages() {
    const to = document.getElementById('to_user_id').value;
    const box = document.getElementById('chat-box');
    const isAtBottom = box.scrollHeight - box.scrollTop <= box.clientHeight + 50;

    fetch(`fetch_messages.php?to=${to}`)
        .then(response => response.text())
        .then(data => {
            box.innerHTML = data;
            if (isAtBottom) {
                setTimeout(() => {
                    box.scrollTop = box.scrollHeight;
                }, 30);
            }
        });
}

document.getElementById('chat-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const message = document.getElementById('message').value;
    const to = document.getElementById('to_user_id').value;

    fetch('send_message.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `message=${encodeURIComponent(message)}&to=${to}`
    }).then(() => {
        document.getElementById('message').value = '';
        fetchMessages();
    });
});

setInterval(fetchMessages, 2000); // 2秒ごとに更新
fetchMessages(); // 最初にも1回読み込み


</script>