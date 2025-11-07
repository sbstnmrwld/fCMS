# Theme-Entwicklung

Erstellen Sie eigene Themes für fCMS mit vollständiger Design-Kontrolle.

## Theme-Struktur

```
themes/
└── mein-theme/
    ├── theme.json          # Metadaten (erforderlich)
    ├── assets/             # Assets (erforderlich)
    │   ├── bootstrap/      # Optional: Bootstrap-Dateien
    │   │   ├── css/
    │   │   └── js/
    │   ├── css/            # Erforderlich: Theme-CSS
    │   │   └── style.css
    │   ├── js/             # Optional: Theme-JavaScript
    │   │   └── main.js
    │   ├── fonts/          # Optional: Eigene Fonts
    │   └── images/         # Optional: Theme-Bilder
    ├── templates/          # Templates (erforderlich)
    │   ├── layout.php      # Haupt-Layout
    │   └── page.php        # Seiten-Template
    └── README.md           # Dokumentation (optional)
```

## Neues Theme erstellen

### Schritt 1: Theme-Ordner erstellen

```bash
mkdir themes/mein-theme
cd themes/mein-theme
```

### Schritt 2: theme.json erstellen

```json
{
  "name": "Mein Theme",
  "version": "1.0.0",
  "description": "Beschreibung meines Themes",
  "author": "Ihr Name",
  "screenshot": "screenshot.png",
  "supports": {
    "navigation": true,
    "footer": true,
    "custom-logo": true
  }
}
```

### Schritt 3: Assets vorbereiten

**CSS-Framework (optional):**

Themes können beliebige CSS-Frameworks verwenden - oder gar keines:

**Option A: Bootstrap verwenden** (wie Default-Theme)
```bash
# Bootstrap herunterladen von https://getbootstrap.com/
# Dann kopieren:
mkdir -p assets/bootstrap
cp -r bootstrap/css/ assets/bootstrap/css/
cp -r bootstrap/js/ assets/bootstrap/js/
```

**Option B: Eigenes CSS**
```bash
# Nur eigene CSS-Datei erstellen
touch assets/css/style.css
```

**Option C: Anderes Framework** (Tailwind, Foundation, etc.)
```bash
# Entsprechende Dateien in assets/ kopieren
```

### Schritt 4: Template-Struktur erstellen

**layout.php** - Haupt-Layout:

```php
<!DOCTYPE html>
<html lang="<?= $lang->getCurrentLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page['title'] ?? 'Home') ?> - <?= htmlspecialchars($siteName) ?></title>

    <!-- Meta Tags -->
    <meta name="description" content="<?= htmlspecialchars($page['description'] ?? '') ?>">

    <!-- CSS Framework (optional - hier Bootstrap als Beispiel) -->
    <link href="<?= $assets->bootstrapCss() ?>" rel="stylesheet">

    <!-- Theme CSS (erforderlich) -->
    <link href="<?= $assets->css('style.css') ?>" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="/"><?= htmlspecialchars($siteName) ?></a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php foreach ($navigation as $navItem): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= htmlspecialchars($navItem['url']) ?>">
                                <?= htmlspecialchars($navItem['label']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hauptinhalt -->
    <main>
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?></p>
                </div>
                <div class="col-md-6">
                    <ul class="list-inline mb-0 text-end">
                        <?php foreach ($footerNavigation as $footerItem): ?>
                            <li class="list-inline-item">
                                <a href="<?= htmlspecialchars($footerItem['url']) ?>" class="text-light">
                                    <?= htmlspecialchars($footerItem['label']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- CSS Framework JS (optional - hier Bootstrap als Beispiel) -->
    <script src="<?= $assets->bootstrapJs() ?>"></script>

    <!-- Theme JS -->
    <script src="<?= $assets->js('main.js') ?>"></script>
</body>
</html>
```

**page.php** - Seiten-Template:

```php
<div class="container my-5">
    <article>
        <header class="mb-4">
            <h1><?= htmlspecialchars($page['title']) ?></h1>

            <?php if (!empty($page['description'])): ?>
                <p class="lead text-muted"><?= htmlspecialchars($page['description']) ?></p>
            <?php endif; ?>
        </header>

        <div class="content">
            <?php foreach ($page['sections'] as $section): ?>
                <?= $section['html'] ?>
            <?php endforeach; ?>
        </div>
    </article>
</div>
```

### Schritt 5: CSS erstellen

**assets/css/style.css**:

```css
/* Theme-spezifische Styles */
:root {
    --primary-color: #007bff;
    --secondary-color: #6c757d;
    --success-color: #28a745;
    --danger-color: #dc3545;
}

/* Navigation */
.navbar-brand {
    font-weight: bold;
}

/* Content */
.content h2 {
    color: var(--primary-color);
    margin-top: 2rem;
}

.content blockquote {
    border-left: 4px solid var(--primary-color);
    padding-left: 1rem;
    margin: 1rem 0;
    font-style: italic;
}

/* Buttons */
.btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

/* Footer */
footer a {
    text-decoration: none;
}

footer a:hover {
    text-decoration: underline;
}

/* Responsive Images */
.content img {
    max-width: 100%;
    height: auto;
    border-radius: 0.25rem;
}

/* Block-spezifische Styles */
.content .block-quote {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.5rem;
    margin: 1.5rem 0;
}

.content .block-button {
    margin: 1rem 0;
}

.content .form-container {
    background-color: #f8f9fa;
    padding: 2rem;
    border-radius: 0.5rem;
    margin: 2rem 0;
}
```

### Schritt 6: JavaScript erstellen

**assets/js/main.js**:

