<?php
// Datei: admin_users.php
session_start();

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'link_utils.php';
$db = new PDO('sqlite:users.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error = $success = "";

// Benutzer löschen (nicht admin!)
if (isset($_POST['delete']) && isset($_POST['user_id'])) {
    $id = (int)$_POST['user_id'];
    $stmt = $db->prepare("SELECT username, role FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['role'] !== 'admin') {
        $db->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $id]);
        $db->prepare("DELETE FROM user_links WHERE user_id = :id")->execute([':id' => $id]);
        $success = "Benutzer '" . htmlspecialchars($user['username']) . "' gelöscht.";
    } else {
        $error = "Der Admin kann nicht gelöscht werden.";
    }
}

// Benutzer speichern
if (isset($_POST['save']) && isset($_POST['user_id'])) {
    $id = (int)$_POST['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $active = isset($_POST['active']) ? 1 : 0;

    $update = $db->prepare("UPDATE users SET username = :username, email = :email, role = :role, active = :active WHERE id = :id");
    $update->execute([
        ':username' => $username,
        ':email' => $email,
        ':role' => $role,
        ':active' => $active,
        ':id' => $id
    ]);

    if (!empty($_POST['new_password'])) {
        $pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $db->prepare("UPDATE users SET password = :p WHERE id = :id")
           ->execute([':p' => $pass, ':id' => $id]);
    }

    $success = "Benutzer aktualisiert.";
}

// Benutzer hinzufügen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['new_username']);
    $email = trim($_POST['new_email']);
    $password = $_POST['new_password'];

    if ($username && $email && $password) {
        try {
            $stmt = $db->prepare("INSERT INTO users (username, password, email) VALUES (:u, :p, :e)");
            $stmt->execute([
                ':u' => $username,
                ':p' => password_hash($password, PASSWORD_DEFAULT),
                ':e' => $email
            ]);
            $newUserId = $db->lastInsertId();
            addDefaultLinks($db, (int)$newUserId);
            $success = "Benutzer erfolgreich erstellt.";
        } catch (PDOException $e) {
            $error = "Fehler: Benutzername oder E-Mail existiert bereits.";
        }
    } else {
        $error = "Alle Felder ausfüllen.";
    }
}

$users = $db->query("SELECT * FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);
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

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" class="row g-2 mb-4">
        <input type="hidden" name="add_user" value="1">
        <div class="col-md-3">
            <input type="text" name="new_username" class="form-control" placeholder="Benutzername" required>
        </div>
        <div class="col-md-3">
            <input type="email" name="new_email" class="form-control" placeholder="E-Mail" required>
        </div>
        <div class="col-md-3">
            <input type="password" name="new_password" class="form-control" placeholder="Passwort" required>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100">Benutzer anlegen</button>
        </div>
    </form>

    <form method="post">
        <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th>Benutzername</th>
                    <th>E-Mail</th>
                    <th>Rolle</th>
                    <th>Passwort</th>
                    <th>Status aktiv</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><input type="text" name="username" class="form-control form-control-sm" value="<?= htmlspecialchars($user['username']) ?>" required></td>
                    <td><input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($user['email']) ?>" required></td>
                    <td>
                        <select name="role" class="form-select form-select-sm">
                            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>user</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
                        </select>
                    </td>
                    <td><input type="password" name="new_password" class="form-control form-control-sm" placeholder="Neues Passwort"></td>
                    <td class="text-center">
                        <input type="checkbox" name="active" value="1" <?= $user['active'] ? 'checked' : '' ?>>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <button name="save" value="1" class="btn btn-success btn-sm">Speichern</button>
                            <?php if ($user['role'] !== 'admin'): ?>
                                <button name="delete" value="1" class="btn btn-danger btn-sm" onclick="return confirm('Benutzer wirklich löschen?')">Löschen</button>
                            <?php endif; ?>
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>

    <a href="admin.php" class="btn btn-secondary">Zurück</a>
</div>
</body>
</html>
