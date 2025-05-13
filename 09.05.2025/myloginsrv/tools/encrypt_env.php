<?php
// Datei: encrypt_env.php – Stand: 2025-04-24 10:41:05 Europe/Berlin

function encryptEnvValue($plain, $key) {
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

if ($argc !== 3) {
    echo "Verwendung: php encrypt_env.php <klartext> <schluessel>\n";
    exit(1);
}

$klartext = $argv[1];
$schluessel = $argv[2];
$enc = encryptEnvValue($klartext, $schluessel);
echo "Verschluesselt (BASE64):\n" . $enc . "\n";
?>
