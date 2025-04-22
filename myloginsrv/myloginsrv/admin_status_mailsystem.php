<?php
// Datei: admin_status.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireRole('admin');

function columnExists($db, $table, $column) {
    $cols = $db->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_COLUMN, 1);
    return in_array($column, $cols);
}

$db = new PDO('sqlite:users.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$expectedTables = [
    'users',
    'user_links',
    'link_requests'
];

$requiredUserColumns = ['username', 'password', 'email', 'role', 'active', 'redirect_urls', 'reset_token', 'reset_expires'];
$requiredLinkRequestColumns = ['user_id', 'alias', 'url', 'created_at', 'status'];
$requiredUserLinksColumns = ['username', 'alias', 'url'];

$envPath = __DIR__ . '/.env';
$requiredEnvVars = [
    'SMTP_HOST', 'SMTP_PORT', 'SMTP_FROM', 'SMTP_SECURE',
    'SMTP_AUTH', 'SMTP_USER', 'SMTP_PASS', 'ADMIN_EMAIL', 'MAIL_TRUST_SELF_SIGNED'
];

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Systemstatus</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 800px;">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">Systemstatus & Strukturprüfung</h4>

            <h5>Datenbanktabellen</h5>
            <ul>
                <?php foreach ($expectedTables as $table): ?>
                    <li>
                        <?= $table ?>:
                        <?= $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'")->fetch() ? '<span class="text-success">✔︎ OK</span>' : '<span class="text-danger">✘ fehlt</span>' ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h5>Spalten in users</h5>
            <ul>
                <?php foreach ($requiredUserColumns as $col): ?>
                    <li><?= $col ?>:
                        <?= columnExists($db, 'users', $col) ? '<span class="text-success">✔︎</span>' : '<span class="text-danger">✘ fehlt</span>' ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h5>Spalten in user_links</h5>
            <ul>
                <?php foreach ($requiredUserLinksColumns as $col): ?>
                    <li><?= $col ?>:
                        <?= columnExists($db, 'user_links', $col) ? '<span class="text-success">✔︎</span>' : '<span class="text-danger">✘ fehlt</span>' ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h5>Spalten in link_requests</h5>
            <ul>
                <?php foreach ($requiredLinkRequestColumns as $col): ?>
                    <li><?= $col ?>:
                        <?= columnExists($db, 'link_requests', $col) ? '<span class="text-success">✔︎</span>' : '<span class="text-danger">✘ fehlt</span>' ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h5>.env-Datei</h5>
            <?php if (file_exists($envPath)): ?>
                <ul>
                    <?php
                    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    $keys = [];
                    foreach ($lines as $line) {
                        $parts = explode('=', $line, 2);
                        if (isset($parts[0])) $keys[] = trim($parts[0]);
                    }
                    foreach ($requiredEnvVars as $key):
                        $found = in_array($key, $keys);
                        ?>
                        <li><?= $key ?>: <?= $found ? '<span class="text-success">✔︎</span>' : '<span class="text-danger">✘ fehlt</span>' ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-danger">✘ .env-Datei nicht gefunden</p>
            <?php endif; ?>

            <h5>Logdateien</h5>
            <ul>
                <li>audit.log: <?= file_exists('audit.log') ? '<span class="text-success">✔︎ vorhanden</span>' : '<span class="text-danger">✘ fehlt</span>' ?></li>
                <li>error.log: <?= file_exists('error.log') ? '<span class="text-success">✔︎ vorhanden</span>' : '<span class="text-danger">✘ fehlt</span>' ?></li>
            </ul>

            <div class="mt-4">
                <a href="admin.php" class="btn btn-outline-secondary">Zurück zum Admin-Dashboard</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
