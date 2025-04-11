<?php
// Datei: link_utils.php

function addDefaultLinks(PDO $db, int $userId): void {
    $jsonPath = __DIR__ . '/default_links.json';
    if (!file_exists($jsonPath)) return;

    $json = file_get_contents($jsonPath);
    $links = json_decode($json, true);

    if (!is_array($links)) return;

    $stmt = $db->prepare("INSERT INTO user_links (user_id, alias, url) VALUES (:uid, :alias, :url)");
    foreach ($links as $link) {
        if (!empty($link['alias']) && !empty($link['url'])) {
            $stmt->execute([
                ':uid' => $userId,
                ':alias' => $link['alias'],
                ':url' => $link['url']
            ]);
        }
    }
}
