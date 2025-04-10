# MyLoginServer

Ein moderner, containerbasierter Login-Server zur Authentifizierung von Benutzern mit Admin-Dashboard, Benutzerverwaltung und Unterstützung für SMTP-Mail-Versand.

---

## 🚀 Features

- Benutzer-Login mit Session-Handling
- Registrierung mit Passwort-Hashing
- Passwort-Vergessen und Zurücksetzen (via Token + E-Mail)
- Admin-Dashboard:
  - Benutzer aktivieren/deaktivieren
  - Benutzer anlegen
  - Mail-Test via PHPMailer
  - Anzeige des `audit.log`
- `.env`-basierte Konfiguration für SMTP
- Audit-Logging (Anmeldung, Reset, Aktionen)
- Docker-Container für Webserver, PHP und Datenhaltung
- Flache Verzeichnisstruktur – alles unter `myloginsrv/`

---

## 🐳 Schnellstart (Docker)

```bash
chmod +x setup.sh
./setup.sh
```

Das Script:

- prüft Docker und Containerstatus
- startet die Container mit `docker-compose up`
- initialisiert die Datenbank (`users.db`)
- vergibt nötige Rechte für Log und DB
- zeigt die lokale IP für Webzugriff

---

## 📁 Verzeichnisse & Dateien

- `setup.sh` – Installer und Rechte-Check
- `users.db` – SQLite-Datenbank mit Audit- und Reset-Feldern
- `audit.log` – alle sicherheitsrelevanten Aktionen
- `*.php` – Frontend/Backend-Funktionen:
  - `login.php`, `register.php`, `logout.php`
  - `forgot.php`, `reset.php`
  - `admin.php`, `admin_users.php`, `admin_mailtest.php`, `admin_logs.php`

---

## ✉️ SMTP konfigurieren

Die Datei `.env` steuert den Mail-Versand über PHPMailer:

```env
SMTP_HOST=smtp.example.com
SMTP_PORT=25
SMTP_FROM=admin@example.com
```

Diese Werte können auch im Admin-Center unter „Mail-Test“ angepasst und gespeichert werden.

---

## 🧪 Test

Besuche nach dem Setup:

```
http://<deine-IP>:8080
```

Login (Standard-Admin):
- Benutzername: `admin`
- Passwort: `adminpass`

---

## ℹ️ Weitere Hinweise

- PHPMailer wird automatisch installiert, wenn nicht vorhanden
- `.env` wird beim Mail-Test automatisch geladen und aktualisiert
- Setup funktioniert sowohl auf Linux-Host als auch unter Docker Desktop (Windows)

---

## 📄 Changelog

Siehe [`changes.md`](./changes.md)