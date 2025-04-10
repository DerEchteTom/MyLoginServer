# MyLoginSrv

![Contributors](https://img.shields.io/badge/contributors-2-brightgreen)

Ein dockerisiertes, leichtgewichtiges Login-System mit:
- Benutzerregistrierung, Login & Passwort-Reset
- Adminbereich mit Benutzerverwaltung und Weiterleitungs-URLs
- SQLite-Datenbank (lokal)
- PHPMailer-Anbindung
- Audit-Log mit IP-Nachverfolgung
- Komplett konfigurierbar per `.env`

## 🔧 Setup-Anleitung

### Voraussetzungen
- Docker & Docker Compose installiert

### Installation

```bash
chmod +x setup.sh
./setup.sh
```

Das Skript:
- startet die Container via Docker Compose
- initialisiert die SQLite-Datenbank (legt Admin-Nutzer an)
- korrigiert Dateiberechtigungen (`users.db`, `audit.log`)
- zeigt IP-Adresse & Port für den Zugriff

Danach öffne deinen Browser und rufe auf:
```
http://localhost:8080
```

### 🧑‍💼 Standard-Admin-Zugang
- Benutzername: `admin`
- Passwort: `adminpass`

Der Benutzer wird automatisch bei der ersten Initialisierung angelegt.

## 📦 Dateistruktur

| Datei                 | Beschreibung |
|----------------------|--------------|
| `login.php`          | Benutzer-Login mit Weiterleitung |
| `register.php`       | Neue Benutzerregistrierung |
| `forgot.php`         | Passwort-vergessen-Funktion mit Mailversand |
| `reset.php`          | Zurücksetzen über Token |
| `admin.php`          | Benutzerverwaltung, Aktiv-Status & Weiterleitungen |
| `init-db.php`        | Erstellt SQLite-Datenbank & Admin-Benutzer |
| `audit.log`          | Protokolliert sicherheitsrelevante Aktionen |
| `.env.example`       | SMTP-Konfigurationsbeispiel |
| `docker-compose.yml`| Docker-Definition |
| `nginx.conf`         | Weiterleitung & PHP-Routing |
| `setup.sh`           | Komplett-Setup-Skript mit IP-Anzeige |

## 💡 Hinweise

- SMTP-Versand funktioniert über PHPMailer und `.env`
- Standardport ist `8080`
- Die Datenbank liegt als `users.db` im Projektverzeichnis

## 👥 Mitwirkende

Siehe [CONTRIBUTORS.md](CONTRIBUTORS.md)

---
© Technoteam / Thomas Schmidt — gemeinsam mit ChatGPT bereitgestellt mit ❤️