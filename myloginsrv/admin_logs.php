<?php
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$logfile = __DIR__ . '/audit.log';
$log = file_exists($logfile) ? file_get_contents($logfile) : "(Keine Logeinträge vorhanden)";
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Loganzeige</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            background: #f8f9fa;
            padding: 1rem;
            border: 1px solid #ddd;
            max-height: 500px;
            overflow: auto;
        }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 800px;">
    <h3 class="mb-4">Audit Log</h3>
    <pre><?= htmlspecialchars($log) ?></pre>
    <a href="admin.php" class="btn btn-secondary mt-3">Zurück zum Adminbereich</a>
</div>
</body>
</html>