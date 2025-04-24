<?php
// Datei: login.php – Stand: 2025-04-22 08:25 Europe/Berlin

date_default_timezone_set('Europe/Berlin');
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db = new PDO('sqlite:users.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username AND active = 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            file_put_contents("audit.log", date('c') . " LOGIN $username FROM " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n", FILE_APPEND);
            header("Location: " . ($user['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
            exit;
        } else {
            $error = "Benutzername oder Passwort ist falsch.";
            file_put_contents("error.log", date('c') . " FAILED LOGIN $username FROM " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n", FILE_APPEND);
        }
    } else {
        $error = "Bitte Benutzername und Passwort eingeben.";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 400px;">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-4">Login</h4>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
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
                <button type="submit" class="btn btn-primary w-100">Anmelden</button>
            </form>
            <div class="mt-3 text-center">
                <a href="forgot.php">Passwort vergessen?</a><br>
                <a href="register.php" class="btn btn-link mt-1">Registrieren</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
