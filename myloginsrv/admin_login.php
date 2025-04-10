<?php
session_start();

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new PDO('sqlite:users.db');
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username AND role = 'admin' LIMIT 1");
    $stmt->execute([':username' => $_POST['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user'] = $user['username'];
        $_SESSION['role'] = 'admin';
        file_put_contents("audit.log", date('c') . " ADMIN LOGIN: {$user['username']} FROM {$_SERVER['REMOTE_ADDR']}\n", FILE_APPEND);
        header("Location: admin.php");
        exit;
    } else {
        $error = "Ungültige Admin-Anmeldedaten.";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width: 400px; margin-top: 100px;">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">Admin Login</h4>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Benutzername</label>
                    <input type="text" class="form-control" name="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Passwort</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Anmelden</button>
            </form>
            <a href="index.php" class="btn btn-link mt-3">Zurück zur Startseite</a>
            <a href="login.php" class="btn btn-link mt-1">Normales Login</a>
        </div>
    </div>
</div>
</body>
</html>
