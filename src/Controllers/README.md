# Controller-Architektur

## Überblick

Dieses Verzeichnis enthält die Controller für den fCMS Admin-Bereich. Die Controller wurden aus der monolithischen `admin.php` (1648 Zeilen) extrahiert um eine bessere Code-Organisation und Wartbarkeit zu ermöglichen.

## Architektur

### BaseController

Abstrakte Basis-Klasse mit gemeinsamen Funktionen:
- `render()` - Template-Rendering
- `json()` - JSON-Responses
- `redirect()` - HTTP-Redirects
- `flash()` - Flash-Messages
- `getParam()` / `getParams()` - Request-Parameter

### Controller

**AuthController** - Authentifizierung
- `showLogin()` - Login-Formular anzeigen
- `login()` - Login-Versuch verarbeiten
- `logout()` - Benutzer ausloggen

**PageController** - Seiten-Verwaltung
- `index()` - Alle Seiten auflisten
- `create()` - Formular für neue Seite
- `store()` - Neue Seite speichern
- `edit()` - Formular zum Bearbeiten
- `update()` - Seite aktualisieren
- `delete()` - Seite löschen

## Verwendung

### Controller registrieren

In `public/admin.php`:

```php
use FCMS\Controllers\PageController;

$container->set(PageController::class, function($container) {
    return new PageController($container);
});
```

### Routes definieren

In `config/routes.php`:

```php
$app->get('/pages', [PageController::class, 'index'])->add($authMiddleware);
$app->post('/pages/create', [PageController::class, 'store'])->add($authMiddleware);
```

## Migration von admin.php

Die Controller nutzen die bestehende `renderAdminTemplate()`-Funktion, um Kompatibilität zu gewährleisten. Schrittweise Migration:

1. ✅ BaseController erstellt
2. ✅ AuthController erstellt
3. ✅ PageController erstellt
4. ⏳ SettingsController (TODO)
5. ⏳ ModuleController (TODO)
6. ⏳ MediaController (TODO)
7. ⏳ NavigationController (TODO)
8. ⏳ ThemeController (TODO)
9. ⏳ BlockController (TODO)

## Vorteile

- **Separation of Concerns**: Business-Logik getrennt von Routing
- **Testbarkeit**: Controller können isoliert getestet werden
- **Wiederverwendbarkeit**: Gemeinsame Funktionen in BaseController
- **Wartbarkeit**: Kleinere, fokussierte Klassen statt Monolith
- **Type-Safety**: Strict Types und Dependency Injection

## Testing

Controller-Tests befinden sich in `tests/Unit/Controllers/`.

Beispiel:
```php
public function testPageIndexReturnsAllPages(): void
{
    $controller = new PageController($this->container);
    $response = $controller->index($this->request, $this->response);

    $this->assertEquals(200, $response->getStatusCode());
}
```
