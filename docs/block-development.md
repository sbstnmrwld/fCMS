# Block-Entwicklung

Erstellen Sie eigene Blöcke für den fCMS-Block-Editor.

## Block-Konzept

Blöcke sind wiederverwendbare Content-Komponenten:

- **Modularer Aufbau**: Jeder Block-Typ in eigener Klasse
- **Frontend-Rendering**: HTML-Ausgabe für Website
- **Editor-Integration**: Bearbeitungs-Interface im Admin
- **Validierung**: Eingabe-Validierung und Sanitization

## Block-Interface

Alle Blöcke implementieren `BlockInterface`:

```php
interface BlockInterface
{
    // Metadaten für Block-Palette
    public function getMetadata(): array;

    // Standard-Attribute für neue Blöcke
    public function getDefaultAttributes(): array;

    // Frontend-Rendering (für Website)
    public function render(array $attributes, string $content = ''): string;

    // Validierung der Attribute
    public function validate(array $attributes): bool;
}
```

## Ersten Block erstellen

### Schritt 1: Block-Klasse erstellen

**blocks/CalloutBlock.php**:

```php
<?php

namespace FCMS\\Blocks;

class CalloutBlock extends AbstractBlock implements BlockInterface
{
    /**
     * Block-Metadaten für Admin-Interface
     */
    public function getMetadata(): array
    {
        return [
            'name' => 'Hinweis-Box',
            'type' => 'callout',
            'category' => 'design',
            'icon' => 'bi-info-circle',
            'description' => 'Hervorgehobene Hinweis-Box mit verschiedenen Stilen',
            'keywords' => ['box', 'hinweis', 'info', 'warnung'],
        ];
    }

    /**
     * Standard-Attribute für neue Blöcke
     */
    public function getDefaultAttributes(): array
    {
        return [
            'title' => '',
            'content' => '',
            'type' => 'info',      // info, warning, success, danger
            'icon' => 'bi-info-circle',
        ];
    }

    /**
     * Frontend-Rendering
     */
    public function render(array $attributes, string $content = ''): string
    {
        $attrs = $this->mergeAttributes($attributes);

        $type = $this->escape($attrs['type']);
        $title = $this->escape($attrs['title']);
        $content = $this->escape($attrs['content']);
        $icon = $this->escape($attrs['icon']);

        // CSS-Klassen basierend auf Typ
        $classes = [
            'info' => 'alert-info',
            'warning' => 'alert-warning',
            'success' => 'alert-success',
            'danger' => 'alert-danger',
        ];

        $cssClass = $classes[$type] ?? 'alert-info';

        $html = '<div class="alert ' . $cssClass . ' d-flex align-items-start">';

        if (!empty($icon)) {
            $html .= '<i class="' . $icon . ' me-3 mt-1"></i>';
        }

        $html .= '<div>';

        if (!empty($title)) {
            $html .= '<h5 class="alert-heading">' . $title . '</h5>';
        }

        if (!empty($content)) {
            $html .= '<p class="mb-0">' . nl2br($content) . '</p>';
        }

        $html .= '</div></div>';

        return $html;
    }

    /**
     * Validierung
     */
    public function validate(array $attributes): bool
    {
        $allowedTypes = ['info', 'warning', 'success', 'danger'];
        $type = $attributes['type'] ?? 'info';

        return in_array($type, $allowedTypes);
    }
}
```

### Schritt 2: Block wird automatisch erkannt

Das Block-Registry-System erkennt neue Blöcke automatisch. Kein manuelles Registrieren nötig!

### Schritt 3: Editor-Integration (optional)

Für erweiterte Editor-Features erstellen Sie JavaScript:

**public/admin/assets/js/blocks/callout-block.js**:

```javascript
// Erweiterte Editor-Funktionen für Callout-Block
document.addEventListener('DOMContentLoaded', function() {
    // Custom Editor-Logik falls benötigt

    // Icon-Picker
    document.addEventListener('change', function(e) {
        if (e.target.matches('.callout-icon-select')) {
            const icon = e.target.value;
            const preview = e.target.parentNode.querySelector('.icon-preview');
            if (preview) {
                preview.className = 'icon-preview ' + icon;
            }
        }
    });
});
```

## AbstractBlock verwenden

Die `AbstractBlock`-Klasse bietet nützliche Helper-Methoden:

```php
abstract class AbstractBlock implements BlockInterface
{
    // Attribute mit Standards zusammenführen
    protected function mergeAttributes(array $attributes): array;

    // HTML-Escaping
    protected function escape(string $string): string;

    // Standard-Validierung
    public function validate(array $attributes): bool;
}
```

### Beispiel-Implementierung:

