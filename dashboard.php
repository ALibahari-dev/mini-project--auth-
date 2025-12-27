<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="fa">
<body>

<h2>سلام <?= htmlspecialchars($user['username']) ?> 👋</h2>
<p><?= htmlspecialchars($user['email']) ?></p>

<a href="logout.php">خروج</a>

</body>
</html>
