# fCMS - Dateibasiertes Content-Management-System

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

**fCMS** ist ein modernes, dateibasiertes Content-Management-System, das speziell für kleine Organisationen wie Fördervereine entwickelt wurde. Es benötigt keine Datenbank, ist DSGVO-konform und kann auf einfachem Webspace ohne technische Vorkenntnisse betrieben werden.

## 📋 Inhaltsverzeichnis

- [Für Endanwender](#-für-endanwender)
  - [Was ist fCMS?](#was-ist-fcms)
  - [Systemanforderungen](#systemanforderungen)
  - [Installation](#installation)
  - [Erste Schritte](#erste-schritte)
  - [Admin-Bereich verwenden](#admin-bereich-verwenden)
- [Für Entwickler](#-für-entwickler)
  - [Entwicklungs-Setup](#entwicklungs-setup)
  - [Architektur-Übersicht](#architektur-übersicht)
  - [Release-Build erstellen](#release-build-erstellen)
  - [Theme-Entwicklung](#theme-entwicklung)
  - [Block-Entwicklung](#block-entwicklung)
  - [Asset-Management](#asset-management)
- [DSGVO-Konformität](#-dsgvo-konformität)
- [Troubleshooting](#-troubleshooting)
- [Support](#-support)

---

## 👥 Für Endanwender

### Was ist fCMS?

fCMS ist ein einfach zu bedienendes Content-Management-System, das:

- ✅ **Keine Datenbank benötigt** - Alle Daten werden in Dateien gespeichert
- ✅ **DSGVO-konform ist** - Keine Cookies im Frontend, keine Datenerhebung
- ✅ **Auf jedem Webspace läuft** - Nur PHP wird benötigt
- ✅ **Einfach zu installieren ist** - ZIP entpacken und hochladen
- ✅ **Intuitiv bedienbar ist** - Block-Editor ähnlich wie WordPress
- ✅ **Auf Deutsch verfügbar ist** - Komplette Benutzeroberfläche auf Deutsch
- ✅ **Mehrsprachig erweiterbar ist** - Unterstützung für weitere Sprachen

### Systemanforderungen

Ihr Webspace muss folgende Anforderungen erfüllen:

- **PHP**: Version 7.4 oder höher
- **Webserver**: Apache (mit mod_rewrite) oder Nginx
- **Speicherplatz**: Mindestens 50 MB
- **Schreibrechte**: Für Verzeichnisse `content/`, `logs/`

**Keine Datenbank erforderlich!**

### Installation

#### Schritt 1: Download

Laden Sie die neueste Version von fCMS herunter:
- `fCMS-v1.0.0.zip` (Produktionsversion)

#### Schritt 2: Entpacken

Entpacken Sie die ZIP-Datei auf Ihrem Computer.

#### Schritt 3: Konfiguration anpassen

1. Öffnen Sie die Datei `config/config.php`
2. Passen Sie folgende Einstellungen an:

```php
'site' => [
    'name' => 'Ihr Vereinsname',  // Name Ihrer Website
    'url' => 'https://ihre-domain.de',  // Ihre URL
],
```

3. **WICHTIG**: Ändern Sie das Admin-Passwort:

```php
'admin' => [
    'username' => 'admin',
    'password' => '$2y$10$...',  // Hier Ihren Hash einfügen
],
```

Um einen Passwort-Hash zu generieren:
- Nach dem Upload: Rufen Sie `https://ihre-domain.de/generate-password.php` auf
- Oder verwenden Sie Online-Tool für PHP password_hash

#### Schritt 4: Hochladen

Laden Sie **alle Dateien** per FTP/SFTP auf Ihren Webserver:
- Zielverzeichnis: `public_html/` oder `httpdocs/` (je nach Provider)

#### Schritt 5: Berechtigungen setzen

Stellen Sie sicher, dass folgende Verzeichnisse beschreibbar sind (chmod 755 oder 775):

```
content/
content/pages/
content/media/
logs/
```

#### Schritt 6: Fertig!

1. Rufen Sie Ihre Website auf: `https://ihre-domain.de`
2. Melden Sie sich im Admin-Bereich an: `https://ihre-domain.de/admin`
3. **Löschen Sie `generate-password.php`** nach dem ersten Login!

### Erste Schritte

Nach der Installation:

1. **Erste Seite erstellen**
   - Gehen Sie zu "Seiten" im Admin-Bereich
   - Klicken Sie auf "Neue Seite erstellen"
   - Fügen Sie Inhalte mit dem Block-Editor hinzu
   - Speichern und veröffentlichen

2. **Navigation einrichten**
   - Gehen Sie zu "Navigation"
   - Wählen Sie, welche Seiten im Hauptmenü erscheinen sollen
   - Legen Sie die Reihenfolge fest

3. **Design anpassen**
   - Gehen Sie zu "Einstellungen" → "Theme"
   - Wählen Sie ein Theme oder passen Sie das Standard-Theme an

### Admin-Bereich verwenden

#### Dashboard

Das Dashboard zeigt Ihnen eine Übersicht über:
- Anzahl der Seiten
- Veröffentlichte Seiten
- Entwürfe

#### Seiten verwalten

**Neue Seite erstellen:**
1. Klicken Sie auf "Seiten" → "Neue Seite"
2. Geben Sie einen Titel ein
3. Fügen Sie Blöcke hinzu (Absatz, Überschrift, Bild, etc.)
4. Speichern Sie als Entwurf oder veröffentlichen Sie direkt

**Verfügbare Blöcke:**
- **Absatz**: Einfacher Text
- **Überschrift**: H1-H6 Überschriften
- **Bild**: Bilder mit Beschriftung
- **Liste**: Nummerierte oder Aufzählungslisten
- **Zitat**: Hervorgehobene Zitate
- **Button**: Call-to-Action Buttons
- Weitere Blöcke können hinzugefügt werden

**Seite bearbeiten:**
- Blöcke per Drag & Drop verschieben
- Blöcke bearbeiten durch Klick
- Blöcke löschen über das Papierkorb-Symbol

#### Navigation

Legen Sie fest, wo Seiten erscheinen:
- **Hauptnavigation**: Oberes Menü
- **Footer**: Fußzeile
- **Nicht anzeigen**: Seite ist nur per direktem Link erreichbar

#### Medien

Verwalten Sie Ihre Bilder und Dateien:
- Dateien hochladen
- Medien-Bibliothek durchsuchen
- In Blöcken verwenden

#### Einstellungen

- Website-Name und URL
- Sprache
- Theme auswählen
- Zeitzone

---

## 💻 Für Entwickler

### Entwicklungs-Setup

fCMS wird mit modernen PHP-Frameworks und Composer entwickelt, aber als produktionsfertiges ZIP ohne Entwicklungs-Dependencies ausgeliefert.

#### Voraussetzungen

- PHP 7.4 oder höher
- Composer
- Git (optional)

#### Repository klonen

```bash
git clone https://github.com/IhrRepo/fCMS.git
cd fCMS
```

#### Dependencies installieren

```bash
composer install
```

#### Konfiguration

```bash
cp config/config.example.php config/config.php
# Passen Sie config/config.php an
```

#### Lokalen Server starten

```bash
php -S localhost:8000 -t public/
```

Öffnen Sie `http://localhost:8000` im Browser.

### Architektur-Übersicht

fCMS basiert auf:

- **Slim Framework 4**: Leichtgewichtiges PHP-Framework für Routing
- **Twig**: Template-Engine (optional, aktuell natives PHP)
- **PHP-DI**: Dependency Injection Container
- **Symfony Components**: Filesystem, YAML
- **PSR-Standards**: PSR-4 Autoloading, PSR-7 HTTP Messages

#### Verzeichnisstruktur

```
fCMS/
├── admin/                  # Admin-Bereich
│   ├── assets/            # Admin-Assets (Bootstrap, CSS, JS)
│   │   ├── bootstrap/
│   │   ├── css/
│   │   ├── js/
│   │   ├── fonts/
│   │   └── images/
│   └── templates/         # Admin-Templates
├── blocks/                # Block-Typen
│   ├── ParagraphBlock.php
│   ├── HeadingBlock.php
│   └── ...
├── build/                 # Build-System
│   └── build.php
├── config/                # Konfiguration
│   ├── config.example.php
│   └── config.php
├── content/               # Inhalte (JSON)
│   ├── pages/
│   └── media/
├── languages/             # Sprachdateien
│   ├── de.json
│   └── en.json
├── logs/                  # Log-Dateien
├── public/                # Web-Root
│   ├── .htaccess
│   ├── index.php          # Frontend Entry Point
│   ├── admin.php          # Admin Entry Point
│   └── generate-password.php
├── releases/              # Build-Outputs
├── src/                   # Core-Klassen
│   ├── Blocks/
│   │   ├── AbstractBlock.php
│   │   ├── BlockInterface.php
│   │   └── BlockRegistry.php
│   └── Core/
│       ├── AdminAssetManager.php
│       ├── AuthManager.php
│       ├── ContentManager.php
│       ├── CsrfManager.php
│       ├── LanguageManager.php
│       ├── SessionManager.php
│       ├── ThemeAssetManager.php
│       └── ThemeManager.php
├── themes/                # Themes
│   └── default/
│       ├── assets/        # Theme-Assets (Bootstrap, CSS, JS)
│       │   ├── bootstrap/
│       │   ├── css/
│       │   ├── js/
│       │   ├── fonts/
│       │   └── images/
│       ├── templates/
│       │   ├── layout.php
│       │   └── page.php
│       └── theme.json
├── vendor/                # Composer Dependencies
├── composer.json
└── README.md
```

### Release-Build erstellen

#### Unterschied: Entwicklung vs. Produktion

**Entwicklungsversion:**
- Enthält `composer.json`, `composer.lock`
- Enthält Dev-Dependencies (PHPUnit, etc.)
- Enthält `.git`, Tests, Dokumentation
- Für Entwickler zum Arbeiten am Code

**Produktionsversion (Release-ZIP):**
- Nur produktionsrelevante Dateien
- Optimierter Autoloader
- Keine Dev-Dependencies
- Keine Git-Dateien
- Bereit für direkten Upload auf Webspace

#### Build-Prozess

**Schritt 1: Build ausführen**

```bash
composer build
# oder
php build/build.php 1.0.0
```

**Schritt 2: Release-ZIP finden**

```
releases/fCMS-v1.0.0.zip
```

**Was passiert beim Build:**

1. ✓ Composer-Dependencies werden mit `--no-dev --optimize-autoloader` installiert
2. ✓ Alle Dateien werden gefiltert kopiert
3. ✓ Entwicklungs-Dateien werden ausgeschlossen (.git, tests, etc.)
4. ✓ Vendor-Verzeichnis wird optimiert (Dokumentation, Tests entfernt)
5. ✓ Konfigurationsdatei wird vorbereitet
6. ✓ ZIP-Archiv wird erstellt
7. ✓ MD5-Checksumme wird generiert
8. ✓ Entwicklungs-Dependencies werden wiederhergestellt

#### Build-Konfiguration anpassen

Bearbeiten Sie `build/build.php` um:
- Exclude-Patterns anzupassen
- Weitere Optimierungen hinzuzufügen
- Build-Ausgabe zu ändern

### Theme-Entwicklung

#### Neues Theme erstellen

**Schritt 1: Theme-Ordner erstellen**

```
themes/
└── mein-theme/
    ├── assets/
    │   ├── bootstrap/      # Bootstrap-Dateien
    │   │   ├── css/
    │   │   └── js/
    │   ├── css/
    │   │   └── style.css
    │   ├── js/
    │   │   └── main.js
    │   ├── fonts/
    │   └── images/
    ├── templates/
    │   ├── layout.php
    │   └── page.php
    └── theme.json
```

**Schritt 2: theme.json erstellen**

```json
{
  "name": "Mein Theme",
  "version": "1.0.0",
  "description": "Beschreibung meines Themes",
  "author": "Ihr Name"
}
```

**Schritt 3: Templates erstellen**

**layout.php** - Haupt-Layout:

```php
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?= $page['title'] ?? 'Home' ?> - <?= $siteName ?></title>

    <!-- Bootstrap aus Theme-Assets -->
    <link href="<?= $assets->bootstrapCss() ?>" rel="stylesheet">

    <!-- Theme CSS -->
    <link href="<?= $assets->css('style.css') ?>" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <!-- Navigation -->
    </nav>

    <main>
        <?= $content ?>
    </main>

    <footer>
        <!-- Footer -->
    </footer>

    <!-- Bootstrap JS aus Theme-Assets -->
    <script src="<?= $assets->bootstrapJs() ?>"></script>

    <!-- Theme JS -->
    <script src="<?= $assets->js('main.js') ?>"></script>
</body>
</html>
```

**page.php** - Seiten-Template:

```php
<div class="container">
    <article>
        <h1><?= htmlspecialchars($page['title']) ?></h1>

        <?php foreach ($page['sections'] as $section): ?>
            <?= $section['html'] ?>
        <?php endforeach; ?>
    </article>
</div>
```

**Schritt 4: Assets hinzufügen**

⚠️ **WICHTIG**: Jedes Theme muss eigene Assets haben!

- Laden Sie Bootstrap herunter und kopieren Sie es nach `assets/bootstrap/`
- Erstellen Sie `assets/css/style.css` für Ihre Styles
- Erstellen Sie `assets/js/main.js` für Ihre Scripts
- Fügen Sie Fonts nach `assets/fonts/` hinzu
- Fügen Sie Bilder nach `assets/images/` hinzu

**Schritt 5: Theme aktivieren**

In `config/config.php`:

```php
'theme' => [
    'active' => 'mein-theme',
],
```

#### Verfügbare Template-Variablen

In Templates stehen folgende Variablen zur Verfügung:

- `$assets`: ThemeAssetManager für Asset-URLs
- `$lang`: LanguageManager für Übersetzungen
- `$page`: Array mit Seitendaten
- `$navigation`: Array mit Navigations-Seiten
- `$footerNavigation`: Array mit Footer-Seiten
- `$siteName`: Website-Name aus Config
- `$content`: Gerendeter Inhalt (in layout.php)

#### Asset-Helper-Methoden

```php
// CSS
$assets->css('style.css')               // /themes/mein-theme/assets/css/style.css
$assets->bootstrapCss()                 // Bootstrap CSS

// JavaScript
$assets->js('main.js')                  // /themes/mein-theme/assets/js/main.js
$assets->bootstrapJs()                  // Bootstrap JS

// Bilder
$assets->image('logo.png')              // /themes/mein-theme/assets/images/logo.png

// Fonts
$assets->font('custom.woff2')           // /themes/mein-theme/assets/fonts/custom.woff2

// Beliebige Assets
$assets->asset('icons/star.svg')        // /themes/mein-theme/assets/icons/star.svg
```

### Block-Entwicklung

#### Neuen Block erstellen

**Schritt 1: Block-Klasse erstellen**

Erstellen Sie `blocks/CustomBlock.php`:

```php
<?php

namespace FCMS\Blocks\Types;

use FCMS\Blocks\AbstractBlock;

class CustomBlock extends AbstractBlock
{
    public function getMetadata(): array
    {
        return [
            'name' => 'custom',
            'title' => 'Mein Block',
            'icon' => 'custom-icon',
            'category' => 'design',
            'description' => 'Beschreibung meines Blocks',
        ];
    }

    public function getDefaultAttributes(): array
    {
        return [
            'title' => '',
            'content' => '',
            'color' => '#000000',
        ];
    }

    public function render(array $attributes, string $content = ''): string
    {
        $attrs = $this->mergeAttributes($attributes);

        $html = '<div class="custom-block" style="color: ' .
                $this->escape($attrs['color']) . '">';
        $html .= '<h3>' . $this->escape($attrs['title']) . '</h3>';
        $html .= '<p>' . $this->escape($attrs['content']) . '</p>';
        $html .= '</div>';

        return $html;
    }

    public function renderEditor(array $attributes, string $content = ''): string
    {
        $attrs = $this->mergeAttributes($attributes);

        return '<div class="block-editor custom-block">
            <div class="mb-2">
                <label class="form-label">Titel</label>
                <input type="text" class="form-control"
                       value="' . $this->escape($attrs['title']) . '"
                       data-block-attr="title">
            </div>
            <div class="mb-2">
                <label class="form-label">Inhalt</label>
                <textarea class="form-control" rows="3"
                          data-block-content>' .
                $this->escape($attrs['content']) .
                '</textarea>
            </div>
            <div class="mb-2">
                <label class="form-label">Farbe</label>
                <input type="color" class="form-control"
                       value="' . $this->escape($attrs['color']) . '"
                       data-block-attr="color">
            </div>
        </div>';
    }
}
```

**Schritt 2: Block wird automatisch erkannt**

Das Block-Registry-System erkennt neue Blöcke automatisch. Kein manuelles Registrieren nötig!

**Schritt 3: Block testen**

1. Seite im Admin-Bereich öffnen
2. "Block hinzufügen" klicken
3. Ihr neuer Block erscheint in der Liste

#### Block-Interface-Methoden

Jeder Block muss folgende Methoden implementieren:

```php
interface BlockInterface
{
    // Metadaten für Block-Palette
    public function getMetadata(): array;

    // Standard-Werte für Attribute
    public function getDefaultAttributes(): array;

    // Frontend-Rendering (für Website)
    public function render(array $attributes, string $content = ''): string;

    // Editor-Rendering (für Admin-Bereich)
    public function renderEditor(array $attributes, string $content = ''): string;

    // Validierung der Attribute
    public function validate(array $attributes): bool;
}
```

#### Best Practices für Blöcke

1. **Verwenden Sie AbstractBlock**
   - Erbt nützliche Helper-Methoden
   - Implementiert Standard-Validierung

2. **Escapen Sie Output**
   ```php
   $this->escape($userInput)
   ```

3. **Verwenden Sie data-Attribute im Editor**
   - `data-block-attr="attributName"` für Attribute
   - `data-block-content` für Hauptinhalt

4. **Frontend-Assets aus Theme**
   - Blöcke verwenden Theme-Styles
   - Keine eigenen CSS/JS-Dateien in Blöcken

5. **Editor-Assets aus Admin**
   - Editor verwendet Admin-Bootstrap
   - Admin-CSS-Klassen (form-control, btn, etc.)

### Asset-Management

#### Wichtig: Strikte Trennung!

⚠️ **Admin-Assets und Theme-Assets sind vollständig getrennt!**

**Admin-Assets** (`/admin/assets/`):
- Werden NUR im Admin-Bereich verwendet
- Eigene Bootstrap-Installation
- Admin-spezifische Styles und Scripts

**Theme-Assets** (`/themes/[theme]/assets/`):
- Werden NUR im Frontend verwendet
- Jedes Theme hat eigene Assets
- Themes teilen KEINE Assets untereinander

#### Admin-Asset-Manager

Nur im Admin-Bereich verfügbar:

```php
$adminAssets->bootstrapCss()      // Admin Bootstrap CSS
$adminAssets->css('admin.css')    // Admin-Styles
$adminAssets->js('admin.js')      // Admin-Scripts
$adminAssets->image('logo.png')   // Admin-Bilder
```

#### Theme-Asset-Manager

Nur im Frontend verfügbar:

```php
$assets->bootstrapCss()           // Theme Bootstrap CSS
$assets->css('style.css')         // Theme-Styles
$assets->js('main.js')            // Theme-Scripts
$assets->image('header.jpg')      // Theme-Bilder
```

#### Warum diese Trennung?

1. **Theme-Unabhängigkeit**: Admin funktioniert immer, egal welches Theme
2. **Keine Konflikte**: Verschiedene Bootstrap-Versionen möglich
3. **Performance**: Nur benötigte Assets werden geladen
4. **Sicherheit**: Admin-Assets nicht im Frontend exponiert
5. **Wartbarkeit**: Klare Zuständigkeiten

#### Asset-Checkliste für Entwickler

✅ **DO:**
- Jedes Theme hat eigene Bootstrap-Kopie
- Admin hat eigene Bootstrap-Kopie
- Assets über entsprechenden Manager laden
- Relative Pfade in Assets verwenden

❌ **DON'T:**
- Admin-Assets in Themes referenzieren
- Theme-Assets im Admin referenzieren
- CDN-Links verwenden (DSGVO!)
- Assets zwischen Themes teilen

---

## 🔒 DSGVO-Konformität

fCMS ist von Grund auf DSGVO-konform gestaltet:

### Frontend (Website)

✅ **Vollständig Cookie-frei**
- Keine Cookies
- Keine Tracking-Pixel
- Keine Analytics
- Keine externe Ressourcen (CDN, Google Fonts, etc.)

✅ **Keine Datenerhebung**
- Keine IP-Adressen werden gespeichert
- Keine Nutzeridentifikation
- Keine Verhaltensanalyse

✅ **Kein Cookie-Banner erforderlich**
- Da keine datenschutzrelevanten Technologien im Frontend

### Admin-Bereich

ℹ️ **Technisch notwendige Cookies**
- Session-Cookie für Login (httponly, secure, samesite=strict)
- Keine Einwilligung erforderlich (technisch notwendig)
- Optional: Minimaler Hinweis im Login

### Logs

✅ **Datenschutzkonforme Protokollierung**
- Nur technische Fehler
- Keine IP-Adressen
- Keine personenbezogenen Daten
- Automatische Löschung nach 30 Tagen

### Kontaktformulare

Falls Sie Kontaktformulare hinzufügen:
- Daten werden nur lokal gespeichert
- Kein Versand an Dritte
- Explizite Einwilligung einholen
- Datenschutzhinweis verlinken

### Empfohlene Maßnahmen

1. **Datenschutzerklärung**
   - Erstellen Sie eine Datenschutzseite
   - Erwähnen Sie Session-Cookie im Admin
   - Listen Sie eventuell eingebundene Dienste auf

2. **Impressum**
   - Erstellen Sie eine Impressums-Seite
   - Pflichtangaben nach TMG

3. **SSL/TLS**
   - Verwenden Sie HTTPS
   - Let's Encrypt ist kostenlos

---

## 🔧 Troubleshooting

### Häufige Probleme

#### "Seite nicht gefunden" (404)

**Problem**: Alle Seiten zeigen 404-Fehler

**Lösung**:
```
1. Prüfen Sie ob mod_rewrite aktiv ist
2. Prüfen Sie .htaccess-Datei in public/
3. Bei Nginx: Entsprechende Rewrite-Rules konfigurieren
```

#### Admin-Login funktioniert nicht

**Problem**: "Ungültiger Benutzername oder Passwort"

**Lösung**:
```
1. Prüfen Sie Benutzername in config/config.php
2. Generieren Sie neuen Passwort-Hash
3. Prüfen Sie ob Sessions funktionieren (session_start-Fehler?)
```

#### "Permission denied" Fehler

**Problem**: Kann keine Seiten speichern

**Lösung**:
```bash
chmod 755 content/
chmod 755 content/pages/
chmod 755 content/media/
chmod 755 logs/
```

#### Theme wird nicht geladen

**Problem**: Seite hat keine Styles

**Lösung**:
```
1. Prüfen Sie Theme-Name in config/config.php
2. Prüfen Sie ob Theme-Ordner existiert: themes/[theme-name]/
3. Prüfen Sie ob assets/bootstrap/ im Theme vorhanden ist
4. Prüfen Sie Browser-Console auf 404-Fehler
```

#### Build schlägt fehl

**Problem**: `composer build` bricht mit Fehler ab

**Lösung**:
```bash
# Composer cache leeren
composer clear-cache

# Dependencies neu installieren
rm -rf vendor/
composer install

# Build erneut versuchen
composer build
```

#### Assets nicht gefunden (404)

**Problem**: CSS/JS-Dateien werden nicht geladen

**Lösung**:
```
1. Admin-Assets: Prüfen Sie /admin/assets/
2. Theme-Assets: Prüfen Sie /themes/[theme]/assets/
3. Prüfen Sie ob Bootstrap-Dateien vorhanden sind
4. Prüfen Sie Pfade in Templates
```

### Debug-Modus aktivieren

In `config/config.php`:

```php
'debug' => [
    'enabled' => true,
    'display_errors' => true,
    'log_errors' => true,
],
```

⚠️ **Achtung**: Nur in Entwicklung aktivieren!

### Logs prüfen

```bash
tail -f logs/error.log
```

---

## 📞 Support

### Dokumentation

- Vollständige Dokumentation: Dieses README
- Inline-Code-Kommentare
- Beispiel-Implementierungen in default-Theme und Standard-Blöcken

### Community

- GitHub Issues: [Issues melden](https://github.com/IhrRepo/fCMS/issues)
- Discussions: [Fragen stellen](https://github.com/IhrRepo/fCMS/discussions)

### Updates & Sicherheit

Halten Sie fCMS aktuell:
- Prüfen Sie regelmäßig auf Updates
- Sicherheitsupdates werden priorisiert
- Breaking Changes werden dokumentiert

---

## 📄 Lizenz

MIT License - Siehe LICENSE-Datei für Details

---

## 🙏 Danksagungen

fCMS verwendet folgende Open-Source-Projekte:

- Slim Framework
- Bootstrap
- Symfony Components
- PHP-DI
- Und viele weitere...

---

**Entwickelt mit ❤️ für kleine Organisationen und Fördervereine**

Version 1.0.0 | [GitHub](https://github.com/IhrRepo/fCMS) | [Website](https://fcms.example.com)
