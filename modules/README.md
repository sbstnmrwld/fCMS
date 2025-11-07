# fCMS Module

Dieses Verzeichnis enthält alle fCMS-Module. Module erweitern die Funktionalität des CMS ohne den Core zu verändern.

## Modul-Struktur

Jedes Modul muss folgende Struktur haben:

```
modules/
  └── mein-modul/
      ├── module.json          # Modul-Metadaten (erforderlich)
      ├── MeinModulModule.php  # Hauptklasse (erforderlich)
      ├── views/               # View-Templates (optional)
      ├── assets/              # CSS/JS/Bilder (optional)
      └── config/              # Konfigurationsdateien (optional)
```

## module.json

Die `module.json` enthält alle Metadaten des Moduls:

```json
{
  "name": "Mein Modul",
  "id": "mein-modul",
  "version": "1.0.0",
  "description": "Beschreibung meines Moduls",
  "author": "Dein Name",
  "requires": {
    "fcms": ">=202511071410-dev"
  },
  "permissions": ["admin"],
  "routes": [
    {
      "path": "/mein-modul",
      "method": "GET",
      "handler": "index"
    }
  ],
  "admin_menu": {
    "title": "Mein Modul",
    "icon": "bi-puzzle",
    "route": "/mein-modul"
  }
}
```

## Modul-Hauptklasse

Die Hauptklasse muss `AbstractModule` erweitern und `ModuleInterface` implementieren:

```php
<?php
namespace fCMS\Modules\MeinModul;

use fCMS\Core\AbstractModule;
use fCMS\Core\ModuleInterface;
use Slim\App;
use Psr\Container\ContainerInterface;

class MeinModulModule extends AbstractModule implements ModuleInterface
{
    public function getName(): string
    {
        return $this->config['name'] ?? 'Mein Modul';
    }

    public function getVersion(): string
    {
        return $this->config['version'] ?? '1.0.0';
    }

    public function boot(App $app, ContainerInterface $container): void
    {
        // Routen registrieren
        $app->get('/admin/mein-modul', [$this, 'index']);
    }

    public function index($request, $response)
    {
        // Controller-Logik hier
        return $response->getBody()->write('Hallo von meinem Modul!');
    }

    public function activate(): bool
    {
        // Optional: Code bei Aktivierung ausführen
        return true;
    }

    public function deactivate(): bool
    {
        // Optional: Code bei Deaktivierung ausführen
        return true;
    }

    public function install(): bool
    {
        // Optional: Installation (Verzeichnisse erstellen, etc.)
        return true;
    }

    public function uninstall(): bool
    {
        // Optional: Deinstallation (Daten löschen, etc.)
        return true;
    }
}
```

## Modul-Verwaltung

Module können über die Admin-Oberfläche unter **Module** aktiviert und deaktiviert werden.

Aktive Module werden in `/content/active-modules.json` gespeichert.

## Mitgelieferte Module

### Form-Builder
Ein vollständiger Formular-Generator mit Drag-and-Drop-Editor, Submission-Verwaltung und E-Mail-Benachrichtigungen.

- **ID**: `form-builder`
- **Routen**: `/admin/forms/*`
- **Funktionen**: Formulare erstellen, bearbeiten, Submissions anzeigen

## Modul erstellen

1. Erstelle einen neuen Ordner in `/modules/` mit der Modul-ID als Namen
2. Erstelle die `module.json` mit allen Metadaten
3. Erstelle die Hauptklasse (z.B. `MeinModulModule.php`)
4. Implementiere die erforderlichen Methoden aus `ModuleInterface`
5. Das Modul erscheint automatisch in der Admin-Modul-Übersicht

## Best Practices

- Verwende eindeutige IDs für Module (z.B. `mein-firma-modulname`)
- Alle Routen sollten mit `/admin/` beginnen für Admin-Funktionen
- Nutze den `AbstractModule` für Basis-Funktionalität
- Speichere Modul-Daten in `/content/modules/{modul-id}/`
- Verwende das LanguageManager-System für Übersetzungen
- Halte Module unabhängig vom Core-System
