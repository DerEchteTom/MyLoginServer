<?php
// Datei: admin_userdump.php
session_start();

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$db = new PDO('sqlite:users.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$users = $db->query("SELECT * FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>User-Dump</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h4>Debug: Benutzer-Datenbank</h4>
    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>E-Mail</th>
                <th>Rolle</th>
                <th>Aktiv</th>
                <th>Redirects</th>
                <th>Token</th>
                <th>Ablauf</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= $u['role'] ?></td>
                <td><?= $u['active'] ? '✔' : '✖' ?></td>
                <td><?= htmlspecialchars($u['redirect_urls']) ?></td>
                <td><?= $u['reset_token'] ?? '' ?></td>
                <td><?= $u['reset_expires'] ?? '' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <a href="admin.php" class="btn btn-secondary">Zurück</a>
</div>
</body>
</html>
