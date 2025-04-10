# 📋 Änderungsprotokoll – MyLoginSrv

## Version 1.0.1 – Rechtefix & Git-Integration

### ✅ Verbesserungen
- `setup.sh`: Setzt automatisch Rechte für `users.db` und `audit.log`
- `.gitattributes`: Erzwingt `LF`-Zeilenenden für Linux-Kompatibilität
- `.editorconfig`: Konfiguriert Editorverhalten (UTF-8, Einrückung, EOL)
- Hinweise für Git-Push-Probleme & Workflows ergänzt (Pull vs. Force Push)
- Admin-Standardbenutzer (`admin` / `adminpass`) wird bei leerer Datenbank automatisch erstellt

---

## Version 1.0.0 – Initial Release

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
- `setup.sh`: automatisierter Installer mit IP-Ausgabe, Rechteprüfung und Containerinitialisierung
- `.env.example`: SMTP-Konfiguration per Umgebungsvariable
- Volle Integration von PHPMailer vorbereitet
- Minimaler Bootstrap-Stil für responsive UI
