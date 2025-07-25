<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$searched_user = null;
$already_requested = false;

if (isset($_POST['add_id'])) {
    $add_id = (int)$_POST['add_id'];

    // 二重登録防止
    $stmt = $pdo->prepare("SELECT * FROM friends WHERE user_id = ? AND friend_id = ?");
    $stmt->execute([$user_id, $add_id]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$user_id, $add_id]);
    }
}

if (isset($_POST['cancel_id'])) {
    $cancel_id = (int)$_POST['cancel_id'];
    $stmt = $pdo->prepare("DELETE FROM friends WHERE user_id = ? AND friend_id = ?");
    $stmt->execute([$user_id, $cancel_id]);
}

if (isset($_GET['search_id'])) {
    $search_id = trim($_GET['search_id']);

    // 自分じゃないか確認
    if ($search_id != $user_id) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$search_id]);
        $searched_user = $stmt->fetch();

        // 既に申請 or 友達か確認
        if ($searched_user) {
            $check = $pdo->prepare("SELECT * FROM friends WHERE user_id = ? AND friend_id = ?");
            $check->execute([$user_id, $searched_user['id']]);
            if ($check->fetch()) {
                $already_requested = true;
            }
        }
    }
}

?>

<head>
  <meta charset="UTF-8">
  <title>友達申請</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="favicon.ico" type="image/ico">
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DotGothic16&family=Hachi+Maru+Pop&family=Kaisei+Decol&family=M+PLUS+Rounded+1c&display=swap" rel="stylesheet">
</head>
<div class="container">
<h2>友達追加申請</h2>

<div class="search-form-wrapper">
  <label for="search_id">ユーザー名を検索：</label>
  <form class="search-form" method="get">
    <input type="text" name="search_id" id="search_id" required>
    <button type="submit">検索</button>
  </form>
</div>

<!-- <form method="get">
    <label>ユーザー名を検索：</label>
    <input type="text" name="search_id" required>
    <button type="submit">検索</button>
</form> -->

<?php if (isset($_GET['search_id'])): ?>
    <hr>
    <?php if (!$searched_user): ?>
        <p>ユーザーが見つかりません。</p>
    <?php elseif ($already_requested): ?>
        <form method="post">
            <input type="hidden" name="cancel_id" value="<?php echo $searched_user['id']; ?>">
            <button type="submit" style="background-color: #f88; color: white; border-radius: 10px; padding: 10px 16px; border: none;">
                ❌ 申請取消
            </button>
        </form>
    <?php else: ?>
<div style="display: flex; align-items: center; gap: 10px; margin: 10px 0;">
    <img src="<?php echo $searched_user['icon'] ?: 'uploads/default.png'; ?>" width="50" height="50" style="border-radius: 50%; object-fit: cover;">
    <div>
        <p style="margin: 0;">
            <?php echo htmlspecialchars($searched_user['display_name']); ?>
        </p>
            <form method="post" style="margin: 5px 0;">
                <input type="hidden" name="add_id" value="<?php echo $searched_user['id']; ?>">
                <button type="submit" class="friend-btn">➕ 友達申請</button>
            </form>
    </div>
</div>
    <?php endif; ?>
<?php endif; ?>

<p><a href="home.php" class="home-btn">🏠️</a></p>
    </div>