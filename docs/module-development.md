# Modul-Entwicklung

Lernen Sie, wie Sie eigene Module für fCMS entwickeln, um die Funktionalität zu erweitern.

## Übersicht

Das fCMS-Modul-System ermöglicht es, die CMS-Funktionalität zu erweitern ohne den Core-Code zu ändern. Module sind vollständig unabhängig und können:

- Eigene Routen registrieren
- Eigene Blöcke hinzufügen
- Eigene Admin-Seiten erstellen
- Eigene Assets laden
- Datenbanken verwenden (optional)

## Modul-Struktur

Ein typisches Modul hat folgende Struktur:

```
modules/
└── mein-modul/
    ├── MeinModulModule.php     # Hauptklasse (erforderlich)
    ├── module.json             # Metadaten (erforderlich)
    ├── README.md               # Dokumentation
    ├── CHANGELOG.md            # Versionshistorie
    ├── assets/                 # Assets
    │   ├── css/
    │   └── js/
    ├── views/                  # Templates
    │   └── admin/
    └── src/                    # Zusätzliche Klassen
```

## Modul erstellen

### Schritt 1: Verzeichnis erstellen

```bash
mkdir modules/mein-modul
cd modules/mein-modul
```

### Schritt 2: module.json erstellen

```json
{
  "name": "mein-modul",
  "version": "202511071800-dev",
  "title": "Mein Modul",
  "description": "Beschreibung meines Moduls",
  "author": "Ihr Name",
  "requires": {
    "fcms": ">=202511071410-dev"
  },
  "permissions": [
    "meinmodul.view",
    "meinmodul.edit"
  ]
}
```

### Schritt 3: Hauptklasse erstellen

Erstellen Sie `MeinModulModule.php`:

```php
<?php

namespace FCMS\\Modules;

use FCMS\\Core\\AbstractModule;
use Psr\\Http\\Message\\ResponseInterface as Response;
use Psr\\Http\\Message\\ServerRequestInterface as Request;

class MeinModulModule extends AbstractModule
{
    /**
     * Wird beim Laden des Moduls aufgerufen
     */
    public function boot(object $app, object $container): void
    {
        // Routen registrieren
        $this->registerRoutes($app, $container);

        // Blöcke registrieren
        $this->registerBlocks($container);
    }

    /**
     * Routen registrieren
     */
    private function registerRoutes(object $app, object $container): void
    {
        // Admin-Route
        $app->get('/admin/meinmodul', function (Request $request, Response $response) use ($container) {
            $content = $this->loadView('admin/overview', [
                'title' => 'Mein Modul'
            ]);

            $html = $this->renderAdminLayout($content, 'Mein Modul', 'meinmodul', $container);
            $response->getBody()->write($html);
            return $response;
        });
    }

    /**
     * Blöcke registrieren
     */
    private function registerBlocks(object $container): void
    {
        try {
            $blockRegistry = $container->get(\\FCMS\\Blocks\\BlockRegistry::class);

            // Block-Klasse laden
            require_once $this->getFilePath('src/MeinBlock.php');

            // Block registrieren
            $block = new \\FCMS\\Modules\\MeinModul\\MeinBlock();
            $blockRegistry->register('meinblock', $block);
        } catch (\\Exception $e) {
            error_log('Block-Registrierung fehlgeschlagen: ' . $e->getMessage());
        }
    }

    /**
     * Wird beim Aktivieren aufgerufen
     */
    public function activate(): void
    {
        // Verzeichnisse erstellen, Datenbank initialisieren, etc.
    }

    /**
     * Wird beim Deaktivieren aufgerufen
     */
    public function deactivate(): void
    {
        // Aufräumen (optional)
    }

    /**
     * Admin-Assets registrieren
     */
    public function getAdminAssets(): array
    {
        return [
            'css' => [
                '/modules/mein-modul/assets/css/admin.css'
            ],
            'js' => [
                '/modules/mein-modul/assets/js/admin.js'
            ]
        ];
    }

    /**
     * Helper für Admin-Layout-Rendering
     */
    private function renderAdminLayout(string $content, string $title, string $activeMenu, object $container): string
    {
        $adminAssets = $container->get(\\FCMS\\Core\\AdminAssetManager::class);
        $lang = $container->get(\\FCMS\\Core\\LanguageManager::class);
        $auth = $container->get(\\FCMS\\Core\\AuthManager::class);
        $csrf = $container->get(\\FCMS\\Core\\CsrfManager::class);

        return renderAdminTemplate('layout', [
            'content' => $content,
            'title' => $title,
            'activeMenu' => $activeMenu,
            'adminAssets' => $adminAssets,
            'lang' => $lang,
            'username' => $auth->getUsername(),
            'csrfToken' => $csrf->getToken(),
        ], $container);
    }
}
```

## Blöcke in Modulen

### Block-Klasse erstellen

Erstellen Sie `src/MeinBlock.php`:

```php
<?php

namespace FCMS\\Modules\\MeinModul;

use FCMS\\Blocks\\AbstractBlock;
use FCMS\\Blocks\\BlockInterface;

class MeinBlock extends AbstractBlock implements BlockInterface
{
    public function getMetadata(): array
    {
        return [
            'name' => 'Mein Block',
            'type' => 'meinblock',
            'category' => 'custom',
            'icon' => 'bi-star',
            'description' => 'Mein custom Block',
        ];
    }

    public function getDefaultAttributes(): array
    {
        return [
            'title' => '',
            'content' => '',
        ];
    }

    public function render(array $attributes, string $content = ''): string
    {
        $title = htmlspecialchars($attributes['title'] ?? '');
        $content = htmlspecialchars($attributes['content'] ?? '');

        return "<div class=\"mein-block\">
            <h3>{$title}</h3>
            <p>{$content}</p>
        </div>";
    }
}
```

