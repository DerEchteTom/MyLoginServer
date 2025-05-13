<?php
// Datei: dashboard.php – Version: 2025-05-09_01 – Rollebasierte Weiterleitung nach Zwischenschritt
session_start();
date_default_timezone_set('Europe/Berlin');

require_once __DIR__ . '/auth.php';

$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? 'user';

if (!$username) {
    header("Location: login.php");
    exit;
}

// Ziel festlegen
$target = ($role === 'admin') ? 'admin.php' : 'links.php';

?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <meta http-equiv="refresh" content="1; URL=<?= htmlspecialchars($target) ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h4>Willkommen, <?= htmlspecialchars($username) ?></h4>
    <p class="text-muted">Du wirst gleich weitergeleitet nach <code><?= htmlspecialchars($target) ?></code> ...</p>
    <div class="alert alert-info small mt-3">
        <strong>Rolle:</strong> <?= htmlspecialchars($role) ?><br>
        <strong>Zeit:</strong> <?= date('Y-m-d H:i:s') ?><br>
        <strong>Debug:</strong> Diese Seite kann spaeter genutzt werden, um Hinweise oder Systemmeldungen anzuzeigen.
    </div>
    <p><a href="<?= htmlspecialchars($target) ?>" class="btn btn-sm btn-primary mt-2">Falls keine Weiterleitung erfolgt &raquo;</a></p>
</body>
</html>
