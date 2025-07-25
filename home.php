<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['cancel_id'])) {
    $cancel_id = (int)$_POST['cancel_id'];
    $stmt = $pdo->prepare("DELETE FROM friends WHERE user_id = ? AND friend_id = ? AND status = 'pending'");
    $stmt->execute([$user_id, $cancel_id]);
}

$display_name = $_SESSION['display_name'];

$stmt = $pdo->prepare("
  SELECT u.id, u.display_name, u.icon
  FROM users u
  WHERE u.id IN (
    SELECT 
      CASE 
        WHEN f.user_id = :uid THEN f.friend_id
        WHEN f.friend_id = :uid THEN f.user_id
      END
    FROM friends f
    WHERE (f.user_id = :uid OR f.friend_id = :uid)
      AND f.status = 'approved'
    GROUP BY CASE 
      WHEN f.user_id < f.friend_id THEN CONCAT(f.user_id, '-', f.friend_id)
      ELSE CONCAT(f.friend_id, '-', f.user_id)
    END
  )
  AND u.id NOT IN (
    SELECT 
      CASE 
        WHEN f.user_id = :uid THEN f.friend_id
        WHEN f.friend_id = :uid THEN f.user_id
      END
    FROM friends f
    WHERE (f.user_id = :uid OR f.friend_id = :uid)
      AND f.status = 'blocked'
  )
");
$stmt->execute([':uid' => $user_id]);
$friends = $stmt->fetchAll();

$pendingCount = $stmt->fetchColumn();

// ユーザー情報（アイコン取得のため）
$stmt = $pdo->prepare("SELECT icon, username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch();

$icon_path = $user_data['icon'] ?: 'uploads/default.png';
$username = $user_data['username'] ?: '(不明)';
if (!$icon_path) $icon_path = 'uploads/default.png';

$stmt = $pdo->prepare("
    SELECT u.id, u.display_name, u.icon
    FROM friends f
    JOIN users u ON f.friend_id = u.id
    WHERE f.user_id = ? AND f.status = 'pending'
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll();

// ブロック中のユーザー一覧
$stmt = $pdo->prepare("
  SELECT u.id, u.display_name, u.icon
  FROM friends f
  JOIN users u ON (u.id = f.friend_id)
  WHERE f.user_id = ? AND f.status = 'blocked'
");
$stmt->execute([$user_id]);
$blocked_users = $stmt->fetchAll();


// ② 未読メッセージを送信者ごとに取得
$stmt = $pdo->prepare("
    SELECT from_user, COUNT(*) as unread_count
    FROM messages
    WHERE to_user = ? AND is_read = 0
    GROUP BY from_user
");
$stmt->execute([$user_id]);
$unreadList = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);  // from_user => 未読数

function statusLabel($status) {
    switch ($status) {
        case 'pending': return '申請中';
        case 'approved': return '友達';
        case 'blocked': return 'ブロック中';
        default: return '不明';
    }
}

?>

<head>
  <meta charset="UTF-8">
  <title>ホーム</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <link rel="icon" href="favicon.ico" type="image/ico">
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DotGothic16&family=Hachi+Maru+Pop&family=Kaisei+Decol&family=M+PLUS+Rounded+1c&display=swap" rel="stylesheet">
</head>
<body>
<div class="container">
<h2>こんにちは、<?php echo htmlspecialchars($display_name); ?>さん！</h2>
<div class="profile-box">
  <img src="<?php echo $icon_path; ?>" class="profile-icon">
    <span class="profile-name">
    <?php echo htmlspecialchars($display_name); ?>
    <small>@<?php echo htmlspecialchars((string)$username); ?></small>
    </span>
</div>
<p><a href="edit_profile.php">プロフィールを編集する</a></p>

<div class="friends-box">
<h3>友達リスト</h3>
<ul>
<?php foreach ($friends as $friend): ?>
    <?php $unread = $unreadList[$friend['id']] ?? 0; ?>
    <li>
    <a href="chat.php?to=<?= $friend['id'] ?>" class="friend-link">
      <img src="<?= $friend['icon'] ?: 'uploads/default.png' ?>" width="40" height="40" style="border-radius: 50%; object-fit: cover;">
      <?= htmlspecialchars($friend['display_name']) ?>（友達）
      <?php if ($unread > 0): ?>
        <span class="notification-badge"><?= $unread ?></span>
      <?php endif; ?>
    </a>
    <form method="post" action="block_user.php" onsubmit="return confirm('このユーザーをブロックしますか？');">
      <input type="hidden" name="block_id" value="<?= $friend['id'] ?>">
      <button type="submit" class="block-btn">ブロック</button>
    </form>
  </li>
<?php endforeach; ?>
</ul>
</div>

<h3>申請中のユーザー</h3>
<ul>
<?php foreach ($requests as $r): ?>
    <li style="margin-bottom: 10px;">
        <img src="<?php echo $r['icon'] ?: 'uploads/default.png'; ?>" width="40" style="border-radius: 50%; vertical-align: middle;">
        <?php echo htmlspecialchars($r['display_name']); ?>
        <form method="post" style="display:inline;">
            <input type="hidden" name="cancel_id" value="<?php echo $r['id']; ?>">
            <button type="submit">申請取消</button>
        </form>
    </li>
<?php endforeach; ?>
</ul>

<a href="friend_requests.php" style="position: relative; display: inline-block;">
  🔔 未処理の申請
  <?php if ($pendingCount > 0): ?>
    <span class="notification-badge"><?php echo $pendingCount; ?></span>
  <?php endif; ?>
</a>

<h3>ブロック中のユーザー</h3>
<ul>
<?php foreach ($blocked_users as $bu): ?>
  <li style="margin-bottom: 10px;">
    <img src="<?= $bu['icon'] ?: 'uploads/default.png' ?>" width="40" style="border-radius: 50%; vertical-align: middle;">
    <?= htmlspecialchars($bu['display_name']) ?>
    <form method="post" action="unblock_user.php" style="display:inline;">
      <input type="hidden" name="unblock_id" value="<?= $bu['id'] ?>">
      <button type="submit">ブロック解除</button>
    </form>
  </li>
<?php endforeach; ?>
</ul>

<p><a href="add_friend.php">＋ 友達追加</a></p>
<!-- <p><a href="friend_requests.php">友達申請を確認する</a></p> -->
<p><a href="logout.php">ログアウト</a></p>
</div>
</body>