### Block-Editor-Integration

Für die Integration in den Block-Editor erstellen Sie `assets/js/mein-block-editor.js`:

```javascript
// Initialisiere Registry falls nicht vorhanden
if (!window.blockEditorRenderers) {
    window.blockEditorRenderers = {};
}

// Registriere Block-Renderer
window.blockEditorRenderers['meinblock'] = function(block, editorInstance) {
    const blockId = block.id;
    const title = block.data.title || '';
    const content = block.data.content || '';

    return `
        <div class="mb-3">
            <label class="form-label">Titel</label>
            <input type="text" class="form-control" value="${title}"
                   onchange="blockEditor.updateBlockData('${blockId}', 'title', this.value)">
        </div>
        <div class="mb-3">
            <label class="form-label">Inhalt</label>
            <textarea class="form-control" rows="3"
                      onchange="blockEditor.updateBlockData('${blockId}', 'content', this.value)">${content}</textarea>
        </div>
    `;
};
```

Registrieren Sie die JS-Datei in `getAdminAssets()`:

```php
public function getAdminAssets(): array
{
    return [
        'js' => [
            '/modules/mein-modul/assets/js/mein-block-editor.js'
        ]
    ];
}
```

## API-Endpunkte

### REST-API erstellen

```php
private function registerApiRoutes(object $app, object $container): void
{
    // GET-Endpunkt
    $app->get('/api/meinmodul/items', function (Request $request, Response $response) {
        $items = $this->getItems();

        $response->getBody()->write(json_encode($items));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // POST-Endpunkt mit Auth
    $app->post('/api/meinmodul/items', function (Request $request, Response $response) use ($container) {
        $auth = $container->get(\\FCMS\\Core\\AuthManager::class);

        if (!$auth->isAuthenticated()) {
            return $response->withStatus(401);
        }

        $data = $request->getParsedBody();
        $this->saveItem($data);

        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    });
}
```

## Daten speichern

### JSON-basierte Speicherung

```php
private function saveItem(array $data): void
{
    $dataDir = __DIR__ . '/../../content/meinmodul';

    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    $itemId = uniqid('item_');
    $file = $dataDir . '/' . $itemId . '.json';

    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

private function getItems(): array
{
    $dataDir = __DIR__ . '/../../content/meinmodul';
    $items = [];

    if (!is_dir($dataDir)) {
        return $items;
    }

    foreach (glob($dataDir . '/*.json') as $file) {
        $data = json_decode(file_get_contents($file), true);
        if ($data) {
            $items[] = $data;
        }
    }

    return $items;
}
```

## Views/Templates

### Admin-View erstellen

Erstellen Sie `views/admin/overview.php`:

```php
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1><?= htmlspecialchars($title ?? 'Mein Modul') ?></h1>

            <div class="card mt-4">
                <div class="card-header">
                    Übersicht
                </div>
                <div class="card-body">
                    <p>Hier kommt Ihr Modul-Inhalt...</p>
                </div>
            </div>
        </div>
    </div>
</div>
```

Laden Sie die View:

```php
$content = $this->loadView('admin/overview', [
    'title' => 'Mein Modul',
    'items' => $items
]);
```

## Modul aktivieren/deaktivieren

Module werden über den Admin-Bereich verwaltet:

1. Gehen Sie zu **"Module"**
2. Klicken Sie auf **"Aktivieren"**
3. Modul wird geladen und `boot()` wird aufgerufen

## Best Practices

### Namenskonventionen

- **Modul-Name**: `kebab-case` (z.B. `mein-modul`)
- **Klassen-Name**: `PascalCase` + `Module` Suffix (z.B. `MeinModulModule`)
- **Namespace**: `FCMS\\Modules`

### Fehlerbehandlung

```php
try {
    // Riskante Operation
} catch (\\Exception $e) {
    error_log('[MeinModul] Fehler: ' . $e->getMessage());
    // Fehler nicht zum Absturz führen lassen
}
```

### CSRF-Schutz

```php
$csrf = $container->get(\\FCMS\\Core\\CsrfManager::class);

// Token validieren
if (!$csrf->validateToken($data['csrf_token'] ?? '')) {
    return $response->withStatus(403);
}

// Token in View übergeben
$content = $this->loadView('admin/form', [
    'csrfToken' => $csrf->getToken()
]);
```

### Datei-Sicherheit

```php
// IMMER basename() verwenden für Datei-Pfade
$file = $dataDir . '/' . basename($id) . '.json';

// NIE direkt User-Input verwenden
// ❌ FALSCH:
$file = $dataDir . '/' . $_GET['id'] . '.json';
```

## Beispiel: FormBuilder-Modul

Das FormBuilder-Modul ist ein gutes Beispiel für ein vollständiges Modul:

- `modules/form-builder/FormBuilderModule.php` - Hauptklasse
- `modules/form-builder/FormBlock.php` - Custom Block
- `modules/form-builder/views/` - Admin-Templates
- `modules/form-builder/assets/` - CSS/JS

Studieren Sie den Code für Inspiration!

## Weiterführende Dokumentation

- [Block-Entwicklung](block-development.md) - Custom Blöcke erstellen
- [Architektur-Übersicht](architecture.md) - System-Architektur verstehen
- [Asset-Management](asset-management.md) - Assets richtig einbinden
