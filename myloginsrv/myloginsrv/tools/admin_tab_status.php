<?php
// Datei: admin_tab_status.php – Stand: 2025-04-22 11:50 Europe/Berlin

date_default_timezone_set('Europe/Berlin');
require_once __DIR__ . '/auth.php';
requireRole('admin');

$env = __DIR__ . '/.env';
$envContent = file_exists($env) ? file_get_contents($env) : 'Datei .env nicht vorhanden.';

function readableSize($bytes) {
    $size = ['B','KB','MB','GB','TB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.1f", $bytes / pow(1024, $factor)) . " " . $size[$factor];
}

$stats = [
    'PHP-Version' => phpversion(),
    'PHP-SAPI' => php_sapi_name(),
    'Betriebssystem' => PHP_OS,
    'Aktueller Benutzer' => get_current_user(),
    'Zeitzone' => date_default_timezone_get(),
    'Datum/Zeit' => date("Y-m-d H:i:s"),
    'users.db' => file_exists('users.db') ? readableSize(filesize('users.db')) : 'nicht vorhanden',
    'audit.log' => file_exists('audit.log') ? readableSize(filesize('audit.log')) : 'nicht vorhanden',
    'error.log' => file_exists('error.log') ? readableSize(filesize('error.log')) : 'nicht vorhanden'
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
<div class="container-fluid mt-4">
    <?php include __DIR__ . '/admin_tab_nav.php'; ?>
    <h4 class="mb-4">Systemstatus</h4>

    <div class="table-responsive mb-4">
        <table class="table table-sm table-bordered bg-white">
            <thead class="table-light"><tr><th>Parameter</th><th>Wert</th></tr></thead>
            <tbody>
                <?php foreach ($stats as $key => $val): ?>
                <tr>
                    <td><?= htmlspecialchars($key) ?></td>
                    <td><?= htmlspecialchars($val) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <h6>Datenbanktabellen (users.db)</h6>
        <div class="bg-white border rounded p-3 small mb-4">
        <?php
        try {
            $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
            echo '<ul class="mb-0">';
            foreach ($tables as $t) {
                echo '<li>' . htmlspecialchars($t) . '</li>';
            }
            echo '</ul>';
        } catch (Exception $e) {
            echo 'Fehler beim Lesen der Tabellen: ' . htmlspecialchars($e->getMessage());
        }
        ?>
        </div>

        <h6>Letzte Zeilen aus audit.log und error.log</h6>
        <div class="row small">
            <div class="col-md-6">
                <div class="bg-white border p-2 mb-3"><strong>audit.log</strong><br>
                <pre style="max-height:200px; overflow:auto;"><?php
                if (file_exists('audit.log')) {
                    $lines = array_slice(file('audit.log'), -10);
                    echo htmlspecialchars(implode('', $lines));
                } else {
                    echo 'audit.log nicht gefunden.';
                }
                ?></pre>
                </div>
            </div>
            <div class="col-md-6">
                <div class="bg-white border p-2 mb-3"><strong>error.log</strong><br>
                <pre style="max-height:200px; overflow:auto;"><?php
                if (file_exists('error.log')) {
                    $lines = array_slice(file('error.log'), -10);
                    echo htmlspecialchars(implode('', $lines));
                } else {
                    echo 'error.log nicht gefunden.';
                }
                ?></pre>
                </div>
            </div>
        </div>
    </div>

    <h6>.env-Datei</h6>
    <pre class="bg-white border p-3 small" style="max-height: 300px; overflow: auto;"><?= htmlspecialchars($envContent) ?></pre>
</div>
<a href="admin_status_mailsystem.php" class="btn btn-outline-secondary btn-sm">Mail-Systemstatus</a>
</body>
</html>
