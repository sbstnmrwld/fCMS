# Installation & Erste Schritte

Diese Anleitung hilft Ihnen, fCMS auf Ihrem Webspace zu installieren und die ersten Seiten zu erstellen.

## Systemanforderungen

Ihr Webspace muss folgende Anforderungen erfüllen:

- **PHP**: Version 7.4 oder höher (PHP 8.0+ empfohlen)
- **Webserver**: Apache (mit mod_rewrite) oder Nginx
- **Speicherplatz**: Mindestens 50 MB
- **Schreibrechte**: Für Verzeichnisse `content/`, `logs/`

**Keine Datenbank erforderlich!**

## Installation

### Schritt 1: Download

Laden Sie die neueste Version von fCMS herunter:
- `fCMS-v[version].zip` (Produktionsversion)

Oder klonen Sie das Repository für Entwicklung:
```bash
git clone https://github.com/IhrRepo/fCMS.git
```

### Schritt 2: Entpacken

Entpacken Sie die ZIP-Datei auf Ihrem Computer. Sie sollten folgende Struktur sehen:

```
fCMS/
├── admin/
├── blocks/
├── config/
├── content/
├── languages/
├── logs/
├── public/
├── src/
└── themes/
```

### Schritt 3: Konfiguration anpassen

1. **Kopieren Sie die Beispiel-Konfiguration** (falls nicht vorhanden):
   ```bash
   cp config/config.example.php config/config.php
   ```

2. **Öffnen Sie** `config/config.php` in einem Text-Editor

3. **Passen Sie folgende Einstellungen an**:

   ```php
   'site' => [
       'name' => 'Ihr Vereinsname',        // Name Ihrer Website
       'url' => 'https://ihre-domain.de',  // Ihre URL (ohne Trailing Slash)
   ],
   ```

4. **WICHTIG - Admin-Passwort ändern**:

   ```php
   'admin' => [
       'username' => 'admin',
       'password' => '$2y$10$...',  // Hier Ihren Hash einfügen
   ],
   ```

### Schritt 4: Passwort-Hash generieren

Sie haben zwei Möglichkeiten:

**Option A: Nach dem Upload (empfohlen)**

1. Laden Sie alle Dateien hoch (siehe Schritt 5)
2. Rufen Sie auf: `https://ihre-domain.de/generate-password.php`
3. Geben Sie Ihr gewünschtes Passwort ein
4. Kopieren Sie den generierten Hash in `config/config.php`
5. **WICHTIG**: Löschen Sie `generate-password.php` sofort nach Verwendung!

**Option B: Lokal generieren**

```bash
php -r "echo password_hash('IhrPasswort', PASSWORD_DEFAULT);"
```

### Schritt 5: Hochladen

Laden Sie **alle Dateien** per FTP/SFTP auf Ihren Webserver:

- **Zielverzeichnis**: `public_html/` oder `httpdocs/` (je nach Provider)
- **Alle Dateien**: Einschließlich versteckter Dateien wie `.htaccess`
- **Verzeichnisstruktur beibehalten**: Ordnerstruktur muss erhalten bleiben

**FTP-Client-Empfehlungen**:
- FileZilla (kostenlos)
- Cyberduck (kostenlos)
- WinSCP (Windows, kostenlos)

### Schritt 6: Berechtigungen setzen

Stellen Sie sicher, dass folgende Verzeichnisse beschreibbar sind:

```bash
chmod 755 content/
chmod 755 content/pages/
chmod 755 content/media/
chmod 755 logs/
```

Bei den meisten Providern sind diese Rechte automatisch korrekt gesetzt.

### Schritt 7: Apache mod_rewrite prüfen

**Bei Apache**: Die `.htaccess` Datei in `public/` sollte automatisch funktionieren.

Falls nicht, kontaktieren Sie Ihren Provider und fragen Sie nach mod_rewrite.

