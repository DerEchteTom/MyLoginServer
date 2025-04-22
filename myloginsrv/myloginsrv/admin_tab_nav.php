<?php
// Datei: admin_tab_nav.php – Stand: 2025-04-22 11:32 Europe/Berlin

if (!isset($db)) {
    $db = new PDO('sqlite:users.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

$count = $db->query("SELECT COUNT(*) FROM link_requests")->fetchColumn();
?>
<nav class="mb-4">
    <div class="btn-group d-flex flex-wrap" role="group">
        <a href="admin_tab_users.php" class="btn btn-outline-secondary btn-sm">Benutzerverwaltung</a>
        <a href="admin_tab_links.php" class="btn btn-outline-secondary btn-sm">Benutzer-Links</a>
        <a href="admin_tab_linkrequests.php" class="btn btn-outline-secondary btn-sm">
    Linkanfragen
    <?php if ($count > 0): ?>
        <span class="badge bg-dark"><?= $count ?></span>
    <?php else: ?>
        <span class="badge bg-light text-dark border">0</span>
    <?php endif; ?>
</a>
        <a href="admin_tab_logs.php" class="btn btn-outline-secondary btn-sm">Logdateien</a>
        <a href="admin_tab_mailtest.php" class="btn btn-outline-secondary btn-sm">Mail-Konfiguration</a>
        <a href="admin_tab_status.php" class="btn btn-outline-secondary btn-sm">Systemstatus</a>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </div>
</nav>
