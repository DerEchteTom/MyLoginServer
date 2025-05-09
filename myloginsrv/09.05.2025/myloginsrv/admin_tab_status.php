<?php
// Datei: admin_tab_status.php – Stand: 2025-05-09 13:35:12 Europe/Berlin
session_start();
date_default_timezone_set('Europe/Berlin');

// Komponentenzustände prüfen
function checkFile($path) {
    return [
        'exists' => file_exists($path),
        'readable' => is_readable($path),
        'writable' => is_writable($path),
        'size' => file_exists($path) ? filesize($path) : 0
    ];
}

function statusBadge($ok) {
    return $ok ? '<span class="badge border border-success text-success">Ja</span>'
               : '<span class="badge border border-danger text-danger">Nein</span>';
}

function getEncStatus($file) {
    if (!file_exists($file)) return "❌ fehlt";
    $txt = file_get_contents($file);
    if (strpos($txt, 'ENC:') !== false || strpos($txt, 'XOR:') !== false) return "✅ verschlüsselt";
    return "🔓 unverschlüsselt";
}

function getMailerStatus() {
    $status = "Nein";
    $diag = [];

    $autoloadPath = __DIR__ . '/vendor/autoload.php';
    $cls = 'PHPMailer\\PHPMailer\\PHPMailer';

    if (file_exists($autoloadPath)) {
        $diag[] = "Autoload vorhanden";
        require_once $autoloadPath;
        if (class_exists($cls)) {
            $diag[] = "Klasse geladen";
            if (method_exists($cls, 'send')) {
                $status = "Ja";
                $diag[] = "send()-Methode vorhanden";
            } else {
                $diag[] = "send()-Methode fehlt";
            }
        } else {
            $diag[] = "Klasse fehlt";
        }
    } else {
        $diag[] = "Autoload fehlt";
    }
    return [$status, $diag];
}

$dbFileStatus = checkFile("users.db");
$audit = checkFile("audit.log");
$error = checkFile("error.log");
$env = checkFile(".env");
$envad = checkFile(".envad");

$envEnc = getEncStatus(".env");
$envadEnc = getEncStatus(".envad");

list($mailerStatus, $mailerDiag) = getMailerStatus();

// Sitzungen zählen
$sessionDir = session_save_path();
$active = 0;
$admins = 0;
$users = 0;

if (is_dir($sessionDir)) {
    foreach (scandir($sessionDir) as $file) {
        if (strpos($file, 'sess_') === 0) {
            $data = @file_get_contents("$sessionDir/$file");
            if ($data && preg_match('/role\|s:\d+:"(admin|user)"/', $data, $m)) {
                $active++;
                if ($m[1] === 'admin') $admins++;
                if ($m[1] === 'user') $users++;
            }
        }
    }
}

$dbh = new PDO("sqlite:users.db");
$totalUsers = $dbh->query("SELECT COUNT(*) FROM users")->fetchColumn();
$inactiveUsers = $dbh->query("SELECT COUNT(*) FROM users WHERE active = 0")->fetchColumn();
$adminUsers = $dbh->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$totalLinks = $dbh->query("SELECT COUNT(*) FROM user_links")->fetchColumn();
$openRequests = $dbh->query("SELECT COUNT(*) FROM link_requests WHERE status = 'open'")->fetchColumn();
$username = $_SESSION['username'] ?? '-';
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Systemstatus</title>
  <link href="./assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light" style="font-size: 0.95rem;">
<?php if (file_exists(__DIR__ . "/admin_tab_nav.php")) include __DIR__ . "/admin_tab_nav.php"; ?>
<div class="container-fluid mt-4">
  <h5 class="mb-3">Systemstatus</h5>

  <div class="mb-3 text-dark fw-bold" style="font-size: 1.25rem;">
    Angemeldet als: <strong><?= htmlspecialchars($username) ?></strong>
    (Session-ID: <?= session_id() ?>)
  </div>

  <table class="table table-sm table-bordered bg-white shadow-sm">
    <thead class="table-light">
      <tr>
        <th>Komponente</th>
        <th>Existiert</th>
        <th>Lesbar</th>
        <th>Schreibbar</th>
        <th>Größe</th>
        <th>Verschlüsselt</th>
      </tr>
    </thead>
    <tbody>
      <tr><td>users.db</td><td><?= statusBadge($dbFileStatus['exists']) ?></td><td><?= statusBadge($dbFileStatus['readable']) ?></td><td><?= statusBadge($dbFileStatus['writable']) ?></td><td><?= $dbFileStatus['size'] ?> B</td><td>-</td></tr>
      <tr><td>audit.log</td><td><?= statusBadge($audit['exists']) ?></td><td><?= statusBadge($audit['readable']) ?></td><td><?= statusBadge($audit['writable']) ?></td><td><?= $audit['size'] ?> B</td><td>-</td></tr>
      <tr><td>error.log</td><td><?= statusBadge($error['exists']) ?></td><td><?= statusBadge($error['readable']) ?></td><td><?= statusBadge($error['writable']) ?></td><td><?= $error['size'] ?> B</td><td>-</td></tr>
      <tr><td>.env</td><td><?= statusBadge($env['exists']) ?></td><td><?= statusBadge($env['readable']) ?></td><td><?= statusBadge($env['writable']) ?></td><td><?= $env['size'] ?> B</td><td><?= $envEnc ?></td></tr>
      <tr><td>.envad</td><td><?= statusBadge($envad['exists']) ?></td><td><?= statusBadge($envad['readable']) ?></td><td><?= statusBadge($envad['writable']) ?></td><td><?= $envad['size'] ?> B</td><td><?= $envadEnc ?></td></tr>
    </tbody>
  </table>

  <h5 class="mt-4">Serverinformationen</h5>
  <ul class="list-group list-group-flush mb-4">
    <li class="list-group-item">PHP-Version: <?= phpversion() ?></li>
    <li class="list-group-item">Webserver: <?= $_SERVER['SERVER_SOFTWARE'] ?? 'unbekannt' ?></li>
    <li class="list-group-item">System: <?= PHP_OS ?></li>
    <li class="list-group-item">PHPMailer erkannt: <?= statusBadge($mailerStatus === "Ja") ?> – <?= implode(", ", $mailerDiag) ?></li>
  </ul>

  <h5 class="mt-4">Datenbank-Zusammenfassung</h5>
  <ul class="list-group list-group-flush mb-4">
    <li class="list-group-item">Benutzer insgesamt: <?= $totalUsers ?></li>
    <li class="list-group-item">Inaktive Benutzer: <?= $inactiveUsers ?></li>
    <li class="list-group-item">Admin-Benutzer: <?= $adminUsers ?></li>
    <li class="list-group-item">Gespeicherte Links: <?= $totalLinks ?></li>
    <li class="list-group-item">Offene Linkanfragen: <?= $openRequests ?></li>
  </ul>

  </div>
</body>
</html>
