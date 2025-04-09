<?php
session_start();
$error = "";
$success = false;
$token = $_GET['token'] ?? '';

if (!$token) {
    $error = "Kein Token angegeben.";
} else {
    $db = new PDO('sqlite:users.db');
    $stmt = $db->prepare("SELECT * FROM users WHERE reset_token = :token AND reset_expires > :now");
    $stmt->execute([':token' => $token, ':now' => time()]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = "Ungültiger oder abgelaufener Token.";
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pw = $_POST['password'] ?? '';
        $pw2 = $_POST['confirm'] ?? '';

        if (!$pw || $pw !== $pw2) {
            $error = "Passwörter stimmen nicht überein oder sind leer.";
        } else {
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $update = $db->prepare("UPDATE users SET password = :pw, reset_token = NULL, reset_expires = NULL WHERE id = :id");
            $update->execute([':pw' => $hash, ':id' => $user['id']]);
            file_put_contents("audit.log", date('c') . " PASSWORD RESET for {$user['username']} FROM {$_SERVER['REMOTE_ADDR']}\n", FILE_APPEND);
            $success = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Passwort zurücksetzen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 400px;">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-4">Neues Passwort setzen</h4>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php elseif ($success): ?>
                <div class="alert alert-success">Passwort geändert. <a href="login.php">Zum Login</a></div>
            <?php endif; ?>
            <?php if (!$success): ?>
                <form method="post">
                    <div class="mb-3">
                        <label for="password" class="form-label">Neues Passwort</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm" class="form-label">Passwort bestätigen</label>
                        <input type="password" class="form-control" id="confirm" name="confirm" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Speichern</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
