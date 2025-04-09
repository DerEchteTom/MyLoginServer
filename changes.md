# 📋 Änderungsprotokoll – MyLoginSrv

## Version 1.0 – Initial Release

### ✅ Hauptfunktionen
- Benutzerregistrierung mit Passwort-Hashing
- Login mit Session-Handling und Redirect-Ziel
- Passwort vergessen → Token-E-Mail-Versand via PHPMailer
- Passwort zurücksetzen mit Tokenprüfung
- Adminbereich mit Benutzerliste, Aktiv/Deaktiv-Schalter und Weiterleitungs-URLs
- Benutzer können mehrere Redirect-URLs erhalten (Textarea-Interface)

### 🔐 Sicherheit & Protokollierung
- `audit.log` mit IP-Adresse, Zeitstempel und Aktion (Login, Logout, Änderungen)
- SQLite-Datenbank: `users.db` mit Feldern für Passwort, Token, Weiterleitung, aktiv
- Eingabefelder serverseitig validiert

### 📦 Systemaufbau
- Docker-basiertes Setup (nginx + php-fpm)
- `setup.sh`: automatisierter Installer mit IP-Ausgabe
- `.env.example`: SMTP-Konfiguration per Umgebungsvariable
- Volle Integration von PHPMailer vorbereitet
- Minimaler Bootstrap-Stil für responsive UI

### 🛠 Entwickler-Tools
- `init-db.php` erstellt Datenbank inkl. Struktur und Constraints
- `README.md` mit Setup-Anleitung und Architekturübersicht
- `docker-compose.yml` + `nginx.conf` optimiert für lokale Nutzung (Port 8080)
