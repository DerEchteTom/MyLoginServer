<?php
// Datei: admin_tab_users.php – Stand: 2025-04-23 10:12 Europe/Berlin

date_default_timezone_set('Europe/Berlin');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireRole('admin');

$db = new PDO('sqlite:users.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$users = $db->query("SELECT * FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
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
    <?php include __DIR__ . '/admin_tab_nav.php'; ?>

    <h4 class="mb-4">Benutzerverwaltung</h4>

    <!-- Kopfzeile -->
    <div class="row fw-bold small border-bottom mb-2 pb-1">
        <div class="col-1">ID</div>
        <div class="col-3">Benutzername</div>
        <div class="col-3">E-Mail</div>
        <div class="col-2">Passwort</div>
        <div class="col-1">Rolle</div>
        <div class="col-1 text-center">Aktiv</div>
        <div class="col-1 text-end">Aktion</div>
    </div>

    <!-- Bearbeiten bestehender Benutzer -->
    <?php foreach ($users as $user): ?>
        <form method="post" action="admin_tab_users.php" class="row align-items-center bg-light border rounded mb-2 g-1 p-2">
            <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
            <div class="col-1"><?= htmlspecialchars($user['id']) ?></div>
            <div class="col-3"><input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="form-control form-control-sm"></div>
            <div class="col-3"><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control form-control-sm"></div>
            <div class="col-2"><input type="password" name="password" placeholder="neu" class="form-control form-control-sm"></div>
            <div class="col-1">
                <select name="role" class="form-select form-select-sm">
                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="col-1 text-center">
                <?php if (isset($user['username']) && $user['username'] !== 'admin'): ?>
                    <input type="checkbox" name="active" value="1" <?= $user['active'] ? 'checked' : '' ?>>
                <?php else: ?>ja<?php endif; ?>
            </div>
            <div class="col-1 text-end">
                <button type="submit" name="action" value="update" class="btn btn-outline-primary btn-sm">Speichern</button>
                <?php if (isset($user['username']) && $user['username'] !== 'admin'): ?>
                    <button type="submit" name="action" value="delete" class="btn btn-outline-danger btn-sm">Löschen</button>
                <?php endif; ?>
            </div>
        </form>
    <?php endforeach; ?>

    <!-- Neuen Benutzer hinzufügen -->
    <form method="post" action="admin_tab_users.php" class="row align-items-center bg-white border rounded mb-2 g-1 p-2">
        <input type="hidden" name="id" value="0">
        <div class="col-1 text-muted">neu</div>
        <div class="col-3"><input type="text" name="username" class="form-control form-control-sm" placeholder="Benutzername"></div>
        <div class="col-3"><input type="email" name="email" class="form-control form-control-sm" placeholder="E-Mail"></div>
        <div class="col-2"><input type="password" name="password" class="form-control form-control-sm" placeholder="Passwort"></div>
        <div class="col-1">
            <select name="role" class="form-select form-select-sm">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="col-1 text-center"><input type="checkbox" name="active" value="1" checked></div>
        <div class="col-1 text-end"><button type="submit" name="action" value="add" class="btn btn-outline-success btn-sm">Hinzufügen</button></div>
    </form>
</div>
</body>
</html>
