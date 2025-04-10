<?php
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$db = new PDO('sqlite:users.db');
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle'])) {
        $stmt = $db->prepare("UPDATE users SET active = NOT active WHERE id = :id");
        $stmt->execute([':id' => $_POST['toggle']]);
    }
    if (isset($_POST['create']) && !empty($_POST['newuser']) && !empty($_POST['newpass'])) {
        $stmt = $db->prepare("INSERT INTO users (username, password, email, role, active) VALUES (:username, :password, '', 'user', 1)");
        $stmt->execute([
            ':username' => $_POST['newuser'],
            ':password' => password_hash($_POST['newpass'], PASSWORD_DEFAULT)
        ]);
        $message = "Benutzer wurde erstellt.";
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
<div class="container mt-4" style="max-width: 800px;">
    <h3 class="mb-4">Benutzerverwaltung</h3>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <form method="post" class="row g-2 mb-4">
        <div class="col-md-4">
            <input type="text" name="newuser" class="form-control" placeholder="Benutzername" required>
        </div>
        <div class="col-md-4">
            <input type="password" name="newpass" class="form-control" placeholder="Passwort" required>
        </div>
        <div class="col-md-4">
            <button type="submit" name="create" value="1" class="btn btn-success w-100">Benutzer erstellen</button>
        </div>
    </form>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Benutzername</th>
                <th>Rolle</th>
                <th>Status</th>
                <th>Aktion</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= $user['role'] ?></td>
                    <td><?= $user['active'] ? 'Aktiv' : 'Inaktiv' ?></td>
                    <td>
                        <form method="post">
                            <button type="submit" name="toggle" value="<?= $user['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                <?= $user['active'] ? 'Deaktivieren' : 'Aktivieren' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="admin.php" class="btn btn-secondary">Zurück zum Adminbereich</a>
</div>
</body>
</html>