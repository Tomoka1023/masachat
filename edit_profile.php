<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// 現在のプロフィール情報を取得
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $display_name = $_POST['display_name'];
    $icon_path = $user['icon']; // 初期値は今のアイコン

    // 新しい画像がアップされたら保存
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir);

        $tmp_name = $_FILES['icon']['tmp_name'];
        $name = basename($_FILES['icon']['name']);
        $unique_name = uniqid() . '_' . $name;
        move_uploaded_file($tmp_name, $upload_dir . $unique_name);
        $icon_path = $upload_dir . $unique_name;
    }

    // 更新処理
    $stmt = $pdo->prepare("UPDATE users SET display_name = ?, icon = ? WHERE id = ?");
    $stmt->execute([$display_name, $icon_path, $user_id]);

    // セッションの表示名も更新
    $_SESSION['display_name'] = $display_name;
    $message = 'プロフィールを更新しました！';
    
    // 最新の情報を再取得
    $user['display_name'] = $display_name;
    $user['icon'] = $icon_path;
}
?>

<head>
  <meta charset="UTF-8">
  <title>プロフィール編集</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <link rel="icon" href="favicon.ico" type="image/ico">
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DotGothic16&family=Hachi+Maru+Pop&family=Kaisei+Decol&family=M+PLUS+Rounded+1c&display=swap" rel="stylesheet">
</head>
<div class="container">
<h2>プロフィール編集</h2>
<p style="color: green;"><?php echo $message; ?></p>

<form method="post" enctype="multipart/form-data">
    <label>表示名:</label><br>
    <input type="text" name="display_name" value="<?php echo htmlspecialchars($user['display_name']); ?>" required><br><br>

    <label>プロフィール画像:</label><br>
    <input type="file" name="icon"><br>
    <?php if ($user['icon']): ?>
        <img src="<?php echo $user['icon']; ?>" width="80" style="margin-top:10px;"><br>
    <?php endif; ?>
    <br>

    <button type="submit">更新する</button>
</form>

<p><a href="home.php" class="home-btn">🏠️</a></p>
</div>
