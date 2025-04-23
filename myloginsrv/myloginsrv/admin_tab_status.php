<?php
// Datei: admin_tab_status.php – Stand: 2025-04-23 11:51 Europe/Berlin

date_default_timezone_set('Europe/Berlin');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireRole('admin');

$db = new PDO('sqlite:users.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$linkRequestCount = $db->query("SELECT COUNT(*) FROM link_requests WHERE status = 'open'")->fetchColumn();
$linkCount = $db->query("SELECT COUNT(*) FROM user_links")->fetchColumn();
$inactiveUsers = $db->query("SELECT COUNT(*) FROM users WHERE active = 0")->fetchColumn();
$adminCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$logAudit = file_exists("audit.log") ? round(filesize("audit.log") / 1024, 1) . ' KB' : 'nicht vorhanden';
$logError = file_exists("error.log") ? round(filesize("error.log") / 1024, 1) . ' KB' : 'nicht vorhanden';
$phpVersion = phpversion();
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'unbekannt';
$os = php_uname();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Systemstatus</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container-fluid mt-4" style="max-width: 100%;">
    <?php if (file_exists(__DIR__ . '/admin_tab_nav.php')) include __DIR__ . '/admin_tab_nav.php'; ?>

    <h4 class="mb-4">Systemstatus</h4>

    <div class="bg-white border p-3 rounded small">
        <div class="row">
            <div class="col-md-6">
                <h6>Datenbank</h6>
                <ul>
                    <li><strong>Benutzer insgesamt:</strong> <?= $userCount ?></li>
                    <li><strong>Inaktive Benutzer:</strong> <?= $inactiveUsers ?></li>
                    <li><strong>Admin-Benutzer:</strong> <?= $adminCount ?></li>
                    <li><strong>Gespeicherte Links:</strong> <?= $linkCount ?></li>
                    <li><strong>Offene Linkanfragen:</strong> <?= $linkRequestCount ?></li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>System</h6>
                <ul>
                    <li><strong>PHP-Version:</strong> <?= $phpVersion ?></li>
                    <li><strong>Server-Software:</strong> <?= $serverSoftware ?></li>
                    <li><strong>Betriebssystem:</strong> <?= $os ?></li>
                    <li><strong>Server-Zeit:</strong> <?= date('Y-m-d H:i') ?></li>
                </ul>
            </div>
        </div>

        <h6 class="mt-3">Logdateien</h6>
        <ul>
            <li>audit.log: <?= $logAudit ?></li>
            <li>error.log: <?= $logError ?></li>
        </ul>
    </div>

    <div class="d-flex justify-content-start gap-2 mt-4">
        <a href="admin_status_mailsystem.php" class="btn btn-outline-secondary btn-sm">Mail-System</a>
        <a href="admin_userdump.php" class="btn btn-outline-secondary btn-sm">Benutzerdatenbank</a>
    </div>
</div>
</body>
</html>