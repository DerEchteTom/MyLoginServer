<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: links.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');

    if ($username && $password && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            $db = new PDO('sqlite:users.db');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $db->prepare("INSERT INTO users (username, password, email, role, active, redirect_urls) VALUES (:u, :p, :e, 'user', 1, '[]')");
            $stmt->execute([
                ':u' => $username,
                ':p' => password_hash($password, PASSWORD_DEFAULT),
                ':e' => $email
            ]);

            $_SESSION['user'] = $username;
            $_SESSION['role'] = 'user';
            file_put_contents("audit.log", date('c') . " REGISTER $username FROM {$_SERVER['REMOTE_ADDR']}\n", FILE_APPEND);
            header("Location: links.php");
            exit;
        } catch (PDOException $e) {
            $error = "Fehler bei der Registrierung: " . $e->getMessage();
        }
    } else {
        $error = "Bitte alle Felder korrekt ausfüllen.";
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
<div class="container mt-5" style="max-width: 500px;">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-4">Registrieren</h4>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Benutzername</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">E-Mail-Adresse</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Passwort</label>
                    <input type="password" class="form-control" id="password" name="password" required>
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
