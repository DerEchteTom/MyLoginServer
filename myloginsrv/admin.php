<?php
session_start();

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

$mailerAvailable = class_exists('PHPMailer\\PHPMailer\\PHPMailer');
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Admin-Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 600px;">
    <div class="card">
        <div class="card-body">
            <h3 class="mb-4">Admin-Dashboard</h3>
            <div class="d-grid gap-3">
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <a href="admin_users.php" class="btn btn-outline-primary">Benutzerverwaltung</a>
                    <?php if ($mailerAvailable): ?>
                        <a href="admin_mailtest.php" class="btn btn-outline-secondary">Mail-Test (SMTP)</a>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">PHPMailer ist nicht installiert – Mail-Test nicht verfügbar.</div>
                    <?php endif; ?>
                    <a href="admin_logs.php" class="btn btn-outline-dark">Logdatei anzeigen</a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-outline-warning">Zurück zur Startseite</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>