**Bei Nginx**: Siehe [Nginx-Konfiguration](#nginx-konfiguration)

### Schritt 8: Testen

1. **Frontend aufrufen**: `https://ihre-domain.de`
   - Sie sollten die Beispiel-Homepage sehen

2. **Admin-Bereich aufrufen**: `https://ihre-domain.de/admin`
   - Melden Sie sich mit Ihrem Benutzernamen und Passwort an

3. **generate-password.php löschen**: Falls verwendet!

## Erste Schritte nach der Installation

### 1. Erste Seite erstellen

1. Melden Sie sich im Admin-Bereich an
2. Klicken Sie auf **"Seiten"** im Menü
3. Klicken Sie auf **"Neue Seite erstellen"**
4. Geben Sie einen Titel ein, z.B. "Über uns"
5. Fügen Sie Inhalte mit dem Block-Editor hinzu:
   - Klicken Sie auf **"Block hinzufügen"**
   - Wählen Sie einen Block-Typ (z.B. "Absatz")
   - Geben Sie Ihren Text ein
6. Klicken Sie auf **"Speichern"**

### 2. Navigation einrichten

1. Gehen Sie zu **"Navigation"** im Menü
2. Wählen Sie, welche Seiten im Hauptmenü erscheinen sollen
3. Aktivieren Sie **"In Hauptnavigation anzeigen"**
4. Legen Sie die Reihenfolge fest (Sortierung nach Position)
5. Speichern Sie die Änderungen

### 3. Website-Einstellungen anpassen

1. Gehen Sie zu **"Einstellungen"**
2. Passen Sie folgende Einstellungen an:
   - Website-Name
   - Website-Beschreibung
   - Sprache
   - Zeitzone
3. Speichern Sie die Änderungen

### 4. Design anpassen

1. Gehen Sie zu **"Themes"** (falls verfügbar)
2. Wählen Sie ein Theme oder passen Sie das Standard-Theme an
3. Siehe [Theme-Entwicklung](theme-development.md) für eigene Anpassungen

## Nginx-Konfiguration

Falls Sie Nginx verwenden, fügen Sie folgende Konfiguration hinzu:

```nginx
server {
    listen 80;
    server_name ihre-domain.de;
    root /var/www/html/public;

    index index.php;

    # Admin-Bereich
    location /admin {
        try_files $uri $uri/ /admin.php?$query_string;
    }

    # Frontend
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-Verarbeitung
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Assets zulassen
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Verweigere Zugriff auf sensible Dateien
    location ~ /\. {
        deny all;
    }

    location ~ /(config|logs|vendor|src) {
        deny all;
    }
}
```

## Docker-Setup (für Entwicklung)

Falls Sie lokal mit Docker entwickeln möchten:

1. **Docker Compose starten**:
   ```bash
   docker-compose up -d
   ```

2. **Website aufrufen**:
   - Frontend: `http://localhost:8000`
   - Admin: `http://localhost:8000/admin`

3. **Container stoppen**:
   ```bash
   docker-compose down
   ```

Siehe [Entwicklungs-Setup](development-setup.md) für mehr Details.

## Checkliste nach der Installation

- [ ] Admin-Passwort geändert
- [ ] `generate-password.php` gelöscht
- [ ] Schreibrechte für `content/` und `logs/` gesetzt
- [ ] Erste Seite erstellt
- [ ] Navigation eingerichtet
- [ ] Website-Einstellungen angepasst
- [ ] SSL/HTTPS aktiviert (empfohlen)
- [ ] Impressum & Datenschutz-Seiten erstellt

## Nächste Schritte

- 📖 [Admin-Bereich verwenden](admin-guide.md) - Lernen Sie alle Features kennen
- ⚙️ [Einstellungen & Konfiguration](configuration.md) - Detaillierte Konfiguration
- 🔒 [DSGVO-Konformität](dsgvo.md) - Rechtlich auf der sicheren Seite

## Hilfe benötigt?

- 🔧 [Troubleshooting](troubleshooting.md) - Häufige Probleme lösen
- 💬 GitHub Discussions - Community fragen
- 🐛 GitHub Issues - Fehler melden
