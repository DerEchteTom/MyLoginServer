<?php
// Datei: admin_tab_users.php – Stand: 2025-04-22 16:09 Europe/Berlin

date_default_timezone_set('Europe/Berlin');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireRole('admin');

$db = new PDO('sqlite:users.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$info = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $active = isset($_POST['active']) ? 1 : 0;

    try {
        if ($action === 'save' && $id > 0) {
            $stmt = $db->prepare("UPDATE users SET username = :u, email = :e, role = :r, active = :a WHERE id = :id");
            $stmt->execute([':u' => $username, ':e' => $email, ':r' => $role, ':a' => $active, ':id' => $id]);
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $db->prepare("UPDATE users SET password = :p WHERE id = :id")->execute([':p' => $hash, ':id' => $id]);
            }
            $info = "Benutzer ID $id aktualisiert.";
        } elseif ($action === 'delete' && $id > 0) {
            $db->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $id]);
            $info = "Benutzer gelöscht.";
        } elseif ($action === 'add') {
            if ($username && $email && $password) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, password, email, role, active) VALUES (:u, :p, :e, :r, :a)");
                $stmt->execute([':u' => $username, ':p' => $hash, ':e' => $email, ':r' => $role, ':a' => $active]);
                $info = "Neuer Benutzer '$username' wurde angelegt.";
            } else {
                $error = "Bitte Benutzername, Passwort und E-Mail angeben.";
            }
        }
    } catch (Exception $e) {
        $error = "Fehler: " . $e->getMessage();
    }
}

$users = $db->query("SELECT * FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Benutzerverwaltung</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container-fluid mt-4">
    <?php if (file_exists(__DIR__ . '/admin_tab_nav.php')) include __DIR__ . '/admin_tab_nav.php'; ?>

    <h4 class="mb-4">Benutzerverwaltung</h4>

    <?php if ($info): ?><div class="alert alert-success small"><?= htmlspecialchars($info) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Benutzer hinzufügen -->
    <form method="post" class="bg-white border rounded p-3 mb-3">
        <div class="row gx-2 gy-1 mb-1">
            <div class="col">
                <label class="form-label small mb-0">Benutzername</label>
                <input type="text" name="username" class="form-control form-control-sm" required>
            </div>
            <div class="col">
                <label class="form-label small mb-0">E-Mail</label>
                <input type="email" name="email" class="form-control form-control-sm" required>
            </div>
            <div class="col">
                <label class="form-label small mb-0">Passwort</label>
                <input type="password" name="password" class="form-control form-control-sm" required>
            </div>
            <div class="col">
                <label class="form-label small mb-0">Rolle</label>
                <select name="role" class="form-select form-select-sm">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="col text-center">
                <label class="form-label small mb-0 d-block">Aktiv</label>
                <input type="checkbox" name="active" value="1" checked>
            </div>
            <div class="col">
                <label class="form-label small mb-0">&nbsp;</label>
                <button type="submit" name="action" value="add" class="btn btn-sm btn-outline-success w-100">Hinzufügen</button>
            </div>
        </div>
    </form>

    <!-- Bestehende Benutzer -->
    <div class="row gx-2 gy-1 small text-muted mb-1 fw-bold">
        <div class="col-1">ID</div>
        <div class="col">Benutzername</div>
        <div class="col">E-Mail</div>
        <div class="col">Passwort</div>
        <div class="col">Rolle</div>
        <div class="col text-center">Aktiv</div>
        <div class="col-2 text-end">Aktion</div>
    </div>

    <?php foreach ($users as $u): ?>
        <form method="post" class="bg-light border rounded p-2 mb-2">
            <input type="hidden" name="id" value="<?= $u['id'] ?>">
            <div class="row gx-2 gy-1 align-items-center">
                <div class="col-1 text-muted"><?= $u['id'] ?></div>
                <div class="col"><input type="text" name="username" class="form-control form-control-sm" value="<?= htmlspecialchars($u['username']) ?>"></div>
                <div class="col"><input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($u['email']) ?>"></div>
                <div class="col"><input type="password" name="password" class="form-control form-control-sm" placeholder="Passwort (leer = bleibt)"></div>
                <div class="col">
                    <select name="role" class="form-select form-select-sm">
                        <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="col text-center">
                    <input type="checkbox" name="active" value="1" <?= $u['active'] ? 'checked' : '' ?>>
                </div>
                <div class="col-2 text-end">
                    <button type="submit" name="action" value="save" class="btn btn-sm btn-outline-primary">Speichern</button>
                    <?php if ($u['username'] !== 'admin'): ?>
                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger">Löschen</button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    <?php endforeach; ?>
</div>
</body>
</html>
