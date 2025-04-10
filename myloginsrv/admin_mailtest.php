<?php
session_start();

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$db = new PDO('sqlite:users.db');

// Neuen Benutzer hinzufügen
if (isset($_POST['new_username'], $_POST['new_password']) && trim($_POST['new_username']) !== '') {
    $username = trim($_POST['new_username']);
    $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $role = $_POST['new_role'] ?? 'user';
    try {
        $stmt = $db->prepare("INSERT INTO users (username, password, role, active, redirect_urls) VALUES (:u, :p, :r, 1, '[]')");
        $stmt->execute([':u' => $username, ':p' => $password, ':r' => $role]);
        file_put_contents("audit.log", date('c') . " ADMIN CREATE {$_SESSION['user']} CREATED USER '$username' FROM {$_SERVER['REMOTE_ADDR']}\n", FILE_APPEND);
    } catch (Exception $e) {
        $error = "❌ Benutzer konnte nicht hinzugefügt werden: " . $e->getMessage();
    }
}

// Bestehende Benutzer bearbeiten
if (isset($_POST['users'])) {
    foreach ($_POST['users'] as $id => $data) {
        $active = isset($data['active']) ? 1 : 0;
        $urls = json_encode(array_filter(array_map('trim', explode("\n", $data['urls']))));
        $update = $db->prepare("UPDATE users SET active = :active, redirect_urls = :urls WHERE id = :id");
        $update->execute([':active' => $active, ':urls' => $urls, ':id' => $id]);
        file_put_contents("audit.log", date('c') . " ADMIN UPDATE {$_SESSION['user']} CHANGED USER #$id FROM {$_SERVER['REMOTE_ADDR']}\n", FILE_APPEND);
    }
}

$users = $db->query("SELECT * FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Benutzerverwaltung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2 class="mb-4">Benutzerverwaltung</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <table class="table table-bordered bg-white">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Benutzername</th>
                    <th>Rolle</th>
                    <th>Aktiv</th>
                    <th>Weiterleitungen (eine pro Zeile)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td><input type="checkbox" name="users[<?= $user['id'] ?>][active]" <?= $user['active'] ? 'checked' : '' ?>></td>
                        <td>
                            <textarea name="users[<?= $user['id'] ?>][urls]" class="form-control" rows="3"><?= htmlspecialchars(join("\n", json_decode($user['redirect_urls'] ?? '[]', true) ?? [])) ?></textarea>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit" class="btn btn-success">Änderungen speichern</button>
    </form>

    <hr class="my-4">
    <h4>➕ Neuen Benutzer anlegen</h4>
    <form method="post" class="row g-3">
        <div class="col-md-4">
            <input type="text" name="new_username" class="form-control" placeholder="Benutzername" required>
        </div>
        <div class="col-md-4">
            <input type="password" name="new_password" class="form-control" placeholder="Passwort" required>
        </div>
        <div class="col-md-2">
            <select name="new_role" class="form-select">
                <option value="user">user</option>
                <option value="admin">admin</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Benutzer hinzufügen</button>
        </div>
    </form>

    <div class="mt-4">
        <a href="admin.php" class="btn btn-secondary">Zurück zum Adminbereich</a>
    </div>
</div>
</body>
</html>
