<?php
// Datei: admin.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Adminbereich</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .nav-tabs .nav-link.active {
            background-color: #f8f9fa;
            border-bottom: 2px solid #0d6efd;
        }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid mt-4">
    <?php include __DIR__ . '/admin_tab_nav.php'; ?>
    
</div>
</body>
</html>
