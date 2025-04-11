<?php
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$db = new PDO('sqlite:users.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $stmt = $db->prepare("UPDATE users SET email = :email, active = :active WHERE id = :id");
    $stmt->execute([
        ':email' => trim($_POST['email']),
        ':active' => $_POST['active'] === '1' ? 1 : 0,
        ':id' => $_POST['id']
    ]);
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
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Benutzername</th>
                <th>E-Mail</th>
                <th>Rolle</th>
                <th>Aktiv</th>
                <th>Aktion</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <form method="post">
                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>"></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td>
                            <select name="active" class="form-select">
                                <option value="1" <?= $user['active'] ? 'selected' : '' ?>>Aktiv</option>
                                <option value="0" <?= !$user['active'] ? 'selected' : '' ?>>Inaktiv</option>
                            </select>
                        </td>
                        <td><button type="submit" class="btn btn-primary btn-sm">Speichern</button></td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="admin.php" class="btn btn-secondary mt-3">Zurück zum Adminbereich</a>
</div>
</body>
</html>