```php
class MeinBlock extends AbstractBlock
{
    public function render(array $attributes, string $content = ''): string
    {
        // Attribute mit Standards mergen
        $attrs = $this->mergeAttributes($attributes);

        // Sicher escapen
        $title = $this->escape($attrs['title']);

        return "<h3>{$title}</h3>";
    }
}
```

## Erweiterte Blöcke

### Block mit Bild-Upload

```php
class HeroBlock extends AbstractBlock
{
    public function getDefaultAttributes(): array
    {
        return [
            'title' => '',
            'subtitle' => '',
            'image' => '',
            'buttonText' => '',
            'buttonUrl' => '',
        ];
    }

    public function render(array $attributes, string $content = ''): string
    {
        $attrs = $this->mergeAttributes($attributes);

        $html = '<div class="hero-section">';

        if (!empty($attrs['image'])) {
            $html .= '<div class="hero-image">';
            $html .= '<img src="' . $this->escape($attrs['image']) . '" alt="Hero Image" class="img-fluid">';
            $html .= '</div>';
        }

        $html .= '<div class="hero-content">';

        if (!empty($attrs['title'])) {
            $html .= '<h1>' . $this->escape($attrs['title']) . '</h1>';
        }

        if (!empty($attrs['subtitle'])) {
            $html .= '<p class="lead">' . $this->escape($attrs['subtitle']) . '</p>';
        }

        if (!empty($attrs['buttonText']) && !empty($attrs['buttonUrl'])) {
            $html .= '<a href="' . $this->escape($attrs['buttonUrl']) . '" class="btn btn-primary">';
            $html .= $this->escape($attrs['buttonText']);
            $html .= '</a>';
        }

        $html .= '</div></div>';

        return $html;
    }
}
```

### Block mit wiederholbaren Elementen

```php
class TestimonialBlock extends AbstractBlock
{
    public function getDefaultAttributes(): array
    {
        return [
            'testimonials' => [
                [
                    'text' => '',
                    'author' => '',
                    'position' => '',
                    'image' => ''
                ]
            ]
        ];
    }

    public function render(array $attributes, string $content = ''): string
    {
        $attrs = $this->mergeAttributes($attributes);
        $testimonials = $attrs['testimonials'] ?? [];

        if (empty($testimonials)) {
            return '';
        }

        $html = '<div class="testimonials row">';

        foreach ($testimonials as $testimonial) {
            $html .= '<div class="col-md-4 mb-4">';
            $html .= '<div class="testimonial card h-100">';
            $html .= '<div class="card-body">';

            if (!empty($testimonial['text'])) {
                $html .= '<blockquote class="blockquote">';
                $html .= '<p>"' . $this->escape($testimonial['text']) . '"</p>';
                $html .= '</blockquote>';
            }

            if (!empty($testimonial['author'])) {
                $html .= '<footer class="blockquote-footer">';
                $html .= $this->escape($testimonial['author']);

                if (!empty($testimonial['position'])) {
                    $html .= ', <cite>' . $this->escape($testimonial['position']) . '</cite>';
                }

                $html .= '</footer>';
            }

            $html .= '</div></div></div>';
        }

        $html .= '</div>';

        return $html;
    }
}
```

## Block-Kategorien

Organisieren Sie Blöcke in Kategorien:

```php
public function getMetadata(): array
{
    return [
        'name' => 'Mein Block',
        'category' => 'design',  // text, media, design, layout, interactive
        // ...
    ];
}
```

**Standard-Kategorien**:
- `text` - Text-Blöcke (Absatz, Überschrift, etc.)
- `media` - Medien-Blöcke (Bild, Video, etc.)
- `design` - Design-Elemente (Callout, Hero, etc.)
- `layout` - Layout-Blöcke (Spalten, Spacer, etc.)
- `interactive` - Interaktive Elemente (Button, Formular, etc.)

## Block-Validierung

### Basis-Validierung

```php
public function validate(array $attributes): bool
{
    // Erforderliche Felder prüfen
    if (empty($attributes['title'])) {
        return false;
    }

    // URL-Validierung
    if (!empty($attributes['url']) && !filter_var($attributes['url'], FILTER_VALIDATE_URL)) {
        return false;
    }

    // Enum-Werte prüfen
    $allowedTypes = ['primary', 'secondary', 'success'];
    if (!in_array($attributes['type'] ?? '', $allowedTypes)) {
        return false;
    }

    return true;
}
```

### Erweiterte Validierung

```php
class FormBlock extends AbstractBlock
{
    public function validate(array $attributes): bool
    {
        $formId = $attributes['formId'] ?? '';

        // Prüfe ob Formular existiert
        $formPath = __DIR__ . '/../../content/forms/' . basename($formId) . '.json';

        if (!empty($formId) && !file_exists($formPath)) {
            return false;
        }

        return true;
    }
}
```

## CSS für Blöcke

