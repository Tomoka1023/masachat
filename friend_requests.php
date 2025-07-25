<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// 承認処理
if (isset($_POST['approve_id'])) {
    $from_id = (int)$_POST['approve_id'];

    // 更新：申請を承認
    $stmt = $pdo->prepare("UPDATE friends SET status = 'approved' WHERE user_id = ? AND friend_id = ?");
    $stmt->execute([$from_id, $user_id]);

    // 双方向に登録（approvedで）
    $stmt = $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'approved')");
    $stmt->execute([$user_id, $from_id]);
}

// 自分宛の申請（pending）一覧
$stmt = $pdo->prepare("
    SELECT users.id, users.display_name FROM friends
    JOIN users ON friends.user_id = users.id
    WHERE friends.friend_id = ? AND friends.status = 'pending'
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll();

// 拒否処理
if (isset($_POST['reject_id'])) {
    $from_id = (int)$_POST['reject_id'];

    // 自分宛の申請（pending）を削除
    $stmt = $pdo->prepare("DELETE FROM friends WHERE user_id = ? AND friend_id = ? AND status = 'pending'");
    $stmt->execute([$from_id, $user_id]);
}

?>

<head>
  <meta charset="UTF-8">
  <title>友達申請</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <link rel="icon" href="favicon.ico" type="image/ico">
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DotGothic16&family=Hachi+Maru+Pop&family=Kaisei+Decol&family=M+PLUS+Rounded+1c&display=swap" rel="stylesheet">
</head>
<div class="container">
<h2>友達申請一覧</h2>
<ul>
<?php foreach ($requests as $r): ?>
    <li>
        <?php echo htmlspecialchars($r['display_name']); ?>
        <form method="post" style="display:inline;">
            <input type="hidden" name="approve_id" value="<?php echo $r['id']; ?>">
            <button type="submit">承認する</button>
        </form>
        <form method="post" style="display:inline;">
            <input type="hidden" name="reject_id" value="<?php echo $r['id']; ?>">
            <button type="submit" onclick="return confirm('本当に拒否しますか？');">拒否する</button>
        </form>
    </li>
<?php endforeach; ?>
</ul>

<p><a href="home.php" class="home-btn">🏠️</a></p>
</div>
