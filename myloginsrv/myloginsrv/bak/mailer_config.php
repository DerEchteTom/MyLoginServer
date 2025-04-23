<?php
// Datei: mailer_config.php

require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

/**
 * .env-Datei manuell einlesen (ohne Dotenv)
 */
function parseEnvFile($file) {
    if (!file_exists($file)) return;
    foreach (file($file) as $line) {
        if (strpos(trim($line), '=') !== false) {
            list($key, $val) = explode('=', trim($line), 2);
            putenv(trim($key) . '=' . trim($val));
        }
    }
}
parseEnvFile(__DIR__ . '/.env');

/**
 * Debug-Infos aus der .env anzeigen
 */
function debugSMTPEnv() {
    return [
        'SMTP_HOST'               => getenv('SMTP_HOST'),
        'SMTP_PORT'               => getenv('SMTP_PORT'),
        'SMTP_FROM'               => getenv('SMTP_FROM'),
        'SMTP_SECURE'             => getenv('SMTP_SECURE'),
        'SMTP_AUTH'               => getenv('SMTP_AUTH'),
        'SMTP_USER'               => getenv('SMTP_USER'),
        'SMTP_PASS'               => getenv('SMTP_PASS'),
        'ADMIN_EMAIL'             => getenv('ADMIN_EMAIL'),
        'MAIL_TRUST_SELF_SIGNED'  => getenv('MAIL_TRUST_SELF_SIGNED')
    ];
}

/**
 * Liefert konfiguriertes PHPMailer-Objekt
 */
function getMailer(string $to, string $subject): ?PHPMailer {
    $host   = getenv('SMTP_HOST');
    $port   = getenv('SMTP_PORT');
    $from   = getenv('SMTP_FROM');
    $auth   = strtolower(trim(getenv('SMTP_AUTH') ?? '')) === 'on';
    $secureRaw = strtolower(trim(getenv('SMTP_SECURE') ?? ''));
    $secure = in_array($secureRaw, ['ssl', 'tls']) ? $secureRaw : '';
    $trustSelfSigned = strtolower(getenv('MAIL_TRUST_SELF_SIGNED') ?? '') === 'on';

    // Validierung
    if (!in_array($secureRaw, ['ssl', 'tls', 'none', '']) && $secureRaw !== '') {
        file_put_contents("error.log", date('c') . " ⚠️ Ungültiger SMTP_SECURE-Wert in .env: '$secureRaw'. Fallback: keine Verschlüsselung\n", FILE_APPEND);
    }

    if (!$host || !$port || !$from) {
        file_put_contents("error.log", date('c') . " ❌ SMTP unvollständig – Host=$host, Port=$port, From=$from\n", FILE_APPEND);
        return null;
    }

    $mail = new PHPMailer(true);
    // Debugging aktivieren
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function($str, $level) {
        file_put_contents("error.log", date('c') . " [SMTPDebug] $str\n", FILE_APPEND);
    };

    $mail->isSMTP();
    $mail->Host = $host;
    $mail->Port = $port;
    $mail->setFrom($from);
    $mail->addAddress($to);
    $mail->isHTML(false);
    $mail->Subject = $subject;

    if ($secure) {
        $mail->SMTPSecure = $secure;
    }

    $mail->SMTPAuth = $auth;

    if ($auth) {
        $user = getenv('SMTP_USER');
        $pass = getenv('SMTP_PASS');
        if ($user && $pass) {
            $mail->Username = $user;
            $mail->Password = $pass;
        }
    }

    if ($trustSelfSigned) {
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ];
    }

    return $mail;
}
