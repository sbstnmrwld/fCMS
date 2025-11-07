# Release-Build erstellen

Erstellen Sie produktionsfertige ZIP-Releases von fCMS.

## Unterschied: Entwicklung vs. Produktion

### Entwicklungsversion

- Enthält `composer.json`, `composer.lock`
- Enthält Dev-Dependencies (PHPUnit, etc.)
- Enthält `.git`, Tests, Dokumentation
- Für Entwickler zum Arbeiten am Code

### Produktionsversion (Release-ZIP)

- Nur produktionsrelevante Dateien
- Optimierter Autoloader
- Keine Dev-Dependencies
- Keine Git-Dateien
- Bereit für direkten Upload auf Webspace

## Build-System

### Build-Script

Das Build-System befindet sich in `build/build.php`:

```php
<?php
/**
 * fCMS Release Builder
 *
 * Erstellt produktionsfertige ZIP-Releases
 */

// Build-Konfiguration
$config = [
    'version' => $argv[1] ?? 'dev',
    'buildDir' => __DIR__ . '/../builds',
    'releaseDir' => __DIR__ . '/../releases',
    'excludePatterns' => [
        '.git*',
        'tests/',
        'docs/',
        'build/',
        'builds/',
        '*.md',
        '.env*',
        'phpunit.xml'
    ]
];
```

### Build ausführen

```bash
# Mit Versionsnummer
php build/build.php 1.0.0

# Oder über Composer
composer build 1.0.0

# Entwicklungs-Build
composer build
```

## Build-Prozess

### Schritt 1: Vorbereitung

```bash
# Build-Verzeichnisse erstellen
mkdir -p builds/
mkdir -p releases/

# Composer-Dependencies für Produktion installieren
composer install --no-dev --optimize-autoloader
```

### Schritt 2: Dateien kopieren

**Eingeschlossen**:
- `public/` - Web-Root mit Entry Points
- `src/` - Core-Klassen
- `blocks/` - Standard-Blöcke
- `admin/` - Admin-Interface
- `themes/` - Standard-Theme
- `config/` - Konfiguration (ohne sensible Daten)
- `content/` - Beispiel-Content
- `languages/` - Übersetzungen
- `vendor/` - Produktions-Dependencies
- `logs/` - Log-Verzeichnis (leer)

**Ausgeschlossen**:
- `.git/` - Versionskontrolle
- `tests/` - Unit-Tests
- `docs/` - Dokumentation
- `build/` - Build-Scripts
- `builds/` - Build-Artefakte
- `*.md` - Dokumentations-Dateien
- `.env*` - Environment-Dateien
- `composer.lock` - Dev-Lock-File

### Schritt 3: Vendor-Optimierung

```php
// In build.php
function optimizeVendor(string $vendorPath): void
{
    // Entferne Dokumentation
    $excludePaths = [
        '*/docs/',
        '*/documentation/',
        '*/examples/',
        '*/test/',
        '*/tests/',
        '*/.git/',
        '*/README*',
        '*/CHANGELOG*',
        '*/LICENSE*'
    ];

    foreach ($excludePaths as $pattern) {
        $files = glob($vendorPath . '/' . $pattern, GLOB_ONLYDIR);
        foreach ($files as $file) {
            removeDirectory($file);
        }
    }
}
```

### Schritt 4: Konfiguration vorbereiten

```php
// config.example.php für Release anpassen
$config = [
    'site' => [
        'name' => 'Meine Website',
        'url' => 'https://example.com',
    ],
    'admin' => [
        'username' => 'admin',
        'password' => '', // Muss vom Benutzer gesetzt werden
    ],
    // ... weitere Standard-Konfiguration
];
```

### Schritt 5: ZIP erstellen

```php
function createZip(string $sourceDir, string $zipPath): void
{
    $zip = new ZipArchive();

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = substr($file->getPathname(), strlen($sourceDir) + 1);
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }

        $zip->close();
    }
}
```

### Schritt 6: Checksumme generieren

```php
$zipPath = "releases/fCMS-v{$version}.zip";
$checksum = md5_file($zipPath);

// Checksumme-Datei erstellen
file_put_contents("releases/fCMS-v{$version}.md5", $checksum);
```

### Schritt 7: Cleanup

```bash
# Entwicklungs-Dependencies wiederherstellen
composer install

# Build-Verzeichnis aufräumen
rm -rf builds/temp-*
```

## Build-Konfiguration

### composer.json

```json
{
  "scripts": {
    "build": "php build/build.php",
    "build:release": "php build/build.php stable"
  },
  "scripts-descriptions": {
    "build": "Erstellt Entwicklungs-Build",
    "build:release": "Erstellt Produktions-Release"
  }
}
```

### build.json (Optional)

```json
{
  "exclude": [
    ".git*",
    "tests/",
    "docs/",
    "*.md",
    "phpunit.xml",
    ".env*"
  ],
  "compress": true,
  "optimization": {
    "removeComments": false,
    "minifyCSS": false,
    "minifyJS": false
  }
}
```

## Ausgabe-Struktur

```
releases/
├── fCMS-v1.0.0.zip           # Release-ZIP
├── fCMS-v1.0.0.md5           # Checksumme
└── RELEASES.md               # Release-Notes
```

### ZIP-Inhalt

