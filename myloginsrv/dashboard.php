<?php
// Datei: dashboard.php – CMS-Infoseite mit Weiterleitung – Stand: 2025-05-17 Europe/Berlin
session_start();
date_default_timezone_set('Europe/Berlin');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config_support.php';

$username = $_SESSION['username'] ?? null;
$role     = $_SESSION['role'] ?? 'user';
$isAdmin  = ($role === 'admin');
$target   = $isAdmin ? 'admin.php' : 'links.php';

if (!$username) {
    header("Location: login.php");
    exit;
}

// DB vorbereiten
$db_file = __DIR__ . '/info.db';
$pdo = new PDO("sqlite:$db_file");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Inhalte laden
$raw = $pdo->query("SELECT section_name, text_content, image_path, link_url, link_text FROM page_content")->fetchAll(PDO::FETCH_ASSOC);
$cms = [];
foreach ($raw as $entry) {
    $cms[$entry['section_name']] = $entry;
}

// Verarbeitung (Speichern) nur für Admin
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    foreach ($_POST['content'] as $key => $value) {
        $stmt = $pdo->prepare("UPDATE page_content SET text_content = ? WHERE section_name = ?");
        $stmt->execute([$value, $key]);
    }

    if (!empty($_FILES['images'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp) {
            if ($_FILES['images']['size'][$key] > 0) {
                $folder = 'uploads/';
                $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                $filename = $key . '_' . time() . '.' . $ext;
                $targetPath = $folder . $filename;
                if (move_uploaded_file($tmp, $targetPath)) {
                    $stmt = $pdo->prepare("UPDATE page_content SET image_path = ? WHERE section_name = ?");
                    $stmt->execute([$targetPath, $key]);
                    $cms[$key]['image_path'] = $targetPath;
                }
            }
        }
    }

    header("Location: dashboard.php?saved=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome – Dashboard</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .cms-image { max-width: 100%; height: auto; margin-bottom: 1rem; }
        .cms-section { padding: 1rem; background: #fff; border-radius: 5px; margin-bottom: 1.5rem; }
        .edit-form { background: #f8f9fa; padding: 1rem; border-radius: 5px; margin-top: 1rem; }
        .btn-edit-toggle { margin-bottom: 0.5rem; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4">
    <h4>Welcome, <?= htmlspecialchars($username) ?></h4>
    <p class="text-muted">This is your start page. You will be redirected to <code><?= htmlspecialchars($target) ?></code> in <span id="countdown">5</span> seconds...</p>

    <?php if (!$isAdmin): ?>
        <button class="btn btn-outline-secondary btn-sm" onclick="stopRedirect()">Pause</button>
        <button class="btn btn-outline-primary btn-sm" onclick="startRedirect()">Continue</button>
    <?php endif; ?>

    <div class="cms-section">
        <h5><?= htmlspecialchars($cms['header']['text_content'] ?? 'Welcome') ?></h5>
        <?php if (!empty($cms['header']['image_path'])): ?>
            <img src="<?= htmlspecialchars($cms['header']['image_path']) ?>" class="cms-image" alt="Header image">
        <?php endif; ?>

        <?php if ($isAdmin): ?>
        <form method="post" enctype="multipart/form-data" class="edit-form">
            <label class="form-label">Header text:</label>
            <input type="text" name="content[header]" class="form-control mb-2" value="<?= htmlspecialchars($cms['header']['text_content'] ?? '') ?>">
            <input type="file" name="images[header]" class="form-control mb-2">
            <button type="submit" class="btn btn-outline-success btn-sm">Save header</button>
        </form>
        <?php endif; ?>
    </div>

    <?php for ($i = 1; $i <= 3; $i++): ?>
    <div class="cms-section">
        <?php if (!empty($cms["text$i"]['image_path'])): ?>
            <img src="<?= htmlspecialchars($cms["text$i"]['image_path']) ?>" class="cms-image" alt="Image <?= $i ?>">
        <?php endif; ?>

        <p><?= nl2br(htmlspecialchars($cms["text$i"]['text_content'] ?? '')) ?></p>

        <?php if ($isAdmin): ?>
        <form method="post" enctype="multipart/form-data" class="edit-form">
            <label class="form-label">Text section <?= $i ?>:</label>
            <textarea name="content[text<?= $i ?>]" class="form-control mb-2"><?= htmlspecialchars($cms["text$i"]['text_content'] ?? '') ?></textarea>
            <input type="file" name="images[text<?= $i ?>]" class="form-control mb-2">
            <button type="submit" class="btn btn-outline-success btn-sm">Save section <?= $i ?></button>
        </form>
        <?php endif; ?>
    </div>
    <?php endfor; ?>

    <p class="text-end"><a href="<?= htmlspecialchars($target) ?>" class="btn btn-sm btn-outline-primary">Go to <?= htmlspecialchars($target) ?> &raquo;</a></p>
</div>

<script>
let counter = 5;
let timer = setInterval(updateCountdown, 1000);
let redirectPaused = false;

function updateCountdown() {
    if (redirectPaused) return;
    if (counter <= 1) {
        window.location.href = "<?= htmlspecialchars($target) ?>";
    } else {
        counter--;
        document.getElementById("countdown").textContent = counter;
    }
}

function stopRedirect() {
    redirectPaused = true;
}

function startRedirect() {
    redirectPaused = false;
}
</script>
</body>
</html>
