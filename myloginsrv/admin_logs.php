<?php
session_start();

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$feedback = [];

function handleLogAction($logfile, $action, &$feedback) {
    $logfile = basename($logfile); // prevent path traversal
    if (!in_array($logfile, ['audit.log', 'error.log'])) return;
    $fullPath = __DIR__ . "/$logfile";

    if (!file_exists($fullPath)) file_put_contents($fullPath, "");

    if ($action === 'clear') {
        file_put_contents($fullPath, "");
        $feedback[] = "$logfile wurde geleert.";
    } elseif ($action === 'download') {
        $timestamp = date("Ymd_His");
        $copyPath = __DIR__ . "/{$logfile}_$timestamp.tar.gz";
        $result = exec("tar -czf $copyPath -C " . dirname($fullPath) . " $logfile", $output, $ret);
        if ($ret === 0 && file_exists($copyPath)) {
            $feedback[] = "$logfile wurde gesichert als: {$logfile}_$timestamp.tar.gz";
        } else {
            $feedback[] = "Fehler beim Sichern von $logfile.";
            file_put_contents("error.log", date('c') . " Fehler beim Sichern von $logfile\n", FILE_APPEND);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['audit_action'])) {
        handleLogAction('audit.log', $_POST['audit_action'], $feedback);
    }
    if (isset($_POST['error_action'])) {
        handleLogAction('error.log', $_POST['error_action'], $feedback);
    }
}

$audit_log = file_exists("audit.log") ? file_get_contents("audit.log") : "Logdatei nicht gefunden.";
$error_log = file_exists("error.log") ? file_get_contents("error.log") : "Logdatei nicht gefunden.";
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Logdateien verwalten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3 class="mb-4">Logdateien</h3>

    <?php if (!empty($feedback)): ?>
        <div class="alert alert-info">
            <?= implode("<br>", array_map('htmlspecialchars', $feedback)) ?>
        </div>
    <?php endif; ?>

    <div class="mb-4">
        <h5>Audit Log</h5>
        <form method="post" class="mb-2">
            <button name="audit_action" value="clear" class="btn btn-warning btn-sm">Audit Log löschen</button>
            <button name="audit_action" value="download" class="btn btn-outline-secondary btn-sm">Audit Log sichern</button>
        </form>
        <pre class="bg-white border p-3" style="max-height: 300px; overflow: auto; white-space: pre-wrap;">
<?= htmlspecialchars($audit_log) ?>
        </pre>
    </div>
    <div class="mb-4">
        <h5>Error Log</h5>
        <form method="post" class="mb-2">
            <button name="error_action" value="clear" class="btn btn-warning btn-sm">Error Log löschen</button>
            <button name="error_action" value="download" class="btn btn-outline-secondary btn-sm">Error Log sichern</button>
        </form>
        <pre class="bg-white border p-3" style="max-height: 300px; overflow: auto; white-space: pre-wrap;">
<?= htmlspecialchars($error_log) ?>
        </pre>
    </div>
    <a href="admin.php" class="btn btn-secondary">Zurück</a>
</div>
</body>
</html>
