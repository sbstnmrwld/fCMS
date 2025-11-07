# Asset-Management

Verstehen Sie, wie Assets in fCMS organisiert und geladen werden.

## Konzept: Strikte Trennung

⚠️ **Wichtig**: Admin-Assets und Theme-Assets sind vollständig getrennt!

```
public/
├── admin/assets/        # Nur für Admin-Bereich
└── themes/
    └── [theme]/assets/  # Nur für Frontend
```

## Warum diese Trennung?

1. **Theme-Unabhängigkeit**: Admin funktioniert unabhängig vom Theme
2. **Keine Konflikte**: Verschiedene Bootstrap-Versionen möglich
3. **Performance**: Nur benötigte Assets werden geladen
4. **Sicherheit**: Admin-Assets nicht im Frontend exponiert
5. **Wartbarkeit**: Klare Zuständigkeiten

## Asset-Struktur

### Admin-Assets

```
public/admin/assets/
├── bootstrap/
│   ├── css/
│   │   ├── bootstrap.min.css
│   │   └── bootstrap.min.css.map
│   └── js/
│       ├── bootstrap.bundle.min.js
│       └── bootstrap.bundle.min.js.map
├── bootstrap-icons/
│   ├── bootstrap-icons.css
│   └── fonts/
├── css/
│   └── admin.css
├── js/
│   ├── admin.js
│   └── block-editor.js
├── fonts/
└── images/
```

### Theme-Assets

```
public/themes/default/assets/
├── bootstrap/
│   ├── css/
│   │   ├── bootstrap.min.css
│   │   └── bootstrap.min.css.map
│   └── js/
│       ├── bootstrap.bundle.min.js
│       └── bootstrap.bundle.min.js.map
├── css/
│   └── style.css
├── js/
│   └── main.js
├── fonts/
└── images/
```

## Admin Asset Manager

Verwendung in Admin-Templates:

```php
<?php
// In admin/templates/layout.php
?>

<!-- Bootstrap CSS -->
<link href="<?= $adminAssets->bootstrapCss() ?>" rel="stylesheet">

<!-- Bootstrap Icons -->
<link href="<?= $adminAssets->bootstrapIcons() ?>" rel="stylesheet">

<!-- Admin CSS -->
<link href="<?= $adminAssets->css('admin.css') ?>" rel="stylesheet">

<!-- Bootstrap JS -->
<script src="<?= $adminAssets->bootstrapJs() ?>"></script>

<!-- Admin JS -->
<script src="<?= $adminAssets->js('admin.js') ?>"></script>
```

### Verfügbare Methoden

```php
// CSS
$adminAssets->bootstrapCss()           // Bootstrap CSS
$adminAssets->bootstrapIcons()         // Bootstrap Icons CSS
$adminAssets->css('admin.css')         // Custom CSS

// JavaScript
$adminAssets->bootstrapJs()            // Bootstrap JS
$adminAssets->js('admin.js')           // Custom JS
$adminAssets->js('block-editor.js')    // Block-Editor

// Bilder
$adminAssets->image('logo.png')        // /admin/assets/images/logo.png

// Beliebige Assets
$adminAssets->asset('icons/star.svg')  // /admin/assets/icons/star.svg
```

## Theme Asset Manager

Verwendung in Theme-Templates:

```php
<?php
// In themes/default/templates/layout.php
?>

<!-- Bootstrap CSS -->
<link href="<?= $assets->bootstrapCss() ?>" rel="stylesheet">

<!-- Theme CSS -->
<link href="<?= $assets->css('style.css') ?>" rel="stylesheet">

<!-- Bootstrap JS -->
<script src="<?= $assets->bootstrapJs() ?>"></script>

<!-- Theme JS -->
<script src="<?= $assets->js('main.js') ?>"></script>
```

### Verfügbare Methoden

```php
// CSS
$assets->bootstrapCss()            // Bootstrap CSS
$assets->css('style.css')          // Theme CSS

// JavaScript
$assets->bootstrapJs()             // Bootstrap JS
$assets->js('main.js')             // Theme JS

// Bilder
$assets->image('header.jpg')       // /themes/[theme]/assets/images/header.jpg

// Fonts
$assets->font('custom.woff2')      // /themes/[theme]/assets/fonts/custom.woff2

// Beliebige Assets
$assets->asset('icons/logo.svg')   // /themes/[theme]/assets/icons/logo.svg
```

## Cache-Busting

Assets haben automatisch Versions-Parameter:

```html
<link href="/admin/assets/css/admin.css?v=1699384800" rel="stylesheet">
```

**Mechanismus**:
- Basierend auf `filemtime()` der Datei
- Ändert sich bei jeder Datei-Änderung
- Browser lädt automatisch neue Version

**Implementierung**:
```php
public function css(string $file): string
{
    $path = '/admin/assets/css/' . $file;
    $fullPath = __DIR__ . '/../../public' . $path;

    $version = file_exists($fullPath) ? filemtime($fullPath) : time();

    return $path . '?v=' . $version;
}
```

## Bootstrap-Setup

### Warum nicht im Repository?