```
fCMS-v1.0.0/
├── admin/
│   ├── assets/
│   └── templates/
├── blocks/
├── config/
│   └── config.example.php
├── content/
│   ├── pages/
│   └── media/
├── languages/
├── logs/
├── public/
│   ├── .htaccess
│   ├── index.php
│   ├── admin.php
│   └── generate-password.php
├── src/
├── themes/
├── vendor/
├── LICENSE
└── INSTALL.txt
```

## Automatisierung

### GitHub Actions (Optional)

**.github/workflows/build.yml**:

```yaml
name: Build Release

on:
  push:
    tags:
      - 'v*'

jobs:
  build:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'

    - name: Install dependencies
      run: composer install --no-dev --optimize-autoloader

    - name: Build release
      run: php build/build.php ${{ github.ref_name }}

    - name: Upload release
      uses: actions/upload-artifact@v3
      with:
        name: fCMS-${{ github.ref_name }}
        path: releases/
```

### Lokaler Build-Hook

**scripts/build.sh**:

```bash
#!/bin/bash

VERSION=${1:-dev}
echo "Building fCMS v$VERSION..."

# Pre-build checks
php -l public/index.php || exit 1
php -l public/admin.php || exit 1

# Run build
php build/build.php $VERSION

# Post-build verification
if [ -f "releases/fCMS-v$VERSION.zip" ]; then
    echo "✓ Build successful: releases/fCMS-v$VERSION.zip"
    echo "✓ Size: $(du -h releases/fCMS-v$VERSION.zip | cut -f1)"
    echo "✓ MD5: $(cat releases/fCMS-v$VERSION.md5)"
else
    echo "✗ Build failed"
    exit 1
fi
```

## Qualitätssicherung

### Pre-Build Tests

```bash
# Syntax-Check
find src/ -name "*.php" -exec php -l {} \;
find blocks/ -name "*.php" -exec php -l {} \;

# Unit-Tests
./vendor/bin/phpunit

# Code-Style
./vendor/bin/phpcs src/ --standard=PSR12
```

### Post-Build Verifikation

```php
// build/verify.php
function verifyBuild(string $buildPath): bool
{
    // Prüfe erforderliche Dateien
    $required = [
        'public/index.php',
        'public/admin.php',
        'config/config.example.php',
        'vendor/autoload.php'
    ];

    foreach ($required as $file) {
        if (!file_exists($buildPath . '/' . $file)) {
            echo "Missing required file: $file\n";
            return false;
        }
    }

    // Prüfe PHP-Syntax
    $phpFiles = glob($buildPath . '/{src,blocks,public}/*.php', GLOB_BRACE);
    foreach ($phpFiles as $file) {
        $output = shell_exec("php -l $file");
        if (strpos($output, 'No syntax errors') === false) {
            echo "Syntax error in: $file\n";
            return false;
        }
    }

    return true;
}
```

## Deployment

### Upload auf Server

```bash
# ZIP auf Server hochladen
scp releases/fCMS-v1.0.0.zip user@server:/var/www/

# Auf Server entpacken
ssh user@server "cd /var/www && unzip -o fCMS-v1.0.0.zip"
```

### Automatisches Deployment

**deploy.php** (Simple):

```php
<?php
$version = $argv[1] ?? 'dev';
$zipFile = "releases/fCMS-v$version.zip";

if (!file_exists($zipFile)) {
    echo "Build not found: $zipFile\n";
    exit(1);
}

// Upload via FTP/SFTP
$connection = ssh2_connect('your-server.com', 22);
ssh2_auth_password($connection, 'username', 'password');

$sftp = ssh2_sftp($connection);
$stream = fopen("ssh2.sftp://$sftp/path/to/webroot/fCMS.zip", 'w');
fwrite($stream, file_get_contents($zipFile));
fclose($stream);

// Remote unzip
ssh2_exec($connection, 'cd /path/to/webroot && unzip -o fCMS.zip');
```

## Checkliste

### Pre-Build

- [ ] Tests erfolgreich
- [ ] Code-Style geprüft
- [ ] Version aktualisiert
- [ ] Changelog gepflegt
- [ ] Dependencies aktualisiert

### Build

- [ ] Build-Script ausgeführt
- [ ] ZIP erstellt
- [ ] Checksumme generiert
- [ ] Größe geprüft (< 50MB)
- [ ] Inhalt verifiziert

### Post-Build

- [ ] ZIP entpackt und getestet
- [ ] Installation geprüft
- [ ] Admin-Login funktioniert
- [ ] Frontend funktioniert
- [ ] Release-Notes erstellt

## Troubleshooting

### Build schlägt fehl

```bash
# Composer-Cache leeren
composer clear-cache
rm -rf vendor/
composer install

# Berechtigungen prüfen
chmod +x build/build.php
chmod 755 builds/ releases/
```

### ZIP zu groß

```bash
# Vendor-Größe prüfen
du -sh vendor/*/

# Unnötige Pakete entfernen
composer remove --dev package/name
```

### Beschädigte ZIP

```bash
# ZIP testen
unzip -t releases/fCMS-v1.0.0.zip

# Neu erstellen
rm releases/fCMS-v1.0.0.zip
php build/build.php 1.0.0
```

## Weiterführend

- [Entwicklungs-Setup](development-setup.md) - Lokale Entwicklung
- [Konfiguration](configuration.md) - Produktions-Konfiguration
- [Installation](installation.md) - Release installieren
