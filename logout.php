<?php
session_start();
session_destroy(); // セッションを完全に破棄
header('Location: index.php'); // index.php にリダイレクト
exit;
?>
