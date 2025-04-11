<?php
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$logFile = 'audit.log';
$errorFile = 'error.log';
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['download'])) {
        $timestamp = date("Ymd_His");
        $archiveName = "log_backup_$timestamp.tar.gz";
        $archivePath = __DIR__ . "/$archiveName";

        $command = "tar -czf '" . $archivePath . "' '" . $logFile . "' '" . $errorFile . "' 2>/dev/null";
        shell_exec($command);
        if (file_exists($archivePath)) {
            $success = "Logdateien wurden archiviert als $archiveName.";
        } else {
            $success = "Archivierung fehlgeschlagen.";
        }
    }

    if (isset($_POST['clear'])) {
        file_put_contents($logFile, "");
        file_put_contents($errorFile, "");
        $success = "Alle Logdateien wurden geleert.";
    }
}

$auditContent = file_exists($logFile) ? file_get_contents($logFile) : "audit.log nicht gefunden.";
$errorContent = file_exists($errorFile) ? file_get_contents($errorFile) : "error.log nicht gefunden.";
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Logdateien anzeigen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 900px;">
    <h3 class="mb-4">System-Logs anzeigen</h3>

    <?php if ($success): ?>
        <div class="alert alert-info"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" class="mb-3 d-flex gap-2">
        <button type="submit" name="download" class="btn btn-primary">Logdateien speichern (tar.gz)</button>
        <button type="submit" name="clear" class="btn btn-danger" onclick="return confirm('Alle Logs wirklich löschen?');">Alle Logdateien leeren</button>
        <a href="admin.php" class="btn btn-secondary">Zurück zum Adminbereich</a>
    </form>

    <h5>Audit-Log:</h5>
    <pre class="bg-white border p-3 mb-4" style="max-height: 300px; overflow-y: auto; white-space: pre-wrap;">
<?= htmlspecialchars($auditContent) ?>
    </pre>

    <h5>Fehler-Log:</h5>
    <pre class="bg-white border p-3" style="max-height: 300px; overflow-y: auto; white-space: pre-wrap;">
<?= htmlspecialchars($errorContent) ?>
    </pre>
</div>
</body>
</html>
