# Entwicklungs-Setup

Richten Sie Ihre lokale Entwicklungsumgebung für fCMS ein.

## Voraussetzungen

- **PHP**: 7.4+ (8.0+ empfohlen)
- **Composer**: Neueste Version
- **Git**: Für Versionskontrolle
- **Docker** (optional): Für isolierte Entwicklung

## Repository klonen

```bash
git clone https://github.com/IhrRepo/fCMS.git
cd fCMS
```

## Composer-Dependencies installieren

```bash
composer install
```

Dies installiert:
- Slim Framework
- PHP-DI
- Symfony Components
- PHPUnit (dev)

## Konfiguration

### 1. Config-Datei erstellen

```bash
cp config/config.example.php config/config.php
```

### 2. Passwort-Hash generieren

```bash
php -r "echo password_hash('admin', PASSWORD_DEFAULT) . \"\\n\";"
```

Kopieren Sie den Hash in `config/config.php`:

```php
'admin' => [
    'username' => 'admin',
    'password' => '$2y$10$...', // Ihr Hash
],
```

### 3. Bootstrap-Assets installieren

⚠️ **Nur für Entwicklung erforderlich**: Produktions-ZIP enthält bereits alle benötigten Assets!

**Bootstrap wird verwendet für:**
- **Admin-Bereich**: Zwingend erforderlich für Admin-Interface
- **Default-Theme**: Optional - eigene Themes können beliebiges CSS verwenden

**Option 1: Automatisches Setup-Script** (empfohlen)

```bash
./scripts/setup-bootstrap.sh
```

**Option 2: Manuell herunterladen**

1. Besuchen Sie https://getbootstrap.com/
2. Laden Sie "Compiled CSS and JS" herunter
3. Entpacken Sie die Datei

4. **Für Admin-Bereich** (zwingend):
   ```bash
   mkdir -p public/admin/assets/bootstrap
   cp -r bootstrap/css/ public/admin/assets/bootstrap/css/
   cp -r bootstrap/js/ public/admin/assets/bootstrap/js/
   ```

5. **Für Default-Theme** (optional):
   ```bash
   mkdir -p public/themes/default/assets/bootstrap
   cp -r bootstrap/css/ public/themes/default/assets/bootstrap/css/
   cp -r bootstrap/js/ public/themes/default/assets/bootstrap/js/
   ```

**Option 3: Bootstrap Icons** (nur für Admin-Bereich):

1. Laden Sie Bootstrap Icons von https://icons.getbootstrap.com/ herunter
2. Kopieren Sie:
   ```bash
   mkdir -p public/admin/assets/bootstrap-icons
   cp bootstrap-icons.min.css public/admin/assets/bootstrap-icons/
   cp -r fonts/ public/admin/assets/bootstrap-icons/
   ```

**Benötigte Dateien**:
```
public/admin/assets/bootstrap/
├── css/
│   ├── bootstrap.min.css
│   └── bootstrap.min.css.map
└── js/
    ├── bootstrap.bundle.min.js
    └── bootstrap.bundle.min.js.map

public/themes/default/assets/bootstrap/
├── css/
│   ├── bootstrap.min.css
│   └── bootstrap.min.css.map
└── js/
    ├── bootstrap.bundle.min.js
    └── bootstrap.bundle.min.js.map
```

## Lokalen Server starten

### Option 1: Docker (empfohlen)

```bash
# Container starten
docker-compose up -d

# Status prüfen
docker-compose ps

# Logs anzeigen
docker-compose logs -f web
```

Öffnen Sie: http://localhost:8000

### Option 2: PHP Built-in Server

```bash
php -S localhost:8000 -t public/
```

**Hinweis**: Der eingebaute PHP-Server ist nur für schnelle Tests geeignet. Für die Entwicklung wird Docker empfohlen.

## Docker-Entwicklung

### Was ist enthalten?

- **Apache 2.4** mit mod_rewrite
- **PHP 8.3** mit allen Extensions
- **Composer** vorinstalliert
- Automatisches Volume-Mounting

### Quick Start

```bash
# Container starten
docker-compose up -d

# Logs anzeigen
docker-compose logs -f web

# Container stoppen
docker-compose down

# Container neu starten
docker-compose restart web
```

### In Container ausführen

```bash
# Bash-Shell öffnen
docker-compose exec web bash

# Composer-Befehl ausführen
docker-compose exec web composer install

# PHP-Script ausführen
docker-compose exec web php script.php
```

### docker-compose.yml

```yaml
version: '3.8'

services:
  web:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8000:80"
    volumes:
      - .:/var/www/html
    environment:
      - APACHE_DOCUMENT_ROOT=/var/www/html/public
```

### Dockerfile

