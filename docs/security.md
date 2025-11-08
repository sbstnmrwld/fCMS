# Sicherheits-Features in fCMS

## Übersicht

fCMS implementiert verschiedene Sicherheitsmaßnahmen zum Schutz vor gängigen Angriffen:

## 1. CSRF-Schutz

- Token-basierter Schutz für alle Admin-Formulare
- Automatische Token-Validierung im `CsrfManager`
- Token-Rotation bei jedem Request

## 2. Directory Traversal Prevention

### Validator::slug()
```php
// Blockiert: ../../../etc/passwd
// Erlaubt: meine-seite
```

- Prüft auf `..` in Slugs
- Validiert Slug-Format (alphanumerisch + Bindestriche)
- Verhindert absolute Pfade

### Module Asset Serving
```php
// Route: /modules/{module}/assets/{path}
// Sicherer Zugriff auf Modul-Assets mit Path-Normalisierung
```

## 3. XSS-Schutz

- HTML-Escaping in allen Templates via `htmlspecialchars()`
- Content Security Policy Headers (empfohlen)
- Twig Auto-Escaping in Themes

## 4. Race Condition Prevention

### Problem
Gleichzeitige Page-Erstellungen können Duplikate erzeugen:
```
Thread A: prüft "test-page" → existiert nicht
Thread B: prüft "test-page" → existiert nicht  
Thread A: erstellt "test-page.json"
Thread B: erstellt "test-page.json" → ÜBERSCHREIBT A!
```

### Lösung: File-Locking

```php
// In ContentManager::createPage()
$lockFile = $this->contentPath . '/.slug-creation.lock';
$lockHandle = fopen($lockFile, 'c');

try {
    flock($lockHandle, LOCK_EX); // Exklusives Lock
    
    $slug = $this->ensureUniqueSlug($slug); // Thread-safe
    $this->savePage($slug, $page);          // Thread-safe
    
    flock($lockHandle, LOCK_UN);
} finally {
    fclose($lockHandle);
}
```

**Vorteile:**
- ✅ Verhindert Slug-Duplikate
- ✅ Verhindert Datenverlust durch Überschreibung
- ✅ Standard PHP-Locking (keine Dependencies)
- ✅ Minimaler Performance-Overhead (~1-2ms)

**Tests:** 
- `tests/Unit/Core/ContentManagerRaceConditionTest.php` (3 Tests)
- `scripts/test-race-condition.php` (Manueller Fork-Test)

**Dokumentation:** `docs/race-condition-fix.md`

## 5. Session-Sicherheit

### SessionManager Features
- Secure Flags für Cookies (HTTPS)
- HttpOnly Flags
- SameSite=Strict
- Session-Regeneration nach Login
- Automatischer Timeout

```php
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => '',
    'secure' => true,      // Nur HTTPS
    'httponly' => true,    // Kein JS-Zugriff
    'samesite' => 'Strict' // CSRF-Schutz
]);
```

## 6. Input-Validierung

### Validator-Klasse
Zentrale Validierung aller Eingaben:

```php
// Seiten-Daten
Validator::pageData($data);

// Slugs
Validator::slug($slug);

// Navigation-Daten
Validator::navigationData($data);

// Modul-Konfiguration
Validator::moduleData($data);
```

**Features:**
- Typ-Validierung
- Längen-Limits
- Format-Prüfung
- Whitelist-basiert (kein Blacklisting)

## 7. File-Upload Sicherheit

### MediaController
- Dateityp-Validierung (MIME-Type + Extension)
- Dateigrößen-Limit
- Eindeutige Dateinamen (verhindert Überschreibung)
- Speicherung außerhalb von Public (wo möglich)

```php
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB
```

## 8. Authentication

### AuthManager
- Passwort-Hashing mit `password_hash()` (bcrypt)
- Rate-Limiting für Login-Versuche (empfohlen)
- Session-basierte Auth (keine JWT im LocalStorage)
- Separate Admin-Entry-Point (`admin.php`)

## 9. Error Handling

### Debug-Modus
```php
// config.php
'debug' => [
    'enabled' => false, // IMMER false in Production!
]
```

- Keine Stack-Traces im Frontend
- Logs statt Error-Output
- Custom Error-Pages

## 10. Dependency Management

### Composer
- Regelmäßige Updates via `composer update`
- Security Audits via `composer audit`
- Vendor-Dateien nicht im Repository

```bash
# Security Check
composer audit

# Updates
composer update --with-dependencies
```

## Best Practices

### Deployment

1. **`.env` für Secrets**
   ```php
   // config.php
   $adminPassword = getenv('ADMIN_PASSWORD');
   ```

2. **Schreibrechte minimieren**
   ```bash
   chmod 755 content/
   chmod 644 content/**/*.json
   ```

3. **HTTPS erzwingen**
   ```apache
   # .htaccess
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

4. **Admin-Pfad ändern**
   ```php
   // admin.php → my-secret-admin.php
   ```

### Monitoring

- Logs regelmäßig prüfen (`logs/`)
- Lock-Dateien überwachen (`.slug-creation.lock` sollte nicht dauerhaft gelockt sein)
- Failed-Login-Attempts loggen

## Security Checklist

- [ ] Debug-Modus deaktiviert
- [ ] HTTPS aktiviert
- [ ] Starkes Admin-Passwort
- [ ] Schreibrechte korrekt gesetzt
- [ ] Error-Reporting deaktiviert
- [ ] Composer-Pakete aktuell
- [ ] Backup-Strategie vorhanden
- [ ] CSRF-Tokens aktiviert
- [ ] Session-Timeout konfiguriert
- [ ] Content-Security-Policy Headers gesetzt

## Weitere Informationen

- **DSGVO:** `docs/dsgvo.md`
- **Race Conditions:** `docs/race-condition-fix.md`
- **Troubleshooting:** `docs/troubleshooting.md`
- **OWASP Top 10:** https://owasp.org/www-project-top-ten/
