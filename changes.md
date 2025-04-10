# Changelog – MyLoginServer

## v1.4.0 – 2024-04-10
- Wechsel auf neue Projektbasis (Reset des Chat-Kontexts)
- Nutzung der aktuellen GitHub-Version als verbindliche Quelle
- Unterstützung für `.env`-Konfiguration in `admin_mailtest.php` hinzugefügt
- Verbesserte Setup-Logik für SMTP-Umgebungsvariablen
- Korrektur von Verweisen auf doppelte Registrieren-Links
- Link zur Registrierung auf `login.php` hinzugefügt (nicht in `admin_login.php`)
- Sicherheitsprüfung und Role-Checks in allen Admin-Views vereinheitlicht
- Erweiterung der `reset.php` mit Tokenprüfung und Logging

---

## Frühere Versionen

### v1.3.0
- Admin-Dashboard mit Benutzerverwaltung, Mail-Test, Loganzeige
- Audit-Log-Datei `audit.log` eingeführt
- PHPMailer Integration mit SMTP-Konfiguration

### v1.2.0
- Passwort zurücksetzen per Token
- Registrierung und Validierung
- Dark Mode optional über CSS

### v1.0.0
- Basisfunktionen: Login, Logout, Session-Handling
