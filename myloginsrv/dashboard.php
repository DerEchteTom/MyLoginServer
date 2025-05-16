<?php
// File: dashboard.php – Unified version with CMS preview + redirect – Stand: 2025-05-16 Europe/Berlin
session_start();
date_default_timezone_set('Europe/Berlin');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? 'user';

if (!$username) {
    header("Location: login.php");
    exit;
}

$isAdmin = ($role === 'admin');
$target = $isAdmin ? 'admin.php' : 'links.php';

// Database setup (info.db)
$dbFile = __DIR__ . '/info.db';
try {
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Table check
    $pdo->exec("CREATE TABLE IF NOT EXISTS page_content (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        section_name TEXT NOT NULL UNIQUE,
        text_content TEXT,
        image_path TEXT,
        link_url TEXT,
        link_text TEXT
    )");

    // Insert defaults if empty
    $count = $pdo->query("SELECT COUNT(*) FROM page_content")->fetchColumn();
    if ((int)$count === 0) {
        $sections = ['header', 'text1', 'text2', 'text3', 'link1_text', 'link1_url', 'link2_text', 'link2_url'];
        foreach ($sections as $s) {
            $pdo->prepare("INSERT INTO page_content (section_name) VALUES (?)")->execute([$s]);
        }
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Save form
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['content'])) {
            foreach ($_POST['content'] as $section => $text) {
                $stmt = $pdo->prepare("UPDATE page_content SET text_content = ? WHERE section_name = ?");
                $stmt->execute([$text, $section]);
            }
        }

        if (!empty($_FILES['images'])) {
            foreach ($_FILES['images']['tmp_name'] as $section => $tmp) {
                if ($_FILES['images']['size'][$section] > 0) {
                    $dir = "uploads/";
                    if (!is_dir($dir)) mkdir($dir);
                    $ext = pathinfo($_FILES['images']['name'][$section], PATHINFO_EXTENSION);
                    $file = $dir . $section . "_" . time() . "." . $ext;
                    if (move_uploaded_file($tmp, $file)) {
                        $stmt = $pdo->prepare("UPDATE page_content SET image_path = ? WHERE section_name = ?");
                        $stmt->execute([$file, $section]);
                    }
                }
            }
        }

        $message = "Changes saved.";
    } catch (Exception $ex) {
        $error = "Error: " . $ex->getMessage();
    }
}

// Load data
$sections = $pdo->query("SELECT section_name, text_content, image_path, link_url, link_text FROM page_content")->fetchAll(PDO::FETCH_ASSOC);
$content = [];
foreach ($sections as $row) {
    $content[$row['section_name']] = [
        'text' => $row['text_content'],
        'image' => $row['image_path'],
        'url' => $row['link_url'] ?? '',
        'label' => $row['link_text'] ?? ''
    ];
}
?>
<?php
// File: dashboard.php – Unified version with CMS preview + redirect – Stand: 2025-05-16 Europe/Berlin
session_start();
date_default_timezone_set('Europe/Berlin');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? 'user';

if (!$username) {
    header("Location: login.php");
    exit;
}

$isAdmin = ($role === 'admin');
$target = $isAdmin ? 'admin.php' : 'links.php';

// Database setup (info.db)
$dbFile = __DIR__ . '/info.db';
try {
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Table check
    $pdo->exec("CREATE TABLE IF NOT EXISTS page_content (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        section_name TEXT NOT NULL UNIQUE,
        text_content TEXT,
        image_path TEXT,
        link_url TEXT,
        link_text TEXT
    )");

    // Insert defaults if empty
    $count = $pdo->query("SELECT COUNT(*) FROM page_content")->fetchColumn();
    if ((int)$count === 0) {
        $sections = ['header', 'text1', 'text2', 'text3', 'link1_text', 'link1_url', 'link2_text', 'link2_url'];
        foreach ($sections as $s) {
            $pdo->prepare("INSERT INTO page_content (section_name) VALUES (?)")->execute([$s]);
        }
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Save form
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['content'])) {
            foreach ($_POST['content'] as $section => $text) {
                $stmt = $pdo->prepare("UPDATE page_content SET text_content = ? WHERE section_name = ?");
                $stmt->execute([$text, $section]);
            }
        }

        if (!empty($_FILES['images'])) {
            foreach ($_FILES['images']['tmp_name'] as $section => $tmp) {
                if ($_FILES['images']['size'][$section] > 0) {
                    $dir = "uploads/";
                    if (!is_dir($dir)) mkdir($dir);
                    $ext = pathinfo($_FILES['images']['name'][$section], PATHINFO_EXTENSION);
                    $file = $dir . $section . "_" . time() . "." . $ext;
                    if (move_uploaded_file($tmp, $file)) {
                        $stmt = $pdo->prepare("UPDATE page_content SET image_path = ? WHERE section_name = ?");
                        $stmt->execute([$file, $section]);
                    }
                }
            }
        }

        $message = "Changes saved.";
    } catch (Exception $ex) {
        $error = "Error: " . $ex->getMessage();
    }
}

// Load data
$sections = $pdo->query("SELECT section_name, text_content, image_path, link_url, link_text FROM page_content")->fetchAll(PDO::FETCH_ASSOC);
$content = [];
foreach ($sections as $row) {
    $content[$row['section_name']] = [
        'text' => $row['text_content'],
        'image' => $row['image_path'],
        'url' => $row['link_url'] ?? '',
        'label' => $row['link_text'] ?? ''
    ];
}
?>
<?php
// File: dashboard.php – Unified version with CMS preview + redirect – Stand: 2025-05-16 Europe/Berlin
session_start();
date_default_timezone_set('Europe/Berlin');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? 'user';