```dockerfile
FROM php:8.3-apache

# Apache Rewrite aktivieren
RUN a2enmod rewrite

# PHP Extensions
RUN docker-php-ext-install pdo pdo_mysql

# Composer installieren
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Arbeitsverzeichnis
WORKDIR /var/www/html

# Apache-Konfiguration
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Berechtigungen
RUN chown -R www-data:www-data /var/www/html
```

## Asset-Struktur

### Verzeichnis-Layout

```
public/
├── admin/
│   └── assets/
│       ├── bootstrap/     # Bootstrap-Dateien
│       ├── bootstrap-icons/ # Icons
│       ├── css/
│       │   └── admin.css  # Admin-Styles
│       ├── js/
│       │   ├── admin.js
│       │   └── block-editor.js
│       ├── fonts/
│       └── images/
└── themes/
    └── default/
        └── assets/
            ├── bootstrap/  # Bootstrap-Dateien
            ├── css/
            │   └── style.css
            ├── js/
            │   └── main.js
            ├── fonts/
            └── images/
```

### Asset-Manager

fCMS verwendet separate Asset-Manager:

**Admin-Assets** (`AdminAssetManager`):
```php
$adminAssets->bootstrapCss()      // Admin Bootstrap
$adminAssets->css('admin.css')    // Admin-Styles
$adminAssets->js('admin.js')      // Admin-Scripts
```

**Theme-Assets** (`ThemeAssetManager`):
```php
$assets->bootstrapCss()           // Theme Bootstrap
$assets->css('style.css')         // Theme-Styles
$assets->js('main.js')            // Theme-Scripts
```

### Cache-Busting

Assets haben automatisch Versions-Parameter:
```html
<link href="/admin/assets/css/admin.css?v=1699384800" rel="stylesheet">
```

Basierend auf `filemtime()` der Datei.

## Entwicklungs-Workflow

### 1. Änderungen machen

```bash
# Core-Code
src/Core/MeinService.php

# Admin-Assets
public/admin/assets/css/admin.css

# Theme-Assets
public/themes/default/assets/css/style.css
```

### 2. Änderungen testen

- Browser: http://localhost:8000
- Hard-Refresh: Strg/Cmd + Shift + R
- Cache leeren wenn nötig

### 3. Autoloader aktualisieren

Falls neue Klassen:
```bash
composer dump-autoload
```

### 4. Tests ausführen

```bash
composer test
# oder
./vendor/bin/phpunit
```

### 5. Code-Style prüfen

```bash
composer phpcs
# oder
./vendor/bin/phpcs
```

## Debug-Modus

In `config/config.php`:

```php
'debug' => [
    'enabled' => true,
    'display_errors' => true,
    'log_errors' => true,
],
```

**Fehlerausgabe**:
- Detaillierte Fehlermeldungen
- Stack Traces
- Query-Logs (falls DB verwendet)

**Logs prüfen**:
```bash
tail -f logs/error.log
```

## Nützliche Scripts

### Passwort-Hash generieren

```bash
php -r "echo password_hash('password', PASSWORD_DEFAULT) . \"\\n\";"
```

### Composer-Cache leeren

```bash
composer clear-cache
rm -rf vendor/
composer install
```

### Berechtigungen setzen

```bash
chmod 755 content/ content/pages/ content/media/ logs/
```

## IDE-Setup

### VS Code

**Empfohlene Extensions**:
- PHP Intelephense
- PHP Debug
- PHP DocBlocker
- EditorConfig

**.vscode/settings.json**:
```json
{
  "php.validate.executablePath": "/usr/bin/php",
  "files.associations": {
    "*.php": "php"
  }
}
```

### PHPStorm

- PHP Interpreter konfigurieren
- Composer-Pfad setzen
- PHPUnit-Konfiguration laden

## Troubleshooting

### Bootstrap-Assets fehlen

```bash
# Prüfen ob vorhanden
ls -la public/admin/assets/bootstrap/
ls -la public/themes/default/assets/bootstrap/

# Falls nicht: Setup-Script ausführen
./scripts/setup-bootstrap.sh
```

### Composer-Fehler

```bash
composer clear-cache
composer self-update
composer install
```

### Docker-Container startet nicht

```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
docker-compose logs -f
```

### Berechtigungsfehler

```bash
chmod -R 755 content/ logs/
chown -R www-data:www-data content/ logs/ # Docker
```

## Nächste Schritte

- 🎨 [Theme-Entwicklung](theme-development.md) - Eigenes Theme erstellen
- 🧩 [Block-Entwicklung](block-development.md) - Custom Blöcke entwickeln
- 🔌 [Modul-Entwicklung](module-development.md) - Eigene Module erstellen
- 📦 [Build-Release](build-release.md) - Produktions-Build erstellen

## Ressourcen

- [Slim Framework Docs](https://www.slimframework.com/)
- [PHP-DI Docs](https://php-di.org/)
- [Bootstrap Docs](https://getbootstrap.com/)
- [Composer Docs](https://getcomposer.org/)
