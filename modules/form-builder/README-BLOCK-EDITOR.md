# Form Block Editor Integration

## Übersicht

Das FormBuilder-Modul ist vollständig unabhängig vom Core-System. Die Integration in den Block-Editor erfolgt über ein **Registry-System** für Custom-Renderer.

## Architektur

### 1. Core Block-Editor (`public/admin/assets/js/block-editor.js`)

Der Block-Editor kennt nur Core-Blöcke (paragraph, heading, image, quote, list). Für unbekannte Block-Typen prüft er, ob ein Custom-Renderer registriert ist:

```javascript
default:
    // Für unbekannte/Module-Blöcke: Prüfe ob ein Custom-Renderer registriert ist
    if (window.blockEditorRenderers && window.blockEditorRenderers[block.type]) {
        return window.blockEditorRenderers[block.type](block, this);
    }
    return '<p class="text-muted">Kein Editor für diesen Block-Typ verfügbar</p>';
```

### 2. Modul-spezifischer Renderer (`modules/form-builder/assets/js/form-block-editor.js`)

Das Modul registriert seinen eigenen Renderer im globalen Registry:

```javascript
// Initialisiere das globale Renderer-Registry
if (!window.blockEditorRenderers) {
    window.blockEditorRenderers = {};
}

// Registriere den Form-Block Renderer
window.blockEditorRenderers['form'] = function(block, editorInstance) {
    // ... Editor-Logik ...
};
```

### 3. Asset-Loading über Modul-Interface

Das Modul teilt dem System mit, welche Assets geladen werden sollen:

**FormBuilderModule.php:**
```php
public function getAdminAssets(): array
{
    return [
        'css' => [],
        'js' => [
            '/modules/form-builder/assets/js/form-block-editor.js'
        ]
    ];
}
```

### 4. Automatisches Laden im Admin-Template

Das Admin-Template lädt automatisch alle Modul-Assets:

**admin/templates/layout.php:**
```php
<!-- Module Assets (JS) -->
<?php if (!empty($moduleAssets['js'])): ?>
    <?php foreach ($moduleAssets['js'] as $jsFile): ?>
        <script src="<?= htmlspecialchars($jsFile) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
```

## Vorteile

✅ **Vollständige Modul-Unabhängigkeit**: Core-Code enthält keine modulspezifische Logik
✅ **Erweiterbar**: Andere Module können genauso eigene Block-Renderer registrieren
✅ **Automatisches Asset-Loading**: Module definieren ihre Assets selbst
✅ **Sauber getrennt**: Klare Grenze zwischen Core und Modulen
✅ **Keine Code-Duplikation**: Renderer-Logik nur im Modul

## Beispiel: Neues Modul mit Custom-Block

```javascript
// modules/mein-modul/assets/js/mein-block-editor.js
window.blockEditorRenderers['meinblock'] = function(block, editorInstance) {
    return `<div>Mein Custom Editor für Block ${block.id}</div>`;
};
```

```php
// modules/mein-modul/MeinModul.php
public function getAdminAssets(): array
{
    return [
        'js' => ['/modules/mein-modul/assets/js/mein-block-editor.js']
    ];
}
```

Das war's! Kein Core-Code muss angepasst werden.
