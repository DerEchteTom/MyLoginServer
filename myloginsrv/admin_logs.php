<?php
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$logfile = 'audit.log';
$log = file_exists($logfile) ? file_get_contents($logfile) : "(Noch keine Logeinträge vorhanden)";
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Audit-Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>pre { white-space: pre-wrap; word-break: break-word; }</style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <h4 class="mb-4">📄 Audit-Log</h4>
    <div class="bg-white border p-3 rounded shadow-sm">
        <pre><?= htmlspecialchars($log) ?></pre>
    </div>
    <a href="admin.php" class="btn btn-secondary mt-4">Zurück zum Adminbereich</a>
</div>
</body>
</html>