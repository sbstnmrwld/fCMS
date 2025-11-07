# System-Architektur

Verstehen Sie die technische Architektur und Konzepte von fCMS.

## Überblick

fCMS ist ein dateibasiertes CMS mit modularer Architektur:

- **Dateibasiert**: Keine Datenbank erforderlich
- **Modular**: Erweiterbar durch Module und Blöcke
- **PSR-kompatibel**: Moderne PHP-Standards
- **Dependency Injection**: Saubere Abhängigkeiten
- **Asset-Trennung**: Admin und Themes isoliert

## Technologie-Stack

### Core-Frameworks

- **[Slim Framework 4](https://www.slimframework.com/)**: HTTP-Routing und Middleware
- **[PHP-DI 7](https://php-di.org/)**: Dependency Injection Container
- **[Symfony Components](https://symfony.com/components)**: Filesystem, YAML
- **Native PHP**: Templates (keine Template-Engine erforderlich)

### Frontend (Admin-Bereich)

- **[Bootstrap 5](https://getbootstrap.com/)**: UI-Framework für Admin-Interface (lokal)
- **[Bootstrap Icons](https://icons.getbootstrap.com/)**: Icon-Set für Admin-Interface (lokal)
- **Vanilla JavaScript**: Keine Framework-Abhängigkeiten

### Frontend (Themes)

- **Flexibel**: Themes können beliebige CSS-Frameworks verwenden
- **Default-Theme**: Verwendet Bootstrap 5 (optional für andere Themes)
- **Vanilla JavaScript**: Keine Framework-Abhängigkeiten

### Development

- **[Composer](https://getcomposer.org/)**: Dependency Management
- **[PHPUnit](https://phpunit.de/)**: Testing Framework
- **PSR-4**: Autoloading-Standard

## Verzeichnis-Struktur

```
fCMS/
├── admin/                  # Admin-Interface
│   ├── assets/            # Admin-Assets (Bootstrap, CSS, JS)
│   └── templates/         # Admin-Templates (PHP)
├── blocks/                # Core-Blöcke
│   ├── AbstractBlock.php
│   ├── ParagraphBlock.php
│   └── ...
├── config/                # Konfiguration
│   └── config.php
├── content/               # Daten (JSON)
│   ├── pages/            # Seiten-JSON
│   ├── media/            # Uploads
│   └── settings.json     # Einstellungen
├── docs/                  # Dokumentation
├── languages/             # Übersetzungen (JSON)
├── logs/                  # Log-Dateien
├── modules/               # Module
│   └── [modul-name]/
├── public/                # Web-Root
│   ├── .htaccess         # Apache-Konfiguration
│   ├── index.php         # Frontend Entry Point
│   └── admin.php         # Admin Entry Point
├── src/                   # Core-Klassen
│   ├── Blocks/           # Block-System
│   └── Core/             # Core-Services
├── themes/                # Themes
│   └── [theme-name]/
├── vendor/                # Composer Dependencies
└── composer.json          # Projekt-Konfiguration
```

## Core-Services

### 1. Dependency Injection Container

**PHP-DI Container** verwaltet alle Services:

```php
// Container-Konfiguration
$containerBuilder = new ContainerBuilder();
$container = $containerBuilder->build();

// Service-Registrierung
$container->set(AuthManager::class, function() {
    return new AuthManager();
});

// Service-Nutzung
$auth = $container->get(AuthManager::class);
```

### 2. Asset-Manager

**Getrennte Asset-Manager** für Admin und Themes:

```php
// Admin-Assets
class AdminAssetManager {
    public function css(string $file): string;
    public function js(string $file): string;
    public function bootstrapCss(): string;
}

// Theme-Assets
class ThemeAssetManager {
    public function css(string $file): string;
    public function js(string $file): string;
    public function image(string $file): string;
}
```

### 3. Authentication & Session

```php
class AuthManager {
    public function isAuthenticated(): bool;
    public function login(string $username, string $password): bool;
    public function logout(): void;
}

class SessionManager {
    public function start(): void;
    public function regenerate(): void;
    public function destroy(): void;
}
```

### 4. Content-Management

```php
class ContentManager {
    public function getPage(string $slug): ?array;
    public function savePage(array $pageData): bool;
    public function getAllPages(): array;
}

class LanguageManager {
    public function get(string $key): string;
    public function getCurrentLanguage(): string;
}
```

## Block-System

### Block-Interface

Alle Blöcke implementieren `BlockInterface`:

```php
interface BlockInterface {
    public function getMetadata(): array;
    public function getDefaultAttributes(): array;
    public function render(array $attributes, string $content = ''): string;
    public function validate(array $attributes): bool;
}
```

### Block-Registry

**Zentrale Block-Registrierung**:

```php
class BlockRegistry {
    private array $blocks = [];

    public function register(string $type, BlockInterface $block): void;
    public function get(string $type): ?BlockInterface;
    public function getAllBlocks(): array;
}
```

### Block-Discovery

Automatische Erkennung von Block-Klassen:

```php
// In src/Blocks/BlockRegistry.php
foreach (glob(__DIR__ . '/../../blocks/*Block.php') as $file) {
    $className = basename($file, '.php');
    $block = new $className();
    $this->register($block->getName(), $block);
}
```

## Modul-System

### Modul-Interface

```php
interface ModuleInterface {
    public function getName(): string;
    public function boot(object $app, object $container): void;
    public function activate(): void;
    public function deactivate(): void;
}
```

### Modul-Manager

```php
class ModuleManager {
    public function discoverModules(): void;
    public function activate(string $name): bool;
    public function bootActiveModules(object $app, object $container): void;
    public function getAdminAssets(): array;
}
```

### Modul-Struktur

```
modules/mein-modul/
├── MeinModulModule.php    # Hauptklasse (implements ModuleInterface)
├── module.json            # Metadaten
├── assets/                # CSS/JS
├── views/                 # Templates
└── src/                   # Zusätzliche Klassen
```

## HTTP-Routing

### Slim Framework Integration

```php
// public/index.php - Frontend
$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response) {
    // Homepage
});

$app->get('/{slug}', function (Request $request, Response $response, $args) {
    // Seiten-Routing
});

// public/admin.php - Admin
$app->get('/admin', function (Request $request, Response $response) {
    // Admin-Dashboard
});
```

### Route-Gruppen

```php
// Admin-Routen mit Middleware
$app->group('/admin', function (Group $group) {
    $group->get('/pages', PagesController::class);
    $group->post('/pages/save', PagesController::class . ':save');
})->add($authMiddleware);
```

## Datenspeicherung

### JSON-basierte Persistierung

**Seiten-Speicherung**:

```php
// content/pages/about.json
{
  "title": "Über uns",
  "slug": "about",
  "status": "published",
  "sections": [
    {
      "type": "paragraph",
      "data": {
        "text": "Willkommen bei..."
      }
    }
  ]
}
```

**Einstellungen**:

```php
// content/settings.json
{
  "site": {
    "name": "Meine Website",
    "language": "de"
  },
  "theme": {
    "active": "default"
  }
}
```

### File-Locking

Thread-sichere Datei-Operationen:

```php
class SettingsManager {
    public function save(): void {
        $fp = fopen($this->file, 'w');
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, json_encode($this->data));
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }
}
```

## Template-System

### Native PHP-Templates

**Kein Template-Engine** erforderlich:

```php
// admin/templates/layout.php
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($title) ?></title>
    <link href="<?= $adminAssets->bootstrapCss() ?>" rel="stylesheet">
</head>
<body>
    <?= $content ?>
</body>
</html>
```

### Template-Vererbung

**Template-Hierarchie**:

1. Theme-spezifische Templates
2. Core-Templates (Fallback)

```php
// Theme-Template prüfen
$themeTemplate = "themes/{$theme}/templates/{$template}.php";
$coreTemplate = "admin/templates/{$template}.php";

if (file_exists($themeTemplate)) {
    include $themeTemplate;
} else {
    include $coreTemplate;
}
```

## Sicherheit

### CSRF-Protection

```php
class CsrfManager {
    public function getToken(): string {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateToken(string $token): bool {
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
```

### Input-Sanitization

```php
// Immer escapen
<?= htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8') ?>

// In Blöcken
protected function escape(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
```

### Session-Sicherheit

```php
'session' => [
    'name' => 'fcms_session',
    'httponly' => true,
    'secure' => true,
    'samesite' => 'Strict',
]
```

## Performance

### Cache-Busting

Assets haben automatische Versionierung:

```php
public function css(string $file): string {
    $path = '/admin/assets/css/' . $file;
    $version = filemtime(__DIR__ . '/../public' . $path);
    return $path . '?v=' . $version;
}
```

### Lazy Loading

```php
// Service erst bei Bedarf laden
$container->set(BlockRegistry::class, function() {
    $registry = new BlockRegistry();
    $registry->discoverBlocks();
    return $registry;
});
```

## Extension Points

### 1. Custom Blöcke

```php
// blocks/CustomBlock.php
class CustomBlock extends AbstractBlock {
    public function render(array $attributes): string {
        return '<div>Custom Content</div>';
    }
}
```

### 2. Module

```php
// modules/my-module/MyModuleModule.php
class MyModuleModule extends AbstractModule {
    public function boot($app, $container): void {
        $app->get('/my-route', MyController::class);
    }
}
```

### 3. Middleware

```php
$app->add(function (Request $request, RequestHandler $handler) {
    // Custom Middleware
    return $handler->handle($request);
});
```

## Deployment-Architektur

### Entwicklung vs. Produktion

**Entwicklung**:
- Alle Composer-Dependencies
- Debug-Modus aktiviert
- Entwicklungs-Tools

**Produktion**:
- Nur Produktions-Dependencies (`--no-dev`)
- Optimierter Autoloader
- Debug-Modus deaktiviert

### Build-Prozess

```bash
# Produktions-Build
composer install --no-dev --optimize-autoloader
composer build  # Erstellt ZIP-Release
```

## Testing

### PHPUnit-Integration

```php
// tests/Unit/BlockRegistryTest.php
class BlockRegistryTest extends TestCase {
    public function testBlockRegistration(): void {
        $registry = new BlockRegistry();
        $block = new ParagraphBlock();

        $registry->register('paragraph', $block);

        $this->assertSame($block, $registry->get('paragraph'));
    }
}
```

## Weiterführend

- [Modul-Entwicklung](module-development.md) - Module erstellen
- [Block-Entwicklung](block-development.md) - Custom Blöcke
- [Theme-Entwicklung](theme-development.md) - Theme-Architektur
- [Asset-Management](asset-management.md) - Asset-System verstehen