```javascript
// Theme-spezifische JavaScript-Funktionalität

document.addEventListener('DOMContentLoaded', function() {
    // Smooth Scrolling für Anker-Links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Lazy Loading für Bilder
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // Form-Verbesserungen
    document.querySelectorAll('.form-container form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Wird gesendet...';
                submitBtn.disabled = true;
            }
        });
    });
});
```

## Template-Variablen

In Templates stehen folgende Variablen zur Verfügung:

### Globale Variablen

```php
$assets          // ThemeAssetManager - für Asset-URLs
$lang            // LanguageManager - für Übersetzungen
$siteName        // Website-Name aus Konfiguration
$content         // Gerendeter Seiten-Inhalt (nur in layout.php)
```

### Navigation

```php
$navigation      // Array mit Hauptnavigation
$footerNavigation // Array mit Footer-Navigation

// Struktur der Navigation-Items:
[
    [
        'url' => '/about',
        'label' => 'Über uns',
        'active' => false
    ],
    // ...
]
```

### Seiten-Daten

```php
$page            // Array mit Seitendaten

// Struktur:
[
    'title' => 'Seitentitel',
    'slug' => 'seiten-url',
    'description' => 'Meta-Beschreibung',
    'status' => 'published',
    'sections' => [
        [
            'type' => 'paragraph',
            'data' => ['text' => 'Inhalt...'],
            'html' => '<p>Gerenderte HTML...</p>'
        ],
        // ...
    ]
]
```

## Asset-Helper

### CSS & JavaScript

```php
// CSS
<?= $assets->bootstrapCss() ?>         // Bootstrap CSS (optional)
<?= $assets->css('style.css') ?>       // Theme CSS (erforderlich)
<?= $assets->css('custom.css') ?>      // Zusätzliche CSS-Datei

// JavaScript
<?= $assets->bootstrapJs() ?>          // Bootstrap JS (optional)
<?= $assets->js('main.js') ?>          // Theme JS
<?= $assets->js('contact.js') ?>       // Seitenspezifisches JS
```

### Bilder & Assets

```php
// Bilder
<?= $assets->image('logo.png') ?>      // /themes/mein-theme/assets/images/logo.png
<?= $assets->image('bg/header.jpg') ?> // Unterordner möglich

// Fonts
<?= $assets->font('custom.woff2') ?>   // /themes/mein-theme/assets/fonts/custom.woff2

// Beliebige Assets
<?= $assets->asset('icons/star.svg') ?> // /themes/mein-theme/assets/icons/star.svg
```

## Theme aktivieren

### In Konfiguration

**config/config.php**:

```php
'theme' => [
    'active' => 'mein-theme',
],
```

### Über Admin-Interface

1. Gehen Sie zu **"Einstellungen"** → **"Theme"**
2. Wählen Sie Ihr Theme aus der Liste
3. Klicken Sie **"Speichern"**

## Erweiterte Features

### Custom Post Types (optional)

```php
// In templates/custom-page.php
if ($page['type'] === 'portfolio') {
    // Spezielle Portfolio-Darstellung
}
```

### Theme-Optionen

**theme.json**:

```json
{
  "name": "Mein Theme",
  "options": {
    "primary_color": "#007bff",
    "enable_dark_mode": true,
    "header_style": "fixed"
  }
}
```

**In Templates**:

```php
$themeOptions = $assets->getThemeOptions();
$primaryColor = $themeOptions['primary_color'] ?? '#007bff';
```

### Mehrsprachigkeit

```php
// In Templates
<?= $lang->get('common.read_more') ?>
<?= $lang->get('navigation.home') ?>
```

## Best Practices

### ✅ DO

- Verwenden Sie Asset-Manager für alle URLs
- Verwenden Sie CSS-Framework Ihrer Wahl (Bootstrap, Tailwind, oder eigenes)
- Escapen Sie alle User-Eingaben mit `htmlspecialchars()`
- Verwenden Sie semantische HTML-Elemente
- Optimieren Sie Bilder und Assets

```php
<!-- ✅ RICHTIG -->
<img src="<?= $assets->image('hero.jpg') ?>" alt="<?= htmlspecialchars($page['title']) ?>">
<h1><?= htmlspecialchars($page['title']) ?></h1>
```

### ❌ DON'T

- Keine absoluten Asset-Pfade verwenden
- Keine externen CDNs (DSGVO!)
- Keine unescapten User-Eingaben ausgeben
- Keine Theme-Assets zwischen Themes teilen

```php
<!-- ❌ FALSCH -->
<img src="/themes/mein-theme/assets/images/hero.jpg">
<h1><?= $page['title'] ?></h1>
```

## Debugging

### Theme-Fehler finden

**Aktivieren Sie Debug-Modus** in `config/config.php`:

```php
'debug' => [
    'enabled' => true,
    'display_errors' => true,
],
```

**Browser-Console prüfen**:
- Fehlende Assets (404)
- JavaScript-Fehler
- CSS-Probleme

**Template-Debugging**:

```php
<?php
// Variablen ausgeben
var_dump($page);
var_dump($navigation);
?>
```

## Checkliste

- [ ] Theme-Ordner erstellt
- [ ] `theme.json` mit Metadaten
- [ ] CSS-Framework gewählt und installiert (optional)
- [ ] `layout.php` und `page.php` erstellt
- [ ] CSS und JS angelegt
- [ ] Asset-Manager verwendet
- [ ] Alle Ausgaben escaped
- [ ] Theme in Config aktiviert
- [ ] Im Browser getestet

## Weiterführend

- [Asset-Management](asset-management.md) - Assets richtig organisieren
- [Block-Entwicklung](block-development.md) - Custom Styling für Blöcke
- [Entwicklungs-Setup](development-setup.md) - Bootstrap installieren
