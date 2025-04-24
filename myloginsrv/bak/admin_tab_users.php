<?php
// Datei: admin_tab_users.php – Stand: 2025-04-24 11:45 Uhr Europe/Berlin

date_default_timezone_set('Europe/Berlin');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mailer_config.php';
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
            file_put_contents("audit.log", date('c') . " Benutzer ID $id aktualisiert durch " . ($_SESSION['user'] ?? 'system') . "\n", FILE_APPEND);
            $info = "Benutzer ID $id aktualisiert.";
        } elseif ($action === 'delete' && $id > 0 && $id != 1) {
            $db->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $id]);
            file_put_contents("audit.log", date('c') . " Benutzer ID $id gelöscht durch " . ($_SESSION['user'] ?? 'system') . "\n", FILE_APPEND);
            $info = "Benutzer gelöscht.";
        } elseif ($action === 'add') {
            if ($username && $email && $password) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, password, email, role, active) VALUES (:u, :p, :e, :r, :a)");
                $stmt->execute([':u' => $username, ':p' => $hash, ':e' => $email, ':r' => $role, ':a' => $active]);
                file_put_contents("audit.log", date('c') . " Neuer Benutzer '$username' wurde angelegt durch " . ($_SESSION['user'] ?? 'system') . "\n", FILE_APPEND);
                $info = "Neuer Benutzer '$username' wurde angelegt.";

                if (!empty($email) && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    $mail = getMailer($email, "Willkommen bei MyLoginSrv");
                    if ($mail) {
                        $mail->Body = "Hallo $username,\n\nDein Zugang wurde vom Administrator eingerichtet.\n\nLogin: http://localhost:8080/login.php\n\nViele Grüße";
                        try {
                            $mail->send();
                            file_put_contents("audit.log", date('c') . " Willkommensmail an $email gesendet\n", FILE_APPEND);
                        } catch (Exception $e) {
                            file_put_contents("error.log", date('c') . " Fehler beim Senden an $email: " . $mail->ErrorInfo . "\n", FILE_APPEND);
                        }
                    }
                }
            } else {
                $error = "Bitte Benutzername, Passwort und E-Mail angeben.";
            }
        }
    } catch (Exception $e) {
        $error = "Fehler: " . $e->getMessage();
        file_put_contents("error.log", date('c') . " Fehler in admin_tab_users.php: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// Suche und Paging
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $totalStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username LIKE :s OR email LIKE :s");
    $totalStmt->execute([':s' => "%$search%"]);
    $total = $totalStmt->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM users WHERE username LIKE :s OR email LIKE :s ORDER BY id LIMIT :l OFFSET :o");
    $stmt->bindValue(':s', "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $ex) {
    $users = [];
    file_put_contents("error.log", date("c") . " Fehler beim Abruf der Benutzer: " . $ex->getMessage() . "\n", FILE_APPEND);
}
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

    <!-- Suche -->
    <div class="bg-white border rounded p-3 mb-4">
        <?php include __DIR__ . '/admin_upload_users.php'; ?>
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
    
    </div>

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

    <?php if (!empty($users)): foreach ($users as $u): if (!isset($u['id']) || !is_numeric($u['id'])) continue; ?>
        <form method="post" class="bg-light border rounded p-2 mb-2">
            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <div class="row gx-2 gy-1 align-items-center">
                <div class="col-1 text-muted"><?= $u['id'] ?></div>
                <div class="col"><input type="text" name="username" class="form-control form-control-sm" value="<?= htmlspecialchars($u['username']) ?>"></div>
                <div class="col"><input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($u['email']) ?>"></div>
                <div class="col"><input type="password" name="password" class="form-control form-control-sm" placeholder="Passwort (leer = bleibt)"></div>
                <div class="col">
                    <?php if ($u['id'] == 1): ?>
                        <input type="hidden" name="role" value="admin">
                        <div class="form-control-plaintext small">Admin</div>
                    <?php else: ?>
                        <select name="role" class="form-select form-select-sm">
                            <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>User</option>
                            <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    <?php endif; ?>
                </div>
                <div class="col text-center">
                    <?php if ($u['id'] == 1): ?>
                        <input type="hidden" name="active" value="1">
                        <div class="form-control-plaintext small text-center">immer aktiv</div>
                    <?php else: ?>
                        <input type="checkbox" name="active" value="1" <?= $u['active'] ? 'checked' : '' ?>>
                    <?php endif; ?>
                </div>
                <div class="col-2 text-end">
                    <button type="submit" name="action" value="save" class="btn btn-sm btn-outline-primary">Speichern</button>
                    <?php if ($u['id'] != 1 && $u['username'] !== 'admin'): ?>
                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger">Löschen</button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    <?php endforeach; endif; ?>

    <!-- Paging -->
    <div class="mt-3">
        <?php if ($total > $limit): ?>
            <?php $totalPages = ceil($total / $limit); ?>
            <nav>
                <ul class="pagination pagination-sm">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">&laquo; Zurück</a></li>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">Weiter &raquo;</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
