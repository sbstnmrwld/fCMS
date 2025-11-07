# Konfiguration & Einstellungen

Detaillierte Anleitung zur Konfiguration von fCMS.

## Konfigurationsdatei

Die Hauptkonfiguration befindet sich in `config/config.php`.

### Basis-Konfiguration

```php
<?php

return [
    // Website-Einstellungen
    'site' => [
        'name' => 'Meine Website',
        'url' => 'https://example.com',
        'language' => 'de',
        'timezone' => 'Europe/Berlin',
    ],

    // Admin-Zugangsdaten
    'admin' => [
        'username' => 'admin',
        'password' => '$2y$10$...', // Passwort-Hash
    ],

    // Theme-Einstellungen
    'theme' => [
        'active' => 'default',
    ],

    // Pfade
    'paths' => [
        'content' => __DIR__ . '/../content',
        'themes' => __DIR__ . '/../themes',
        'logs' => __DIR__ . '/../logs',
        'admin' => __DIR__ . '/../public/admin',
        'modules' => __DIR__ . '/../modules',
    ],

    // Session-Einstellungen
    'session' => [
        'name' => 'fcms_session',
        'lifetime' => 1800, // 30 Minuten
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ],

    // Sicherheit
    'security' => [
        'login_attempts' => 5,
        'lockout_time' => 900, // 15 Minuten
    ],

    // Debug (NUR für Entwicklung!)
    'debug' => [
        'enabled' => false,
        'display_errors' => false,
        'log_errors' => true,
    ],
];
```

## Einstellungen-Manager

fCMS verwendet einen SettingsManager für dynamische Einstellungen.

### Einstellungen speichern

```php
$settings->set('site.name', 'Neuer Name');
$settings->set('theme.active', 'custom-theme');
$settings->save();
```

### Einstellungen abrufen

```php
$siteName = $settings->get('site.name');
$theme = $settings->get('theme.active', 'default'); // mit Fallback
```

### Dot-Notation

```php
$settings->get('site.name')           // Top-Level
$settings->get('theme.options.color') // Verschachtelt
```

## Umgebungsvariablen

Für sensible Daten können Umgebungsvariablen verwendet werden:

### .env-Datei erstellen

```env
ADMIN_USERNAME=admin
ADMIN_PASSWORD_HASH=$2y$10$...
DB_CONNECTION=mysql # falls benötigt
DB_HOST=localhost
DB_DATABASE=fcms
```

### In config.php verwenden

```php
'admin' => [
    'username' => getenv('ADMIN_USERNAME') ?: 'admin',
    'password' => getenv('ADMIN_PASSWORD_HASH'),
],
```

## Passwort-Hash generieren

### Methode 1: generate-password.php

```bash
# Nach Upload aufrufen
https://ihre-domain.de/generate-password.php
```

### Methode 2: PHP-CLI

```bash
php -r "echo password_hash('IhrPasswort', PASSWORD_DEFAULT) . \"\\n\";"
```

### Methode 3: PHP-Script

```php
<?php
echo password_hash('IhrPasswort', PASSWORD_DEFAULT);
```

## Theme-Konfiguration

### theme.json

Jedes Theme hat eine `theme.json`:

```json
{
  "name": "Mein Theme",
  "version": "1.0.0",
  "description": "Beschreibung",
  "author": "Ihr Name",
  "screenshot": "screenshot.png",
  "supports": {
    "navigation": true,
    "footer": true,
    "widgets": false
  }
}
```

## Sprachen

### Verfügbare Sprachen

- `de` - Deutsch (Standard)
- `en` - Englisch
- Weitere können in `languages/` hinzugefügt werden

### Sprache wechseln

In `config/config.php`:

```php
'site' => [
    'language' => 'en', // Englisch
],
```

### Eigene Übersetzungen

Erstellen Sie `languages/de.json`:

```json
{
  "common": {
    "save": "Speichern",
    "cancel": "Abbrechen",
    "delete": "Löschen"
  },
  "pages": {
    "title": "Seiten",
    "create": "Neue Seite erstellen"
  }
}
```

## Performance

### Cache aktivieren (Optional)

```php
'cache' => [
    'enabled' => true,
    'driver' => 'file',
    'path' => __DIR__ . '/../cache',
    'lifetime' => 3600, // 1 Stunde
],
```

### Assets minifizieren

Für Produktion:

```php
'assets' => [
    'minify' => true,
    'combine' => true,
    'cache_bust' => true,
],
```

## Sicherheit

### HTTPS erzwingen

```php
'security' => [
    'force_https' => true,
],
```

### CORS-Header

```php
'security' => [
    'cors' => [
        'enabled' => false,
        'origin' => '*',
        'methods' => 'GET, POST',
    ],
],
```

### Content Security Policy

```php
'security' => [
    'csp' => [
        'enabled' => true,
        'directives' => [
            'default-src' => "'self'",
            'script-src' => "'self' 'unsafe-inline'",
            'style-src' => "'self' 'unsafe-inline'",
        ],
    ],
],
```

## Logging

### Log-Level konfigurieren

```php
'logging' => [
    'level' => 'error', // debug, info, warning, error
    'path' => __DIR__ . '/../logs',
    'max_files' => 30,
    'max_size' => 10485760, // 10 MB
],
```

### Logs rotieren

Alte Logs werden automatisch nach 30 Tagen gelöscht.

## Checkliste

- [ ] Website-Name und URL angepasst
- [ ] Admin-Passwort geändert
- [ ] Theme ausgewählt
- [ ] Sprache konfiguriert
- [ ] Zeitzone gesetzt
- [ ] HTTPS aktiviert
- [ ] Debug-Modus deaktiviert (Produktion)
- [ ] Logs konfiguriert
