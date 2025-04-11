<?php
// Datei: register.php
session_start();

require_once 'link_utils.php';

$error = $success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');

    if (!$username || !$password || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Bitte alle Felder korrekt ausfüllen.";
    } else {
        try {
            $db = new PDO('sqlite:users.db');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $db->prepare("INSERT INTO users (username, password, email) VALUES (:u, :p, :e)");
            $stmt->execute([
                ':u' => $username,
                ':p' => password_hash($password, PASSWORD_DEFAULT),
                ':e' => $email
            ]);

            $userId = $db->lastInsertId();
            addDefaultLinks($db, (int)$userId);

            $success = "Registrierung erfolgreich. Du kannst dich jetzt anmelden.";
        } catch (PDOException $e) {
            $error = "Fehler: Benutzername oder E-Mail bereits vergeben.";
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
            <h4 class="mb-3">Registrieren</h4>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php elseif ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Benutzername</label>
                    <input type="text" class="form-control" name="username" id="username" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">E-Mail-Adresse</label>
                    <input type="email" class="form-control" name="email" id="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Passwort</label>
                    <input type="password" class="form-control" name="password" id="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Registrieren</button>
            </form>
            <div class="mt-3 text-center">
                <a href="login.php">Zurück zum Login</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
