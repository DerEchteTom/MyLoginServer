<?php
// File: dashboard.php – Version: 2025-05-17_01 – Role-based redirect with debug view
date_default_timezone_set('Europe/Berlin');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth.php';

$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? 'user';

if (!$username) {
    header("Location: login.php");
    exit;
}

$target = ($role === 'admin') ? 'admin.php' : 'links.php';

// Debug mode disables auto-redirect
$noRedirect = isset($_GET['debug']) || isset($_GET['noredirect']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <?php if (!$noRedirect): ?>
    <meta http-equiv="refresh" content="5; URL=<?= htmlspecialchars($target) ?>">
    <?php endif; ?>
</head>
<body class="bg-light">
<div class="container-fluid mt-5" style="max-width: 800px;">
    <h4 class="mb-3">Welcome, <?= htmlspecialchars($username) ?></h4>
    <p class="text-muted">You will be redirected shortly to <code><?= htmlspecialchars($target) ?></code> ...</p>

    <div class="alert alert-info small mt-3">
        <strong>Role:</strong> <?= htmlspecialchars($role) ?><br>
        <strong>Time:</strong> <?= date('Y-m-d H:i:s') ?><br>
        <strong>Status:</strong> This page can be used for future info/debug output.
    </div>

    <?php if ($noRedirect): ?>
    <div class="alert alert-warning small mt-3">
        <strong>Debug mode:</strong> No automatic redirect.<br>
        Click the button below to proceed manually.
    </div>
    <?php endif; ?>

    <p><a href="<?= htmlspecialchars($target) ?>" class="btn btn-sm btn-outline-primary mt-2">Go now &raquo;</a></p>
</div>
</body>
</html>