1. **Lizenz-Compliance**: Bootstrap hat eigene Lizenz
2. **Größe**: Würde Repository aufblähen
3. **Versionskontrolle**: Sie wählen Bootstrap-Version
4. **Aktualität**: Immer neueste Version möglich

### Bootstrap installieren

**Option 1: Setup-Script** (empfohlen)

```bash
./scripts/setup-bootstrap.sh
```

**Option 2: Manuell**

1. Download: https://getbootstrap.com/
2. Entpacken und kopieren:

```bash
# Admin
cp -r bootstrap/css/ public/admin/assets/bootstrap/css/
cp -r bootstrap/js/ public/admin/assets/bootstrap/js/

# Default-Theme
cp -r bootstrap/css/ public/themes/default/assets/bootstrap/css/
cp -r bootstrap/js/ public/themes/default/assets/bootstrap/js/
```

**Option 3: CDN** (nur Entwicklung!)

```php
// ⚠️ Nicht DSGVO-konform für Produktion!
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
```

## Modul-Assets

Module können eigene Assets registrieren:

```php
// In MeinModul.php
public function getAdminAssets(): array
{
    return [
        'css' => [
            '/modules/mein-modul/assets/css/style.css'
        ],
        'js' => [
            '/modules/mein-modul/assets/js/script.js'
        ]
    ];
}
```

**Automatisches Laden**:
- Admin-Template lädt alle Modul-Assets
- Pfade müssen absolut vom Document-Root sein
- Assets werden nach Admin-Assets geladen

## Pfad-Konventionen

### Admin-Assets

```
/admin/assets/[type]/[file]
```

Beispiele:
- `/admin/assets/css/admin.css`
- `/admin/assets/js/block-editor.js`
- `/admin/assets/images/logo.png`

### Theme-Assets

```
/themes/[theme-name]/assets/[type]/[file]
```

Beispiele:
- `/themes/default/assets/css/style.css`
- `/themes/custom/assets/js/main.js`
- `/themes/default/assets/images/bg.jpg`

### Modul-Assets

```
/modules/[modul-name]/assets/[type]/[file]
```

Beispiele:
- `/modules/form-builder/assets/css/forms.css`
- `/modules/form-builder/assets/js/form-editor.js`

## Best Practices

### ✅ DO

- Verwenden Sie Asset-Manager-Methoden
- Jedes Theme hat eigene Bootstrap-Kopie
- Admin hat eigene Bootstrap-Kopie
- Relative Pfade in CSS verwenden
- Cache-Busting nutzen

```css
/* In admin.css oder style.css */
background-image: url('../images/bg.jpg');  /* ✅ Relativ */
```

### ❌ DON'T

- Admin-Assets in Themes referenzieren
- Theme-Assets im Admin referenzieren
- CDN-Links in Produktion (DSGVO!)
- Assets zwischen Themes teilen
- Absolute Pfade in CSS

```css
/* ❌ FALSCH */
background-image: url('/admin/assets/images/bg.jpg');  /* Absolut */
```

## Entwicklung

### Assets live bearbeiten

Assets liegen direkt in `public/`:
- Änderungen sofort sichtbar
- Kein Build-Step erforderlich
- Hard-Refresh (Strg+Shift+R) wenn nötig

### Assets minifizieren (Produktion)

**CSS**:
```bash
# Mit npm/yarn
npm install -g clean-css-cli
cleancss -o admin.min.css admin.css
```

**JavaScript**:
```bash
# Mit npm/yarn
npm install -g uglify-js
uglifyjs admin.js -o admin.min.js
```

**Oder**: Online-Tools wie https://www.minifier.org/

## Checkliste

- [ ] Bootstrap in `/public/admin/assets/bootstrap/` installiert
- [ ] Bootstrap in `/public/themes/[theme]/assets/bootstrap/` installiert
- [ ] Asset-Manager in Templates verwenden
- [ ] Keine CDN-Links in Produktion
- [ ] Cache-Busting aktiviert
- [ ] Assets minifiziert (Produktion)

## Troubleshooting

### Assets nicht gefunden (404)

1. Prüfen Sie Pfad:
   ```bash
   ls -la public/admin/assets/css/admin.css
   ```

2. Prüfen Sie Asset-Manager-Aufruf:
   ```php
   <?= $adminAssets->css('admin.css') ?>
   ```

3. Prüfen Sie Berechtigungen:
   ```bash
   chmod 755 public/admin/assets/
   ```

### Bootstrap fehlt

```bash
# Prüfen
ls -la public/admin/assets/bootstrap/

# Installieren
./scripts/setup-bootstrap.sh
```

### Cache-Probleme

```bash
# Hard-Refresh im Browser
Strg + Shift + R (Windows/Linux)
Cmd + Shift + R (Mac)

# Oder Cache leeren
Strg + Shift + Del
```

## Weiterführend

- [Theme-Entwicklung](theme-development.md) - Theme-Assets organisieren
- [Modul-Entwicklung](module-development.md) - Modul-Assets registrieren
- [Entwicklungs-Setup](development-setup.md) - Bootstrap installieren