### Theme-Integration

Blöcke verwenden Theme-CSS. Fügen Sie Styles zu `themes/[theme]/assets/css/style.css` hinzu:

```css
/* Callout-Block Styles */
.alert {
    border: none;
    border-left: 4px solid;
}

.alert-info {
    border-left-color: #0dcaf0;
    background-color: #d1ecf1;
    color: #055160;
}

.alert-warning {
    border-left-color: #ffc107;
    background-color: #fff3cd;
    color: #664d03;
}

.alert-success {
    border-left-color: #198754;
    background-color: #d1e7dd;
    color: #0f5132;
}

.alert-danger {
    border-left-color: #dc3545;
    background-color: #f8d7da;
    color: #721c24;
}

/* Hero-Block Styles */
.hero-section {
    position: relative;
    padding: 4rem 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-image {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: -1;
}

.hero-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-content {
    position: relative;
    z-index: 1;
}

/* Testimonial-Block Styles */
.testimonial {
    transition: transform 0.3s ease;
}

.testimonial:hover {
    transform: translateY(-5px);
}

.testimonial blockquote {
    margin-bottom: 1rem;
}
```

## Block-Testing

### Unit-Tests

```php
// tests/Unit/Blocks/CalloutBlockTest.php
use PHPUnit\\Framework\\TestCase;
use FCMS\\Blocks\\CalloutBlock;

class CalloutBlockTest extends TestCase
{
    private CalloutBlock $block;

    protected function setUp(): void
    {
        $this->block = new CalloutBlock();
    }

    public function testRender(): void
    {
        $attributes = [
            'title' => 'Test Title',
            'content' => 'Test Content',
            'type' => 'info'
        ];

        $html = $this->block->render($attributes);

        $this->assertStringContainsString('Test Title', $html);
        $this->assertStringContainsString('Test Content', $html);
        $this->assertStringContainsString('alert-info', $html);
    }

    public function testValidation(): void
    {
        $validAttributes = ['type' => 'info'];
        $invalidAttributes = ['type' => 'invalid'];

        $this->assertTrue($this->block->validate($validAttributes));
        $this->assertFalse($this->block->validate($invalidAttributes));
    }
}
```

### Manuelles Testing

1. **Block erstellen**: Fügen Sie Block-Datei zu `blocks/` hinzu
2. **Admin testen**: Gehen Sie zu "Blöcke" im Admin
3. **Editor testen**: Erstellen Sie Seite und fügen Block hinzu
4. **Frontend testen**: Besuchen Sie die Seite im Frontend

## Best Practices

### ✅ DO

- Verwenden Sie `AbstractBlock` als Basis
- Escapen Sie alle Ausgaben mit `$this->escape()`
- Verwenden Sie aussagekräftige Block-Namen
- Dokumentieren Sie komplexe Attribute
- Validieren Sie alle Eingaben

```php
public function render(array $attributes, string $content = ''): string
{
    $attrs = $this->mergeAttributes($attributes);

    return '<h2>' . $this->escape($attrs['title']) . '</h2>';
}
```

### ❌ DON'T

- Keine unescapten Ausgaben
- Keine direkten `$_GET`/`$_POST` Zugriffe in Blöcken
- Keine externen API-Calls im render() (Performance!)
- Keine Theme-spezifischen CSS-Klassen hardcoden

```php
// ❌ FALSCH
public function render(array $attributes): string
{
    return '<h2>' . $attributes['title'] . '</h2>'; // Nicht escaped!
}
```

## Debugging

### Debug-Ausgaben

```php
public function render(array $attributes, string $content = ''): string
{
    // Debug nur in Entwicklung
    if ($_ENV['APP_DEBUG'] ?? false) {
        error_log('Block attributes: ' . json_encode($attributes));
    }

    // ...
}
```

### Block-Fehler finden

1. **Admin-Block-Übersicht**: Gehen Sie zu "Blöcke" - fehlende Blöcke werden angezeigt
2. **Browser-Console**: JavaScript-Fehler bei Editor-Problemen
3. **Error-Logs**: `logs/error.log` für PHP-Fehler

## Weiterführend

- [Modul-Entwicklung](module-development.md) - Blöcke in Modulen erstellen
- [Theme-Entwicklung](theme-development.md) - Block-CSS in Themes
- [Architektur](architecture.md) - Block-System verstehen

## Checkliste

- [ ] Block-Klasse in `blocks/` erstellt
- [ ] `BlockInterface` implementiert
- [ ] Metadaten definiert
- [ ] Standard-Attribute festgelegt
- [ ] Render-Methode implementiert
- [ ] Validierung hinzugefügt
- [ ] Alle Ausgaben escaped
- [ ] CSS in Theme hinzugefügt
- [ ] Block getestet (Admin + Frontend)
