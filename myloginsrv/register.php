<?php
session_start();
$error = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (!$username || !$password || !$confirm) {
        $error = "Bitte füllen Sie alle Felder aus.";
    } elseif ($password !== $confirm) {
        $error = "Die Passwörter stimmen nicht überein.";
    } else {
        $db = new PDO('sqlite:users.db');
        $stmt = $db->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        if ($stmt->fetch()) {
            $error = "Benutzername bereits vergeben.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $db->prepare("INSERT INTO users (username, password, role, active, redirect_urls) VALUES (:u, :p, 'user', 1, '[]')");
            $insert->execute([':u' => $username, ':p' => $hash]);
            file_put_contents("audit.log", date('c') . " REGISTER $username FROM {$_SERVER['REMOTE_ADDR']}\n", FILE_APPEND);
            $success = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Registrieren</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 400px;">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-4">Registrieren</h4>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php elseif ($success): ?>
                <div class="alert alert-success">Registrierung erfolgreich. <a href="login.php">Zum Login</a></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Benutzername</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Passwort</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm" class="form-label">Passwort bestätigen</label>
                    <input type="password" class="form-control" id="confirm" name="confirm" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Registrieren</button>
            </form>
            <div class="mt-3 text-center">
                <a href="login.php">Zum Login</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
