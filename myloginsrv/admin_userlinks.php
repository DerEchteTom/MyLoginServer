<?php
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$db = new PDO('sqlite:users.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Benutzerliste holen
$users = $db->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

// Link löschen
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM user_links WHERE id = :id");
    $stmt->execute([':id' => $_GET['delete']]);
    header("Location: admin_userlinks.php?user_id=" . ($_GET['user_id'] ?? ''));
    exit;
}

// Link aktualisieren
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $stmt = $db->prepare("UPDATE user_links SET alias = :alias, url = :url WHERE id = :id");
    $stmt->execute([
        ':alias' => trim($_POST['alias']),
        ':url' => trim($_POST['url']),
        ':id' => $_POST['edit_id']
    ]);
    header("Location: admin_userlinks.php?user_id=" . $_POST['user_id']);
    exit;
}

// Link hinzufügen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['alias_add'], $_POST['url_add'])) {
    $stmt = $db->prepare("INSERT INTO user_links (user_id, alias, url) VALUES (:uid, :alias, :url)");
    $stmt->execute([
        ':uid' => $_POST['user_id'],
        ':alias' => trim($_POST['alias_add']),
        ':url' => trim($_POST['url_add'])
    ]);
    header("Location: admin_userlinks.php?user_id=" . $_POST['user_id']);
    exit;
}

// Links anzeigen
$selectedId = $_GET['user_id'] ?? $users[0]['id'] ?? null;
$assignedLinks = [];
if ($selectedId) {
    $stmt = $db->prepare("SELECT * FROM user_links WHERE user_id = :uid ORDER BY alias ASC");
    $stmt->execute([':uid' => $selectedId]);
    $assignedLinks = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Benutzer-Links verwalten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 700px;">
    <h3 class="mb-4">Benutzer-Links verwalten</h3>

    <form method="get" class="mb-4">
        <label for="user_id" class="form-label">Benutzer wählen:</label>
        <select name="user_id" class="form-select" onchange="this.form.submit()">
            <?php foreach ($users as $user): ?>
                <option value="<?= $user['id'] ?>" <?= $user['id'] == $selectedId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($user['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($selectedId): ?>
    <form method="post" class="row g-2 mb-3">
        <input type="hidden" name="user_id" value="<?= $selectedId ?>">
        <div class="col-md-5">
            <input type="text" name="alias_add" class="form-control" placeholder="Alias (Anzeigename)" required>
        </div>
        <div class="col-md-5">
            <input type="url" name="url_add" class="form-control" placeholder="Ziel-URL" required>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Hinzufügen</button>
        </div>
    </form>

    <table class="table table-bordered">
        <thead><tr><th>Alias</th><th>URL</th><th>Aktion</th></tr></thead>
        <tbody>
            <?php foreach ($assignedLinks as $link): ?>
                <tr>
                    <form method="post" class="row g-2 align-items-center">
                        <input type="hidden" name="edit_id" value="<?= $link['id'] ?>">
                        <input type="hidden" name="user_id" value="<?= $selectedId ?>">
                        <td><input type="text" name="alias" class="form-control" value="<?= htmlspecialchars($link['alias']) ?>"></td>
                        <td><input type="url" name="url" class="form-control" value="<?= htmlspecialchars($link['url']) ?>"></td>
                        <td class="d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-success">Speichern</button>
                            <a href="?user_id=<?= $selectedId ?>&delete=<?= $link['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Eintrag wirklich löschen?');">Löschen</a>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <a href="admin.php" class="btn btn-secondary mt-3">Zurück zum Adminbereich</a>
</div>
</body>
</html>
