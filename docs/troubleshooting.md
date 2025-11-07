# Troubleshooting

Häufige Probleme und ihre Lösungen.

## Installation

### Problem: "Seite nicht gefunden" (404)

**Symptom**: Alle Seiten zeigen 404-Fehler

**Lösung**:
1. **Apache**: Prüfen Sie ob mod_rewrite aktiv ist
   ```bash
   sudo a2enmod rewrite
   sudo service apache2 restart
   ```

2. **Prüfen Sie .htaccess** in `public/`:
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^ index.php [QSA,L]
   ```

3. **Nginx**: Siehe [Installation](installation.md#nginx-konfiguration)

### Problem: Admin-Login funktioniert nicht

**Symptom**: "Ungültiger Benutzername oder Passwort"

**Lösung**:
1. Prüfen Sie Benutzername in `config/config.php`
2. Generieren Sie neuen Passwort-Hash:
   ```bash
   php -r "echo password_hash('NeuesPasswort', PASSWORD_DEFAULT);"
   ```
3. Ersetzen Sie Hash in `config/config.php`
4. Prüfen Sie ob Sessions funktionieren

### Problem: "Permission denied"

**Symptom**: Kann keine Seiten speichern

**Lösung**:
```bash
chmod 755 content/
chmod 755 content/pages/
chmod 755 content/media/
chmod 755 logs/
```

## Admin-Bereich

### Problem: Theme wird nicht geladen

**Symptom**: Seite hat keine Styles

**Lösung**:
1. Prüfen Sie Theme-Name in `config/config.php`
2. Prüfen Sie ob Theme-Ordner existiert: `themes/[theme-name]/`
3. Prüfen Sie ob `assets/bootstrap/` im Theme vorhanden ist
4. Browser-Console auf 404-Fehler prüfen

### Problem: Bilder werden nicht angezeigt

**Symptom**: Bilder zeigen 404

**Lösung**:
1. Prüfen Sie Bild-Pfad: `/content/media/bild.jpg`
2. Prüfen Sie Schreibrechte für `content/media/`
3. Prüfen Sie Dateigröße (max. 5 MB)
4. Unterstützte Formate: JPG, PNG, GIF, SVG, WebP

### Problem: Block-Editor lädt nicht

**Symptom**: Keine Blöcke sichtbar

**Lösung**:
1. Browser-Console auf JavaScript-Fehler prüfen
2. Prüfen Sie ob `/public/admin/assets/js/block-editor.js` existiert
3. Cache leeren (Strg+F5)
4. Prüfen Sie Module: Deaktivieren Sie alle Module testweise

## Module

### Problem: Modul kann nicht aktiviert werden

**Symptom**: Fehler beim Aktivieren

**Lösung**:
1. Prüfen Sie Logs: `logs/error.log`
2. Prüfen Sie Modul-Struktur:
   - `module.json` vorhanden?
   - Hauptklasse `[Name]Module.php` vorhanden?
3. Prüfen Sie Namespace in Hauptklasse
4. Prüfen Sie required fCMS-Version

### Problem: Modul-Assets werden nicht geladen

**Symptom**: Modul-CSS/JS fehlt

**Lösung**:
1. Prüfen Sie `getAdminAssets()` Methode im Modul
2. Pfade müssen absolut vom Document-Root sein:
   ```php
   '/modules/mein-modul/assets/js/script.js'
   ```
3. Prüfen Sie ob Datei existiert
4. Browser-Cache leeren

## Performance

### Problem: Langsame Ladezeiten

**Lösung**:
1. **Bilder optimieren**:
   - Max. 1-2 MB pro Bild
   - WebP verwenden
   - Lazy Loading aktivieren

2. **Cache aktivieren** (falls verfügbar):
   ```php
   'cache' => ['enabled' => true]
   ```

3. **Assets minifizieren** (Produktion):
   ```php
   'assets' => ['minify' => true]
   ```

### Problem: Out of Memory

**Symptom**: "Allowed memory size exhausted"

**Lösung**:
1. Erhöhen Sie PHP Memory Limit:
   ```ini
   memory_limit = 256M
   ```

2. Oder in `config/config.php`:
   ```php
   ini_set('memory_limit', '256M');
   ```

## Entwicklung

### Problem: Composer-Fehler

**Symptom**: Build schlägt fehl

**Lösung**:
```bash
# Cache leeren
composer clear-cache

# Dependencies neu installieren
rm -rf vendor/
composer install

# Build erneut
composer build
```

### Problem: Docker-Container startet nicht

**Lösung**:
```bash
# Container neu bauen
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Logs prüfen
docker-compose logs -f web
```

## Debug-Modus

### Debug aktivieren

In `config/config.php`:

```php
'debug' => [
    'enabled' => true,
    'display_errors' => true,
    'log_errors' => true,
],
```

**⚠️ Nur in Entwicklung aktivieren!**

### Logs prüfen

```bash
# Error-Log
tail -f logs/error.log

# Alle Logs
ls -la logs/
```

## Häufige Fehlermeldungen

### "Call to undefined function..."

**Ursache**: Fehlende Abhängigkeit oder Autoloader-Problem

**Lösung**:
```bash
composer dump-autoload
```

### "Class not found"

**Ursache**: Falscher Namespace oder fehlende Datei

**Lösung**:
1. Prüfen Sie Namespace in Klasse
2. Prüfen Sie Dateiname (muss mit Klassennamen übereinstimmen)
3. Composer Autoloader neu generieren

### "Headers already sent"

**Ursache**: Output vor HTTP-Headers

**Lösung**:
1. Entfernen Sie Leerzeichen/BOM vor `<?php`
2. Entfernen Sie `echo`/`print` vor Header-Ausgabe
3. Aktivieren Sie Output Buffering

## Noch Probleme?

1. **Logs prüfen**: `logs/error.log`
2. **GitHub Issues**: [Fehler melden](https://github.com/IhrRepo/fCMS/issues)
3. **Discussions**: [Community fragen](https://github.com/IhrRepo/fCMS/discussions)
4. **Debug-Modus**: Detaillierte Fehlermeldungen

## Checkliste Problemlösung

- [ ] Error-Logs geprüft
- [ ] Browser-Console geprüft
- [ ] Berechtigungen geprüft
- [ ] Cache geleert
- [ ] Debug-Modus aktiviert
- [ ] Konfiguration geprüft
- [ ] PHP-Version geprüft
- [ ] mod_rewrite aktiviert (Apache)