if (!$username) {
    header("Location: login.php");
    exit;
}

$isAdmin = ($role === 'admin');
$target = $isAdmin ? 'admin.php' : 'links.php';

// Database setup (info.db)
$dbFile = __DIR__ . '/info.db';
try {
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Table check
    $pdo->exec("CREATE TABLE IF NOT EXISTS page_content (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        section_name TEXT NOT NULL UNIQUE,
        text_content TEXT,
        image_path TEXT,
        link_url TEXT,
        link_text TEXT
    )");

    // Insert defaults if empty
    $count = $pdo->query("SELECT COUNT(*) FROM page_content")->fetchColumn();
    if ((int)$count === 0) {
        $sections = ['header', 'text1', 'text2', 'text3', 'link1_text', 'link1_url', 'link2_text', 'link2_url'];
        foreach ($sections as $s) {
            $pdo->prepare("INSERT INTO page_content (section_name) VALUES (?)")->execute([$s]);
        }
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Save form
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['content'])) {
            foreach ($_POST['content'] as $section => $text) {
                $stmt = $pdo->prepare("UPDATE page_content SET text_content = ? WHERE section_name = ?");
                $stmt->execute([$text, $section]);
            }
        }

        if (!empty($_FILES['images'])) {
            foreach ($_FILES['images']['tmp_name'] as $section => $tmp) {
                if ($_FILES['images']['size'][$section] > 0) {
                    $dir = "uploads/";
                    if (!is_dir($dir)) mkdir($dir);
                    $ext = pathinfo($_FILES['images']['name'][$section], PATHINFO_EXTENSION);
                    $file = $dir . $section . "_" . time() . "." . $ext;
                    if (move_uploaded_file($tmp, $file)) {
                        $stmt = $pdo->prepare("UPDATE page_content SET image_path = ? WHERE section_name = ?");
                        $stmt->execute([$file, $section]);
                    }
                }
            }
        }

        $message = "Changes saved.";
    } catch (Exception $ex) {
        $error = "Error: " . $ex->getMessage();
    }
}

// Load data
$sections = $pdo->query("SELECT section_name, text_content, image_path, link_url, link_text FROM page_content")->fetchAll(PDO::FETCH_ASSOC);
$content = [];
foreach ($sections as $row) {
    $content[$row['section_name']] = [
        'text' => $row['text_content'],
        'image' => $row['image_path'],
        'url' => $row['link_url'] ?? '',
        'label' => $row['link_text'] ?? ''
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard / Start Page</title>
    <meta http-equiv="refresh" content="6; URL=<?= htmlspecialchars($target) ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .cms-block { margin: 2rem 0; }
        .cms-block img { max-width: 100%; height: auto; margin-top: 0.5rem; }
        .admin-edit { background: #f0f0f0; padding: 1rem; margin-top: 1rem; border-left: 4px solid #007bff; }
        .edit-form { display: none; margin-top: 10px; }
        textarea, input[type="text"] { width: 100%; }
        .success { color: green; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4">
    <h4>Welcome, <?= htmlspecialchars($username) ?></h4>
    <p class="text-muted">You will be redirected to <code><?= htmlspecialchars($target) ?></code> in a few seconds ...</p>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="alert alert-info small mt-2">
        <strong>Role:</strong> <?= htmlspecialchars($role) ?><br>
        <strong>Time:</strong> <?= date('Y-m-d H:i:s') ?><br>
        <strong>Debug:</strong> This dashboard page can be used for displaying content blocks and admin editable notes.
    </div>

    <p><a href="<?= htmlspecialchars($target) ?>" class="btn btn-outline-primary btn-sm">Click here if you are not redirected &raquo;</a></p>

    <hr>
    <!-- CMS Output Blocks -->
    <div class="cms-block">
        <h2><?= htmlspecialchars($content['header']['text'] ?? 'Welcome!') ?></h2>
        <?php if ($isAdmin): ?>
            <div class="admin-edit">
                <strong>Edit header:</strong>
                <form method="post">
                    <input type="text" name="content[header]" value="<?= htmlspecialchars($content['header']['text'] ?? '') ?>">
                    <button class="btn btn-sm btn-outline-success mt-1" type="submit">Save</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <?php for ($i = 1; $i <= 3; $i++): ?>
        <div class="cms-block">
            <h5>Section <?= $i ?></h5>
            <?php if (!empty($content["text$i"]['image'])): ?>
                <img src="<?= htmlspecialchars($content["text$i"]['image']) ?>" alt="Image <?= $i ?>">
            <?php endif; ?>
            <p><?= nl2br(htmlspecialchars($content["text$i"]['text'] ?? '')) ?></p>
            <?php if (!empty($content["link{$i}_url"]['url'])): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($content["link{$i}_url"]['url']) ?>">
                    <?= htmlspecialchars($content["link{$i}_url"]['label'] ?? 'More') ?>
                </a>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
                <div class="admin-edit">
                    <strong>Edit section <?= $i ?>:</strong>
                    <form method="post" enctype="multipart/form-data">
                        <textarea name="content[text<?= $i ?>]"><?= htmlspecialchars($content["text$i"]['text'] ?? '') ?></textarea>
                        <label class="form-label mt-2">Upload image:
                            <input type="file" name="images[text<?= $i ?>]" class="form-control form-control-sm">
                        </label>
                        <button class="btn btn-sm btn-outline-success mt-1" type="submit">Save</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endfor; ?>
</div>
</body>
</html>
