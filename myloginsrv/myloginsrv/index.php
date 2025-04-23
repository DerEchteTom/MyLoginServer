<?php
// Datei: index.php – Stand: 2025-04-22 08:22 Europe/Berlin

date_default_timezone_set('Europe/Berlin');
session_start();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Startseite</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 600px;">
    <div class="card shadow-sm">
        <div class="card-body text-center">
            <h3 class="mb-4">Willkommen bei MyLoginSrv</h3>

            <?php if (isset($_SESSION['user']) && is_string($_SESSION['user'])): ?>
                <p class="text-muted mb-4">Angemeldet als <strong><?= htmlspecialchars($_SESSION['user']) ?></strong></p>
            <?php endif; ?>

            <div class="d-grid gap-2">
                <a href="login.php" class="btn btn-primary">Login</a>
                <?php if (isset($_SESSION['user']) && ($_SESSION['role'] ?? '') === 'admin'): ?>
                    <a href="admin.php" class="btn btn-outline-info">Adminbereich</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="logout.php" class="btn btn-outline-danger">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
