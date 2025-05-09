# MyLoginServer

Ein moderner, erweiterbarer Login-Server mit Admin-Dashboard, Benutzerverwaltung, AD-Anbindung und verschlüsseltem SMTP-Mailversand. Das System basiert auf einer schlanken PHP-/SQLite-Architektur und ist containerfähig (Docker).

---

## 🚀 Features

- 🔐 Kombinierter Login:
  - Lokale Benutzer mit Passwort
  - Active Directory (AD)-Login via LDAP
  - Inaktive AD-Benutzer werden automatisch lokal erfasst
- 👤 Benutzerverwaltung im Adminbereich:
  - Aktivierung, Rollenvergabe, Passwort-Reset
  - Willkommensmail mit Login-Link
  - Benutzer-Import via JSON
- 🔗 Benutzerbezogene Linkverwaltung:
  - Standard-Links pro Benutzer
  - Benutzer können Linkvorschläge einreichen
-✉️ SMTP-Versand mit PHPMailer
  - Konfiguration via `.env`
  - Verschlüsselung sensibler Variablen
  - Testmails direkt im Adminbereich
- 🛠️ Debugging & Status:
  - Debug-Ausgabe im Login optional sichtbar
  - Debug-Fenster & Logfile-Erstellung
  - Admin-Statusseite zeigt Systemdateien, PHP-Version, Verschlüsselungsstatus, PHPMailer-Zustand
- 📦 Einfache Struktur & Bootstrap-Design
- 🐳 Docker-fähig mit Installer `setup.sh`

---

## 🐳 Schnellstart (Docker)

```bash
chmod +x setup.sh
./setup.sh
```

Falls nötig:

```bash
sudo apt install dos2unix
dos2unix setup.sh
```

Das Skript:
- prüft Docker-Installation
- startet die Container
- initialisiert die SQLite-Datenbank
- legt `.env`, `audit.log`, `error.log` an
- prüft PHPMailer & Composer
- zeigt lokale IP zur Anmeldung

---

## ✉️ SMTP-Konfiguration (.env)

Beispiel:

```env
SMTP_HOST=smtp.example.com
SMTP_PORT=25
SMTP_FROM=noreply@example.com
SMTP_SECURE=none
SMTP_AUTH=off
ADMIN_EMAIL=admin@example.com
```

> Die Konfiguration kann im Adminbereich (Mail-Test) angezeigt, geändert und verschlüsselt werden. Eine Vorschaltdatei `.env.example` ist enthalten.

---

## 🧪 Test & Login

```bash
http://<deine-IP>:8080
```

Erstanmeldung (nach Setup):
- Benutzer: `admin`
- Passwort: `adminpass` (oder wie in `setup.sh` gesetzt)

---

## 🔐 Sicherheit

- Passwort-Hashing mit bcrypt
- Audit-Logging für Registrierung, Mailversand, Benutzeraktionen
- Nur Admins dürfen admin-Dateien aufrufen (`requireRole('admin')`)
- Ausnahme: `admin_tab_status.php` (frei zugänglich zur Diagnose)

---

## 🔄 Konfigurierbare Module

- `.envad`: für AD-spezifische Einstellungen (verschlüsselbar)
- `default_links.json`: Standard-Links bei Benutzeranlage
- `config_support.php`: zentrale Mail- und Kryptofunktionen

---

## ℹ️ Hinweise

- PHPMailer wird automatisch erkannt und getestet
- Verschlüsselung basiert auf XOR, Key kann gesetzt oder automatisch verwendet werden
- Adminmenü enthält Tabs für: Benutzer, Links, Linkvorschläge, Mail, Logs, Status
- Debug-Ausgaben optional per Toggle oder URL `?debug=1`

---

## 📄 Changelog

Siehe [`changes.md`](./changes.md)
