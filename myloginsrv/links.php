<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$db = new PDO('sqlite:users.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $_SESSION['user']]);
$userId = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT alias, url FROM user_links WHERE user_id = :id ORDER BY alias ASC");
$stmt->execute([':id' => $userId]);
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Deine Startseite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 600px;">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">Willkommen, <?= htmlspecialchars($_SESSION['user']) ?></h4>
            <?php if (count($links) > 0): ?>
                <p>Hier sind deine verfügbaren Links:</p>
                <ul class="list-group">
                    <?php foreach ($links as $link): ?>
                        <li class="list-group-item">
                            <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank">
                                <?= htmlspecialchars($link['alias']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Es wurden dir noch keine Links zugewiesen.</p>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-secondary mt-4">Logout</a>
        </div>
    </div>
</div>
</body>
</html>