<?php
session_start();
require_once 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // ユーザー名で検索
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // パスワード一致 → ログイン成功
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['display_name'] = $user['display_name'];
        header('Location: home.php');
        exit;
    } else {
        $message = 'ユーザー名またはパスワードが違います。';
    }
}
?>

<head>
  <meta charset="UTF-8">
  <title>ログイン</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="favicon.ico" type="image/ico">
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DotGothic16&family=Hachi+Maru+Pop&family=Kaisei+Decol&family=M+PLUS+Rounded+1c&display=swap" rel="stylesheet">
</head>
<div class="container">
<h2>ログイン</h2>
<p style="color: red;"><?php echo $message; ?></p>

<form method="post">
    <label>ユーザー名:</label><br>
    <input type="text" name="username" required><br><br>

    <label>パスワード:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">ログイン</button>
</form>

<p><a href="register.php">新規登録はこちら</a></p>
</div>
