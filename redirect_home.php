<?php
session_start();

// ログインしていれば home.php に、してなければ login.php にリダイレクト
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
} else {
    header('Location: login.php');
}
exit;
?>