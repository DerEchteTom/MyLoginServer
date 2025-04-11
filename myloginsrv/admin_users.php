<?php
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$db = new PDO('sqlite:users.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$currentUser = $_SESSION['user'];
$currentId = $db->query("SELECT id FROM users WHERE username = " . $db->quote($currentUser))->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        file_put_contents("audit.log", date('c') . " ADMIN created user: {$_POST['username']} FROM {$_SERVER['REMOTE_ADDR']}
", FILE_APPEND);
        $stmt = $db->prepare("INSERT INTO users (username, password, email, role, active, redirect_urls) VALUES (:u, :p, :e, :r, :a, '[]')");
        $stmt->execute([
            ':u' => trim($_POST['username']),
            ':p' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            ':e' => trim($_POST['email']),
            ':r' => $_POST['role'],
            ':a' => $_POST['active'] === '1' ? 1 : 0
        ]);
    } elseif (isset($_POST['update'])) {
        $stmt = $db->prepare("UPDATE users SET email = :email, role = :role, active = :active WHERE id = :id");
        $stmt->execute([
            ':email' => trim($_POST['email']),
            ':role' => $_POST['role'],
            ':active' => $_POST['active'] === '1' ? 1 : 0,
            ':id' => $_POST['id']
        ]);

        if (!empty($_POST['password'])) {
            file_put_contents("audit.log", date('c') . " ADMIN updated password for user ID {$_POST['id']} FROM {$_SERVER['REMOTE_ADDR']}
", FILE_APPEND);
            $stmt = $db->prepare("UPDATE users SET password = :p WHERE id = :id");
            $stmt->execute([
                ':p' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                ':id' => $_POST['id']
            ]);
        }
    } elseif (isset($_POST['delete']) && is_numeric($_POST['id']) && $_POST['id'] != $currentId) {
        file_put_contents("audit.log", date('c') . " ADMIN deleted user ID {$_POST['id']} FROM {$_SERVER['REMOTE_ADDR']}
", FILE_APPEND);
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $_POST['id']]);
    }
    file_put_contents("audit.log", date('c') . " ADMIN action by $currentUser
", FILE_APPEND);
    header("Location: admin_users.php");
    exit;
}

$users = $db->query("SELECT id, username, email, role, active FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Benutzerverwaltung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3 class="mb-4">Benutzerverwaltung</h3>
    <form method="post" class="mb-4 row g-2">
        <input type="hidden" name="create" value="1">
        <div class="col-md-2">
            <input type="text" name="username" class="form-control" placeholder="Benutzername" required>
        </div>
        <div class="col-md-2">
            <input type="email" name="email" class="form-control" placeholder="E-Mail" required>
        </div>
        <div class="col-md-2">
            <input type="password" name="password" class="form-control" placeholder="Passwort" required>
        </div>
        <div class="col-md-2">
            <select name="role" class="form-select">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="active" class="form-select">
                <option value="1">Aktiv</option>
                <option value="0">Inaktiv</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-success w-100">Neu anlegen</button>
        </div>
    </form>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Benutzername</th>
                <th>E-Mail</th>
                <th>Rolle</th>
                <th>Aktiv</th>
                <th>Neues Passwort</th>
                <th>Aktion</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <form method="post" class="align-middle">
                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>"></td>
                        <td>
                            <select name="role" class="form-select">
                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </td>
                        <td>
                            <select name="active" class="form-select">
                                <option value="1" <?= $user['active'] ? 'selected' : '' ?>>Aktiv</option>
                                <option value="0" <?= !$user['active'] ? 'selected' : '' ?>>Inaktiv</option>
                            </select>
                        </td>
                        <td><input type="password" name="password" class="form-control" placeholder="Neues Passwort"></td>
                        <td class="d-flex gap-2">
                            <button type="submit" name="update" class="btn btn-primary btn-sm">Speichern</button>
                            <?php if ($user['id'] != $currentId): ?>
                                <button type="submit" name="delete" class="btn btn-danger btn-sm" onclick="return confirm('Benutzer wirklich löschen?');">Löschen</button>
                            <?php endif; ?>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="admin.php" class="btn btn-secondary mt-3">Zurück zum Adminbereich</a>
</div>
</body>
</html>
