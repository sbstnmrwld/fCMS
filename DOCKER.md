# fCMS - Docker Development Setup

## 🐳 Was ist enthalten?

- **Apache 2.4** mit mod_rewrite aktiviert
- **PHP 8.3** mit allen Extensions
- **Composer** für Dependency Management
- **Vollständige .htaccess-Unterstützung**

## 🚀 Schnellstart

### 1. Docker Desktop starten
Stelle sicher, dass Docker Desktop läuft.

### 2. Container starten

**Option A - Mit Script (empfohlen):**
```bash
./docker-start.sh
```

**Option B - Manuell:**
```bash
docker-compose up -d
```

### 3. Öffne im Browser
- Frontend: http://localhost:8000
- Admin: http://localhost:8000/admin

## 📋 Nützliche Befehle

### Container verwalten
```bash
# Container starten
docker-compose up -d

# Container stoppen
docker-compose down

# Container neu starten
docker-compose restart

# Logs anzeigen
docker-compose logs -f

# Nur Web-Container Logs
docker-compose logs -f web
```

### In Container einloggen
```bash
docker-compose exec web bash
```

Dann kannst du im Container z.B.:
```bash
# Composer Packages installieren
composer install

# PHP-Version prüfen
php -v

# Apache-Status prüfen
apache2ctl -S
```

### Container komplett neu bauen
```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

## 🔧 Konfiguration

### Port ändern
In `docker-compose.yml`:
```yaml
ports:
  - "8080:80"  # Ändere 8080 zu deinem gewünschten Port
```

### PHP-Version ändern
In `Dockerfile`:
```dockerfile
FROM php:8.2-apache  # Ändere zu 8.1, 8.2, etc.
```

### Zusätzliche PHP-Extensions
In `Dockerfile`:
```dockerfile
RUN docker-php-ext-install pdo pdo_mysql mbstring gd
```

## ⚡ Performance-Tipps

### macOS/Windows: Volume-Performance verbessern
Für bessere Performance kannst du in `docker-compose.yml` verwenden:
```yaml
volumes:
  - .:/var/www/html:cached
```

### Vendor-Verzeichnis außerhalb mounten
Für schnellere Composer-Performance:
```yaml
volumes:
  - .:/var/www/html
  - vendor-volume:/var/www/html/vendor

volumes:
  vendor-volume:
```

## 🐛 Troubleshooting

### Port 8000 bereits belegt?
```bash
# Stoppe den PHP-Dev-Server falls er läuft
pkill -f "php -S localhost:8000"

# Oder ändere den Port in docker-compose.yml
```

### Container startet nicht?
```bash
# Prüfe Docker-Status
docker info

# Prüfe Container-Logs
docker-compose logs web

# Prüfe laufende Container
docker ps -a
```

### Datei-Permissions-Probleme?
```bash
# Im Container
docker-compose exec web chown -R www-data:www-data /var/www/html/logs
docker-compose exec web chmod -R 775 /var/www/html/content
```

### Cache-Probleme?
```bash
# Browser-Cache leeren
# Oder Composer-Cache im Container löschen:
docker-compose exec web composer clear-cache
```

## 🔐 Sicherheit

⚠️ **Wichtig:** Dieses Setup ist NUR für die Entwicklung!

Für Produktion:
- [ ] Nutze eigenes Apache/PHP-Image
- [ ] Konfiguriere SSL/TLS
- [ ] Setze Umgebungsvariablen sicher
- [ ] Verwende Docker Secrets
- [ ] Implementiere Health-Checks
- [ ] Nutze Read-Only Filesysteme wo möglich

## 📊 Status prüfen

```bash
# Container-Status
docker-compose ps

# Ressourcen-Verwendung
docker stats

# Apache-Prozesse im Container
docker-compose exec web ps aux | grep apache
```

## 🗑️ Aufräumen

```bash
# Container und Volumes löschen
docker-compose down -v

# Docker-Images aufräumen
docker system prune -a
```

## 🆚 Docker vs. PHP Dev-Server

### Docker (Apache) ✅
- Echte .htaccess-Unterstützung
- Security-Headers funktionieren
- Wie in Produktion
- Isolierte Umgebung
- Etwas mehr Setup

### PHP Dev-Server ⚡
- Schneller Start
- Keine Installation nötig
- Gut für schnelle Tests
- Keine .htaccess-Unterstützung
- Nur für Entwicklung

## 💡 Tipps

1. **Logs beobachten während Entwicklung:**
   ```bash
   docker-compose logs -f web
   ```

2. **Shell-Alias erstellen** (in ~/.config/fish/config.fish):
   ```fish
   alias fcms-start='cd ~/Dev/fCMS && ./docker-start.sh'
   alias fcms-stop='cd ~/Dev/fCMS && docker-compose down'
   alias fcms-logs='cd ~/Dev/fCMS && docker-compose logs -f web'
   ```

3. **VS Code Docker Extension** installieren für GUI-Management

## 🎯 Nächste Schritte

Nach dem ersten Start:
1. ✅ Teste http://localhost:8000
2. ✅ Login im Admin-Bereich
3. ✅ Prüfe ob .htaccess funktioniert
4. ✅ Schaue dir die Logs an

Viel Erfolg! 🚀
