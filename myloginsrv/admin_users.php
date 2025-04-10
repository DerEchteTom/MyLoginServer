<?php
session_start();

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$db = new PDO('sqlite:users.db');

// Änderungen speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    <div class="mt-4">
        <a href="admin.php" class="btn btn-secondary">Zurück zum Adminbereich</a>
    </div>
</div>
</body>
</html>
