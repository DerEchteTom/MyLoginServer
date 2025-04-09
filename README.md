# MyLoginSrv

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
- entpackt die ZIP-Datei (falls vorhanden)
- startet die Container via Docker Compose
- initialisiert die SQLite-Datenbank
- zeigt IP-Adresse & Port

Danach öffne deinen Browser und rufe auf:
```
http://localhost:8080
```

## 📦 Dateistruktur

| Datei                 | Beschreibung |
|----------------------|--------------|
| `login.php`          | Benutzer-Login mit Weiterleitung |
| `register.php`       | Neue Benutzerregistrierung |
| `forgot.php`         | Passwort-vergessen-Funktion mit Mailversand |
| `reset.php`          | Zurücksetzen über Token |
| `admin.php`          | Benutzerverwaltung, Aktiv-Status & Weiterleitungen |
| `init-db.php`        | Erstellt SQLite-Datenbank |
| `audit.log`          | Protokolliert sicherheitsrelevante Aktionen |
| `.env.example`       | SMTP-Konfigurationsbeispiel |
| `docker-compose.yml`| Docker-Definition |
| `nginx.conf`         | Weiterleitung & PHP-Routing |
| `setup.sh`           | Komplett-Setup-Skript |

## 💡 Hinweise

- SMTP-Versand funktioniert über PHPMailer und `.env`
- Standardport ist `8080`
- Die Datenbank liegt als `users.db` im Projektverzeichnis

---
Thomas - bereitgestellt mit ❤️ und ChaGPT
