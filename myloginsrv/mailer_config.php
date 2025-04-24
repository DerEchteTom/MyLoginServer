<?php
// Datei: mailer_config.php – Stand: 2025-04-23 13:30 Europe/Berlin

require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * .env-Datei einlesen
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
 * Mailer vorbereiten
 */
function getMailer(string $to, string $subject): ?PHPMailer {
    $host   = getenv('SMTP_HOST');
    $port   = getenv('SMTP_PORT');
    $from   = getenv('SMTP_FROM');
    $secure = strtolower(trim(getenv('SMTP_SECURE') ?? ''));
    $auth   = strtolower(trim(getenv('SMTP_AUTH') ?? '')) === 'on';
    $trust  = strtolower(trim(getenv('MAIL_TRUST_SELF_SIGNED') ?? '')) === 'on';

    if (!$host || !$port || !$from) {
        file_put_contents("error.log", date('c') . " ❌ SMTP-Parameter unvollständig – Mailversand abgebrochen
", FILE_APPEND);
        return null;
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $host;
    $mail->Port = (int)$port;
    $mail->setFrom($from);
    $mail->addAddress($to);
    $mail->isHTML(false);
    $mail->Subject = $subject;

    if (in_array($secure, ['tls', 'ssl'])) {
        $mail->SMTPSecure = $secure;
    } elseif (!empty($secure)) {
        file_put_contents("error.log", date('c') . " ⚠️ Ungültiger Wert für SMTP_SECURE in .env: $secure
", FILE_APPEND);
    }

    $mail->SMTPAuth = $auth;
    if ($auth) {
        $user = getenv('SMTP_USER');
        $pass = getenv('SMTP_PASS');
        if ($user && $pass) {
            $mail->Username = $user;
            $mail->Password = $pass;
        } else {
            file_put_contents("error.log", date('c') . " ⚠️ SMTP_AUTH aktiviert aber USER/PASS fehlen
", FILE_APPEND);
        }
    }

    if ($trust) {
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

/**
 * Gibt alle geladenen SMTP-Umgebungsvariablen für Debug-Zwecke zurück
 */
function debugSMTPEnv() {
    return [
        'SMTP_HOST' => getenv('SMTP_HOST'),
        'SMTP_PORT' => getenv('SMTP_PORT'),
        'SMTP_FROM' => getenv('SMTP_FROM'),
        'SMTP_SECURE' => getenv('SMTP_SECURE'),
        'SMTP_AUTH' => getenv('SMTP_AUTH'),
        'SMTP_USER' => getenv('SMTP_USER'),
        'SMTP_PASS' => getenv('SMTP_PASS'),
        'ADMIN_EMAIL' => getenv('ADMIN_EMAIL'),
        'MAIL_TRUST_SELF_SIGNED' => getenv('MAIL_TRUST_SELF_SIGNED')
    ];
}
