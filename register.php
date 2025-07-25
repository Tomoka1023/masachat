<!-- register.php -->
<?php
require_once 'db.php'; // DB接続ファイル

$message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $icon_path = null;
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir);
    
        $tmp_name = $_FILES['icon']['tmp_name'];
        $name = basename($_FILES['icon']['name']);
        $unique_name = uniqid() . '_' . $name;
        move_uploaded_file($tmp_name, $upload_dir . $unique_name);
        $icon_path = $upload_dir . $unique_name;
    }

    $username = $_POST['username'];
    $display_name = $_POST['display_name'];
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 同じユーザー名があるか確認
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $message = 'そのユーザー名は既に使われています。';
    } else {
        // 新規登録
        $stmt = $pdo->prepare('INSERT INTO users (username, password, display_name, icon) VALUES (?, ?, ?, ?)');
        $stmt->execute([$username, $hashed_password, $display_name, $icon_path]);
        $message = '登録完了！ログインしてね✨';
    }
}

?>

<head>
  <meta charset="UTF-8">
  <title>ユーザー登録</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="favicon.ico" type="image/ico">
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DotGothic16&family=Hachi+Maru+Pop&family=Kaisei+Decol&family=M+PLUS+Rounded+1c&display=swap" rel="stylesheet">
</head>
<div class="container">
<h2>ユーザー登録</h2>
<p style="color: red;"><?php echo $message; ?></p>

<form method="post" enctype="multipart/form-data">
    <label>ユーザー名:</label><br>
    <input type="text" name="username" required><br><br>

    <label>表示名:</label><br>
    <input type="text" name="display_name" required><br><br>

    <label>パスワード:</label><br>
    <input type="password" name="password" required><br><br>

    <label>プロフィール画像:</label><br>
    <input type="file" name="icon"><br><br>

    <button type="submit">登録する</button>
</form>

<p><a href="index.php">トップへ戻る</a></p>
</div>