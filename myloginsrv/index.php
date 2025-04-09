<?php session_start(); ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>MyLoginSrv Startseite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 500px;">
    <div class="card">
        <div class="card-body text-center">
            <h3 class="mb-4">Willkommen bei MyLoginSrv</h3>
            <p class="mb-4">Wählen Sie eine Aktion:</p>
            <a href="login.php" class="btn btn-primary w-100 mb-2">Login</a>
            <a href="register.php" class="btn btn-outline-secondary w-100 mb-2">Registrieren</a>
            <a href="forgot.php" class="btn btn-outline-warning w-100 mb-2">Passwort vergessen</a>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="admin.php" class="btn btn-success w-100 mb-2">Adminbereich</a>
                <a href="logout.php" class="btn btn-danger w-100">Logout</a>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
