<?php
// dashboard.php – Darstellung aller gefüllten CMS-Sektionen
session_start();
date_default_timezone_set('Europe/Berlin');
require_once 'config.php';
require_once 'auth.php';

$isAdmin = isAuthenticated() && hasRole("admin");
$username = $_SESSION['username'] ?? 'guest';
$role = $_SESSION['role'] ?? 'user';
$target = ($role === 'admin') ? 'admin.php' : 'links.php';

$pdo = new PDO('sqlite:cms.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Timer laden (default: 5s)
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_name = 'redirect_timer'");
$stmt->execute();
$delay = (int) ($stmt->fetchColumn() ?? 5);

// Nur Sektionen mit Inhalt laden
$stmt = $pdo->query("SELECT section_name, text_content FROM page_content WHERE text_content IS NOT NULL AND TRIM(text_content) != '' ORDER BY section_name ASC");
$sections = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sections[$row['section_name']] = $row['text_content'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link href="./assets/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .ql-editor img { max-width: 100%; height: auto; }
    .ql-editor { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 0 8px rgba(0,0,0,0.05); }
  </style>
</head>
<body class="bg-light">
<div class="container mt-4" style="max-width: 90%;">

  <h4 class="mb-3">Welcome, <?= htmlspecialchars($username) ?> <?= $isAdmin ? '(Admin)' : '' ?></h4>
  <p class="text-muted">You will be redirected in <span id="timer"><?= $delay ?></span> seconds.</p>
  <div class="d-flex gap-2 mb-4">
    <button onclick="pauseRedirect()" class="btn btn-sm btn-outline-warning">Pause Redirect</button>
    <a href="<?= htmlspecialchars($target) ?>" class="btn btn-sm btn-outline-primary" id="go-now-btn">Go Now</a>
    <?php if ($isAdmin): ?>
      <a href="cms_edit.php" class="btn btn-sm btn-outline-success">Edit CMS Content</a>
    <?php endif; ?>
  </div>

  <!-- CMS-Inhalte -->
  <?php if (empty($sections)): ?>
    <p class="text-muted">No content available.</p>
  <?php else: ?>
    <?php foreach ($sections as $section => $html): ?>
      <h5 class="text-muted"><?= htmlspecialchars(ucfirst($section)) ?></h5>
      <div class="ql-editor">
        <?= $html ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

<script>
let timerElement = document.getElementById("timer");
let redirectTimeout;
let remainingTime = <?= $delay ?>;

function startRedirect() {
  const interval = setInterval(() => {
    if (remainingTime > 0) {
      remainingTime--;
      timerElement.textContent = remainingTime;
    }
  }, 1000);

  redirectTimeout = setTimeout(() => {
    window.location.href = <?= json_encode($target) ?>;
  }, remainingTime * 1000);
}

function pauseRedirect() {
  clearTimeout(redirectTimeout);
  document.getElementById("go-now-btn").style.display = "inline-block";
  document.getElementById("timer").textContent = '⏸️';
}

window.onload = startRedirect;
</script>
</body>
</html>
