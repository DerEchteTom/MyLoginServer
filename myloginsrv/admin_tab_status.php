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
    return $ok ? '<span class="badge border border-success text-success">Yes</span>'
               : '<span class="badge border border-danger text-danger">No</span>';
}

function getEncStatus($file) {
    if (!file_exists($file)) return "❌ missing";
    $txt = file_get_contents($file);
    if (strpos($txt, 'ENC:') !== false || strpos($txt, 'XOR:') !== false) return "✅ yes";
    return "🔓 no";
}

function getMailerStatus() {
    $status = "No";
    $diag = [];

    $autoloadPath = __DIR__ . '/vendor/autoload.php';
    $cls = 'PHPMailer\\PHPMailer\\PHPMailer';

    if (file_exists($autoloadPath)) {
        $diag[] = "Autoload available";
        require_once $autoloadPath;
        if (class_exists($cls)) {
            $diag[] = "class loaded";
            if (method_exists($cls, 'send')) {
                $status = "Ja";
                $diag[] = "send()-method available";
            } else {
                $diag[] = "send()-method missing";
            }
        } else {
            $diag[] = "class missing";
        }
    } else {
        $diag[] = "autoload missing";
    }
    return [$status, $diag];
}

$dbFileStatus = checkFile("users.db");
$dbinfo = checkFile("info.db");
$dbcms = checkFile("cms.db");
$audit = checkFile("audit.log");
$error = checkFile("error.log");
$env = checkFile(".env");
$envad = checkFile(".envad");
$encryption = checkFile("encryption.key");

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
  <title>system status</title>
  <link href="./assets/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light"style="font-size: 0.95rem;">
<div class="container-fluid mt-4">
<?php include "admin_tab_nav.php"; ?>
<div style="width: 90%; margin: 0 auto;">
  <h5 class="mb-3">system status</h5>

  <div class="mb-3 text-dark fw-bold" style="font-size: 1rem;">
    logged in: <strong><?= htmlspecialchars($username) ?></strong>
    (Session-ID: <?= session_id() ?>)
  </div>

  <table class="table table-sm table-bordered bg-white shadow-sm">
    <thead class="table-light">
      <tr>
        <th>component</th>
        <th>exist</th>
        <th>readable</th>
        <th>writable</th>
        <th>Größesize</th>
        <th>encrypted</th>
      </tr>
    </thead>
    <tbody>
      <tr><td>users.db - user and links db</td><td><?= statusBadge($dbFileStatus['exists']) ?></td><td><?= statusBadge($dbFileStatus['readable']) ?></td><td><?= statusBadge($dbFileStatus['writable']) ?></td><td><?= $dbFileStatus['size'] ?> B</td><td>-</td></tr>
      <tr><td>cms.db - mini cms db</td><td><?= statusBadge($dbcms['exists']) ?></td><td><?= statusBadge($dbcms['readable']) ?></td><td><?= statusBadge($dbcms['writable']) ?></td><td><?= $dbcms['size'] ?> B</td><td>-</td></tr>
      <tr><td>encryption.key - your personal variant</td><td><?= statusBadge($encryption['exists']) ?></td><td><?= statusBadge($encryption['readable']) ?></td><td><?= statusBadge($encryption['writable']) ?></td><td><?= $encryption['size'] ?> B</td><td>-</td></tr>
      <tr><td>audit.log</td><td><?= statusBadge($audit['exists']) ?></td><td><?= statusBadge($audit['readable']) ?></td><td><?= statusBadge($audit['writable']) ?></td><td><?= $audit['size'] ?> B</td><td>-</td></tr>
      <tr><td>error.log</td><td><?= statusBadge($error['exists']) ?></td><td><?= statusBadge($error['readable']) ?></td><td><?= statusBadge($error['writable']) ?></td><td><?= $error['size'] ?> B</td><td>-</td></tr>
      <tr><td>.env - smtp config</td><td><?= statusBadge($env['exists']) ?></td><td><?= statusBadge($env['readable']) ?></td><td><?= statusBadge($env['writable']) ?></td><td><?= $env['size'] ?> B</td><td><?= $envEnc ?></td></tr>
      <tr><td>.envad - ad config</td><td><?= statusBadge($envad['exists']) ?></td><td><?= statusBadge($envad['readable']) ?></td><td><?= statusBadge($envad['writable']) ?></td><td><?= $envad['size'] ?> B</td><td><?= $envadEnc ?></td></tr>
    </tbody>
  </table>

  <h5 class="mt-4">server informations</h5>
  <ul class="list-group list-group-flush mb-4">
    <li class="list-group-item">PHP-version: <?= phpversion() ?></li>
    <li class="list-group-item">webserver: <?= $_SERVER['SERVER_SOFTWARE'] ?? 'unbekannt' ?></li>
    <li class="list-group-item">system: <?= PHP_OS ?></li>
    <li class="list-group-item">PHPMailer exist: <?= statusBadge($mailerStatus === "Ja") ?> – <?= implode(", ", $mailerDiag) ?></li>
  </ul>

  <h5 class="mt-4">Datenbank-Zusammenfassung</h5>
  <ul class="list-group list-group-flush mb-4">
    <li class="list-group-item">all users: <?= $totalUsers ?></li>
    <li class="list-group-item">active users: <?= $inactiveUsers ?></li>
    <li class="list-group-item">admin users: <?= $adminUsers ?></li>
    <li class="list-group-item">saved links: <?= $totalLinks ?></li>
    <li class="list-group-item">open linkrequests: <?= $openRequests ?></li>
  </ul>

  </div>
</body>
</html>
