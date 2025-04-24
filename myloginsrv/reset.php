<?php
// Datei: reset.php – Stand: 2025-04-23 10:12 Europe/Berlin

date_default_timezone_set('Europe/Berlin');
session_start();

$error = '';
$info = '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'], $_POST['token'])) {
    $newPassword = $_POST['password'];
    $token = $_POST['token'];

    if (strlen($newPassword) < 6) {
        $error = "Passwort muss mindestens 6 Zeichen lang sein.";
    } else {
        $db = new PDO('sqlite:users.db');
        $stmt = $db->prepare("SELECT * FROM users WHERE reset_token = :t AND reset_expires > :now");
        $stmt->execute([':t' => $token, ':now' => time()]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $db->prepare("UPDATE users SET password = :p, reset_token = NULL, reset_expires = NULL WHERE id = :id");
            $update->execute([':p' => $hash, ':id' => $user['id']]);
            file_put_contents("audit.log", date('c') . " Passwort zurückgesetzt für Benutzer {$user['username']}
", FILE_APPEND);
            $info = "Dein Passwort wurde erfolgreich geändert. Du kannst dich jetzt anmelden.";
        } else {
            $error = "Ungültiger oder abgelaufener Token.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Passwort zurücksetzen</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 500px;">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">Passwort ändern</h4>

            <?php if ($error): ?><div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($info): ?>
                <div class="alert alert-success small"><?= htmlspecialchars($info) ?></div>
                <a href="login.php" class="btn btn-primary w-100">Zum Login</a>
            <?php elseif ($token): ?>
                <form method="post">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <div class="mb-3">
                        <label class="form-label">Neues Passwort</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Passwort ändern</button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning small">Kein gültiger Link oder Token übergeben.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
