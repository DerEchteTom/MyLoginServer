<?php
session_start();

$error = "";
$success = "";
$token = $_GET['token'] ?? '';

if ($token === '') {
    $error = "Kein gültiger Reset-Token übergeben.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass = $_POST['password'] ?? '';
    if (strlen($newPass) < 6) {
        $error = "Das Passwort muss mindestens 6 Zeichen lang sein.";
    } else {
        $db = new PDO('sqlite:users.db');
        $stmt = $db->prepare("SELECT * FROM users WHERE reset_token = :token AND reset_expires > :now LIMIT 1");
        $stmt->execute([':token' => $token, ':now' => time()]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password = :pass, reset_token = NULL, reset_expires = NULL WHERE id = :id")
               ->execute([':pass' => $hashed, ':id' => $user['id']]);
            file_put_contents("audit.log", date('c') . " PASSWORD RESET SUCCESS: {$user['username']} FROM {$_SERVER['REMOTE_ADDR']}\n", FILE_APPEND);
            $success = "Das Passwort wurde erfolgreich geändert. Du kannst dich nun einloggen.";
        } else {
            $error = "Der Reset-Link ist ungültig oder abgelaufen.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Neues Passwort setzen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 500px;">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">Neues Passwort setzen</h4>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

            <?php if (!$success && $token): ?>
            <form method="post">
                <div class="mb-3">
                    <label for="password" class="form-label">Neues Passwort</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Passwort speichern</button>
            </form>
            <?php endif; ?>

            <a href="login.php" class="btn btn-link mt-3">Zurück zum Login</a>
        </div>
    </div>
</div>
</body>
</html>