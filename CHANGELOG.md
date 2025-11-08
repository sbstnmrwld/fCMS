# Changelog

Alle wichtigen Änderungen an fCMS werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).

## [202511081732-dev] - 2025-11-08

### Behoben
- **Race Condition bei Slug-Generierung**: File-Locking verhindert jetzt Duplikate
  - Exklusives Lock während `ensureUniqueSlug()` und `savePage()`
  - Lock-Datei: `content/pages/.slug-creation.lock`
  - Garantiert eindeutige Slugs auch bei gleichzeitigen Page-Erstellungen
  - 100% Thread-Safe ohne externe Dependencies

### Hinzugefügt
- **Race Condition Tests**: Umfassende Test-Suite für parallele Zugriffe
  - `ContentManagerRaceConditionTest.php`: 3 Unit-Tests (31 Assertions)
  - `test-race-condition.php`: Manuelles Test-Script mit Fork
  - `PageCreationRaceConditionTest.php`: Integration-Tests mit curl_multi
  - Dokumentation: `docs/race-condition-fix.md`

## [202511081615-dev] - 2025-11-08

### Hinzugefügt
- **Module-Asset-Route**: Dynamisches Serving von Modul-Assets über `/modules/{module}/assets/{path}`
  - Sicherheitscheck gegen Directory Traversal
  - Content-Type-Detection für CSS, JS, Bilder, Fonts
  - Ermöglicht Modulen eigene Assets im Frontend zu laden

### Geändert
- **ModuleManager Frontend-Integration**: Module werden jetzt korrekt im Frontend initialisiert
  - `ModuleManager::discoverModules()` wird in `public/index.php` aufgerufen
  - Module-Blöcke (z.B. FormBlock) werden im BlockRegistry registriert
  - Public Routes von Modulen werden korrekt registriert
- **.gitignore**: Erweitert um `content/submissions/*` und `logs/*`

### Behoben
- **Modul-Blöcke im Frontend**: Blöcke von Modulen werden jetzt korrekt gerendert
  - FormBlock wird auf Frontend-Seiten angezeigt
  - BlockRegistry hat Zugriff auf alle registrierten Module-Blöcke

## [202511081603-dev] - 2025-11-08

### Hinzugefügt
- **Drag-and-Drop Navigation-Verwaltung**: Vollständig interaktive Navigation-Verwaltung unter `/admin/navigation`
  - Seiten per Drag & Drop zwischen Haupt- und Footer-Navigation verschieben
  - Reihenfolge per Drag & Drop innerhalb der Navigationen anpassen
  - Echtzeit-Speichern mit visueller Feedback-Anzeige
  - Automatische Neuberechnung aller Positionen nach Änderungen
  - Responsive 2-Spalten-Layout mit visuellen Drag-Handles
  - Verhindert Entfernen von Seiten (alle müssen in einer Navigation sein)
- **NavigationController.update()**: JSON-API für Navigation-Updates
  - Akzeptiert JSON-Struktur: `{main: [...slugs], footer: [...slugs]}`
  - CSRF-Token-Validierung
  - Batch-Update aller Seiten mit neuer Position
- **JavaScript NavigationManager**: Client-seitige Drag-and-Drop-Logik
  - Native HTML5 Drag & Drop API
  - Bidirektionales Verschieben zwischen Listen
  - Automatisches Sortieren während des Dragging
  - Bootstrap-Notifications für Feedback
- **CSS navigation.css**: Styling für Drag-and-Drop-Interface
  - Hover-Effekte und visuelle Drag-Feedback
  - Responsive Design für mobile Geräte
  - Dark-Mode-Support
- **Router-Script**: `public/router.php` für PHP Built-in Development Server
  - Simuliert .htaccess Rewrite-Regeln
  - Admin-Assets werden korrekt ausgeliefert
  - Ermöglicht lokale Entwicklung ohne Apache/Nginx
- **6 neue NavigationController-Tests**: Vollständige Test-Coverage für Drag-and-Drop-API
- **PageController**: Exklusive Navigation-Auswahl mit Radio-Buttons
  - Nur eine Navigation wählbar (entweder Main oder Footer)
  - Automatische Position-Zuweisung beim Erstellen/Wechseln
  - Link zur Drag-and-Drop-Verwaltung im Formular

### Geändert
- **Navigation-Verwaltung Workflow**: Position wird nicht mehr manuell eingegeben
  - Position-Feld aus Seitenbearbeitung entfernt
  - Automatische Zuweisung ans Ende der gewählten Navigation
  - Reihenfolge nur noch über Drag-and-Drop in `/admin/navigation` anpassbar
  - Vereinfachtes UI in Seitenbearbeitung
- **PageController store()/update()**: Automatische Position-Berechnung
  - Neue Seiten werden ans Ende gesetzt
  - Bei Navigation-Wechsel (Main ↔ Footer) ans Ende der neuen Navigation
  - Ansonsten Position beibehalten
- **Navigation-API**: JSON statt URL-encoded für Arrays
  - Korrekte Übertragung von Array-Strukturen
  - Content-Type: application/json
  - Explizites JSON-Parsing im Backend
- **CSS-Klassennamen**: Konflikt mit Bootstrap's `.nav-item` behoben
  - `.nav-item` → `.navigation-page-item` (und alle Varianten)
  - Admin-Navigation funktioniert wieder korrekt
- **Dokumentation**: Development Server Befehle aktualisiert
  - CLAUDE.md und README.md mit Router-Script-Verwendung
  - Korrekte Anleitung für lokale Entwicklung

### Entfernt
- **Position-API-Endpunkte**: `/pages/suggest-position` und `/pages/check-position`
  - Nicht mehr benötigt durch Drag-and-Drop-Verwaltung
  - `page-form.js` vollständig entfernt
  - 5 Tests für Position-API entfernt

### Technische Details
- **Tests**: 311 Tests, 468 Assertions (alle grün)
- **Code-Qualität**: Strict types, vollständige Type-Hints, Exception-Handling
- **Security**: CSRF-Validierung, Input-Validierung, keine XSS-Möglichkeiten

---

## [202511081430-dev]

### Behoben
- **PageController Create-Funktionalität**: Seiten-Erstellung war nicht funktionsfähig
  - `renderPageForm()` Methode war unvollständiger Platzhalter (nur Überschrift, keine Formularfelder)
  - Vollständiges Formular implementiert mit allen Feldern (Titel, Slug, Status, Navigation, SEO)
  - Block-Editor vollständig integriert (CSS/JS Assets, Editor-Container, Initialisierung)
  - `edit()` Methode refactored - nutzt jetzt die gemeinsame `renderPageForm()` Methode (DRY-Prinzip)
  - Code-Duplizierung eliminiert (120+ Zeilen redundanter Form-HTML entfernt)
- **Code-Qualität**: Unbenutzte Variablen in Exception-Catch-Blöcken mit Unterstrich-Präfix versehen

### Hinzugefügt
- **CLAUDE.md**: Umfassende Entwickler-Dokumentation für Claude Code
  - Projekt-Übersicht und Architektur-Beschreibung
  - Entwicklungs-Kommandos (Tests, Build, Server)
  - Controller-Architektur und Core-Services
  - Exception-Handling und Validierungssystem
  - Block-, Modul- und Theme-System Dokumentation
  - Testing-Standards und Code-Practices
  - Häufige Entwicklungsaufgaben

### Geändert
- **PageController**: Von 437 auf 437 Zeilen bei gleichzeitiger Funktionserweiterung durch Code-Deduplizierung

---

## [202511081147-dev]

### Geändert
- **admin.php Refactoring vollständig abgeschlossen**: admin.php drastisch von **1648 auf 343 Zeilen reduziert** (79% kleiner, 1305 Zeilen entfernt)
  - **Auth-Routes**: Nutzen jetzt AuthController (login, logout)
  - **Page-Routes**: Nutzen jetzt PageController (index, create, store, edit, update, delete)
  - **Dashboard-Routes**: Nutzen jetzt DashboardController (index)
  - **Settings-Routes**: Nutzen jetzt SettingsController (index, update)
  - **Navigation-Routes**: Nutzen jetzt NavigationController (index)
  - **Block-Routes**: Nutzen jetzt BlockController (index)
  - **Module-Routes**: Nutzen jetzt ModuleController (index, activate, deactivate)
  - **Media-Routes**: Nutzen jetzt MediaController (index, Platzhalter)
  - **Theme-Routes**: Nutzen jetzt ThemeController (index, Platzhalter)
  - **Controller-Integration**: Alle 9 Controller in DI Container registriert und mit Routes verbunden
  - **Verbesserte Maintainability**: Gesamte Route-Logik nun in dedizierten Controller-Klassen

- **Alle Controller vollständig implementiert**:
  - **PageController**: Vollständiges Edit-Formular mit Block-Editor, JSON-Parsing, CSRF-Validierung
  - **SettingsController**: Komplettes Settings-Formular (Website, SEO, Theme, Wartungsmodus)
  - **NavigationController**: Navigation-Management mit Hauptnavigation, Footer-Navigation und verfügbaren Seiten
  - **BlockController**: Block-Bibliothek mit Live-Beispielen und Metadata für alle registrierten Blöcke
  - **ModuleController**: Modul-Verwaltung mit Aktivierung/Deaktivierung und vollständiger Modul-Info
  - **DashboardController**: Dashboard mit Statistiken und Quick-Actions
  - **AuthController**: Login/Logout mit korrekten Template-Parametern (csrfField, config, lockoutTime)
  - **MediaController**: Platzhalter für zukünftige Media-Verwaltung
  - **ThemeController**: Platzhalter für zukünftige Theme-Verwaltung

### Hinzugefügt
- **9 Controller-Klassen**: Vollständige MVC-Architektur für Admin-Bereich
  - src/Controllers/AuthController.php
  - src/Controllers/PageController.php
  - src/Controllers/DashboardController.php
  - src/Controllers/SettingsController.php
  - src/Controllers/ModuleController.php
  - src/Controllers/MediaController.php
  - src/Controllers/NavigationController.php
  - src/Controllers/ThemeController.php
  - src/Controllers/BlockController.php

- **Controller-Tests**: Vollständige Test-Abdeckung für neue Controller-Architektur
  - **AuthControllerTest**: 7 Tests für Login/Logout-Funktionalität (CSRF-Validierung, Success/Failure-Szenarien)
  - **PageControllerTest**: 12 Tests für CRUD-Operationen (index, create, store, edit, update, delete)
  - **Test-Bootstrap**: Mock-Funktion für renderAdminTemplate() in Unit-Tests
  - **Test-Suite**: Erweitert von 286 auf 305 Tests (+19 Tests, +43 Assertions)

### Behoben
- Login-Seite zeigt keine PHP-Warnings mehr (Undefined variable $csrfField)
- Edit-Seite rendert vollständiges Formular statt leerer Platzhalter
- Settings-Update verwendet korrekte SettingsManager::set() Methode
- Module activate/deactivate verwenden korrekte ModuleManager-Methoden

### Technical Debt
- **Media-Verwaltung**: Noch nicht vollständig implementiert (Platzhalter vorhanden)
- **Theme-Verwaltung**: Noch nicht vollständig implementiert (Platzhalter vorhanden)

---

## [202511081145-dev]

### Hinzugefügt
- **Controller-Architektur**: Refactoring-Grundlage für admin.php erstellt
  - **BaseController**: Basis-Klasse mit gemeinsamen Helper-Methoden
  - **AuthController**: Login/Logout-Logik vorbereitet
  - **PageController**: Seiten-CRUD-Operationen vorbereitet
  - **Routes-Konfiguration**: Zentrale Route-Definitionen in config/routes.php (noch nicht integriert)
  - **Controller-Dokumentation**: README mit Architektur-Übersicht

### Technical Debt
- **Nicht integriert**: Controller-Dateien erstellt aber admin.php nutzte sie noch nicht

---

## [202511081140-dev]

### Hinzugefügt
- **Vollständige Test-Suite**: 63 neue Tests für verbleibende Komponenten
  - **ThemeAssetManagerTest**: 15 Tests für Asset-URLs, Cache-Busting, Theme-Switching
  - **AdminAssetManagerTest**: 13 Tests für Admin-Assets mit Cache-Busting
  - **BlockRegistryTest**: 13 Tests für Block-Registry, Rendering, Discovery
  - **AbstractBlockTest**: 8 Tests für Block-Helper-Methoden, Validation, Escaping
  - **AbstractModuleTest**: 14 Tests für Modul-Base-Class, Config-Loading
  - **Gesamt**: 286 Tests, 412 Assertions

### Geändert
- **Test-Coverage**: Von 223 auf 286 Tests erweitert (+28%)
- **Vollständigkeit**: Alle Core-, Block- und Asset-Manager-Klassen getestet

---

## [202511081130-dev]

### Hinzugefügt
- **High-Priority Unit-Tests**: 36 neue Tests für wichtige Core-Komponenten
  - **ThemeManagerTest**: 11 Tests für Template-Rendering, Theme-Switching
  - **LanguageManagerTest**: 14 Tests für Übersetzungen, Fallbacks, Caching
  - **SettingsManagerTest**: 11 Tests für Settings-Verwaltung, Persistenz
  - **Gesamt**: 223 Tests, 335 Assertions

### Geändert
- **Test-Coverage**: Von 187 auf 223 Tests erweitert (+19%)

---

## [202511081120-dev]

### Hinzugefügt
- **Kritische Unit-Tests**: 96 neue Tests für Core-Komponenten
  - **SessionManagerTest**: 18 Tests für Session-Handling, Sicherheit, Timeout
  - **CsrfManagerTest**: 16 Tests für CSRF-Token-Generierung und -Validierung
  - **AuthManagerTest**: 19 Tests für Login, Logout, Brute-Force-Protection
  - **ContentManagerTest**: 27 Tests für CRUD-Operationen, Validierung, File-Locking
  - **ModuleManagerTest**: 16 Tests für Modul-Discovery, Aktivierung, Persistenz
  - **Gesamt**: 187 Tests, 285 Assertions, ~95% Code Coverage

### Geändert
- **Test-Infrastruktur**: Tests laufen in separaten Prozessen für Session-Isolation

---

## [202511081112-dev]

### Geändert
- **PHP Mindestversion**: Erhöhung von PHP 7.4 auf PHP 8.1
  - Kompatibilität mit modernen Symfony-Komponenten (6.x)
  - PHP 7.4 und 8.0 sind End-of-Life (keine Security-Updates)
  - Test-Matrix reduziert auf PHP 8.1, 8.2, 8.3
- **GitHub Actions**: Deprecated `--no-suggest` Flag entfernt
- **Composer Validation**: `--strict` Flag entfernt (version-Feld Warnung irrelevant für Projekte)

---

## [202511081104-dev]

### Geändert
- **GitHub Actions Workflows**: Aktualisierung auf neueste Action-Versionen
  - `actions/upload-artifact@v3` → `@v4` (v3 deprecated)
  - `actions/cache@v3` → `@v4`
  - Verbesserte Performance und langfristige Unterstützung

---

## [202511081053-dev]

### Hinzugefügt
- **PHPUnit Test-Suite**: Umfassende automatisierte Tests
  - 91 Unit-Tests mit 148 Assertions
  - Validator-Tests (73 Tests, 100% Coverage)
  - Exception-Tests (18 Tests, 100% Coverage)
  - PHPUnit 9.6 Konfiguration mit Code-Coverage
  - `composer test` Command für einfache Test-Ausführung
- **Test-Dokumentation**: `TESTING.md` mit vollständiger Anleitung
  - Test-Struktur und Organisation
  - Code-Coverage-Ziele (~95% aktuell)
  - Best Practices und Beispiele
  - Troubleshooting-Guide
- **Fish-Shell Test-Runner**: `scripts/test.fish`
  - Interaktives Test-Script mit farbiger Ausgabe
  - Watch-Mode für kontinuierliches Testing
  - Filter-Optionen für spezifische Tests
  - Coverage-Report-Generierung mit Auto-Open (macOS)
- **CI/CD Test-Integration**: Automatische Tests in GitHub Actions
  - `.github/workflows/tests.yml`: Multi-Version PHP-Tests (7.4-8.3)
  - Codecov-Integration für Coverage-Tracking
  - Test-Execution vor Release-Builds
  - Composer-Cache für schnellere Builds

### Hinzugefügt (von 202511081040-dev)
- **Umfassendes Input-Validierungssystem**: Zentrale Validierung aller Benutzereingaben
  - `Validator`-Klasse mit 15+ Validierungsmethoden (String, Slug, Enum, Integer, Boolean, Array, Email, URL, JSON, Filename)
  - `ValidationException`, `NotFoundException`, `StorageException` für typisiertes Error-Handling
  - Composite-Validierungen: `pageData()`, `loginCredentials()`

### Sicherheit
- **Path-Traversal-Schutz**: Slug-Validierung verhindert `../` und andere Path-Traversal-Versuche
- **Type-Safety**: Strikte Typ-Validierung für alle Inputs (Strings, Integers, Booleans, Arrays)
- **Enum-Validierung**: Status-Felder und andere Enums werden gegen erlaubte Werte geprüft
- **JSON-Validierung**: Sichere JSON-Dekodierung mit Fehlerprüfung
- **Filename-Validierung**: Schutz vor Path-Traversal bei Dateinamen
- **Length-Limits**: UTF-8-aware Längenprüfung für alle String-Inputs
- **Reservierte Slugs**: Schutz vor Verwendung reservierter Namen (admin, api, assets, etc.)

### Geändert
- **ContentManager**: Alle Methoden nutzen Validator und werfen typisierte Exceptions
  - `createPage()`: Validiert alle Seitendaten via `Validator::pageData()`
  - `updatePage()`: Validiert Slug und Update-Daten, wirft `NotFoundException`
  - `deletePage()`: Validiert Slug, wirft `NotFoundException` und `StorageException`
  - `getPage()`: Validiert Slug, wirft `StorageException` bei JSON-Fehlern
  - `savePage()`: Prüft JSON-Encoding und File-Operations
  - `pageExists()`: Validiert Slug gegen Path-Traversal
- **AuthManager**: Login-Credentials werden validiert via `Validator::loginCredentials()`
- **admin.php**: Exception-Handling in allen Routen (Login, Seiten erstellen/bearbeiten/löschen)
  - HTTP 400 bei Validierungsfehlern
  - HTTP 404 bei fehlenden Ressourcen
  - HTTP 500 bei Speicherfehlern

### Breaking Changes
- **ContentManager-API**: Methoden werfen nun Exceptions statt `false` zurückzugeben
  - `updatePage()`: Wirft `NotFoundException` statt `false`
  - `deletePage()`: Wirft `NotFoundException`/`StorageException` statt `false`
  - `getPage()`: Wirft `StorageException` bei JSON-Fehlern
- **Migration erforderlich**: Try-Catch-Blöcke um ContentManager-Aufrufe implementieren

### Dokumentation
- **VALIDATION_IMPLEMENTATION.md**: Vollständige Dokumentation des Validierungssystems
  - API-Beschreibung aller Validator-Methoden
  - Sicherheitsverbesserungen und Beispiele
  - Migration-Guide für Breaking Changes
  - Performance-Hinweise und Best Practices

### Technische Details
- Alle neuen Klassen nutzen `declare(strict_types=1)` für Type-Safety
- Exception-Klassen mit Factory-Methoden für aussagekräftige Fehlermeldungen
- Validierung erfolgt fail-fast (vor Dateisystem-Operationen)
- UTF-8-Operationen via `mb_*` Funktionen
- Regex-Patterns optimiert für Performance

---

## [202511072246-dev]

### Hinzugefügt
- **GitHub Actions CI/CD Pipeline**: Vollautomatisierte Release-Erstellung
  - `release.yml`: Automatischer Build bei `-stable` Tags
  - `manual-release.yml`: Manueller Build für Dev/Beta/Alpha Releases
  - Automatische Bootstrap und Bootstrap Icons Installation
  - GitHub Release-Erstellung mit ZIP und MD5
  - Build-Artifacts mit 90 Tage Aufbewahrung
- **CI/CD Dokumentation**: Vollständige Pipeline-Anleitung
  - `docs/ci-cd-pipeline.md`: Detaillierte Workflow-Dokumentation
  - `.github/workflows/README.md`: Workflow-Übersicht
  - Tag-Konventionen und Best Practices

### Geändert
- **Release-Workflow**: Nur `-stable` Tags lösen automatische Builds aus
  - Development, Beta, Alpha via manuellen Workflow
  - Pre-Release Status manuell steuerbar
- **README.md Branding**: Logo zentriert für bessere Darstellung

---

## [202511072230-dev]

### Hinzugefügt
- **README-Branding**: fCMS-Logo in README.md für besseres Branding
  - Responsive Logo mit Dark/Light-Mode-Support (`<picture>` Element)
  - Logo-Dateien: `docs/img/logo-dunkel.svg` und `docs/img/logo-hell.svg`
  - Professional presentation with 200px width

### Dokumentation
- **Docker-First-Approach**: Alle Dokumentationen auf Docker als primäre Entwicklungsoption aktualisiert
  - `development-setup.md`: Docker Option 1, PHP Built-in Server Option 2
  - Docker-Befehle und Container-Management dokumentiert
- **Bootstrap-Klarstellungen**: Bootstrap-Abhängigkeiten korrekt dargestellt
  - Admin-Bereich: Bootstrap zwingend erforderlich
  - Themes: Bootstrap optional (Default-Theme als Beispiel)
  - Eigene CSS-Frameworks (Tailwind, Foundation, etc.) explizit als Alternative erwähnt
- **Strukturelle Verbesserungen**: Alle Dokumentationen auf aktuellen Projektstand
  - `theme-development.md`: CSS-Framework-Flexibilität hervorgehoben
  - `architecture.md`: Frontend-Sektion in Admin/Themes aufgeteilt
  - `docs/README.md`: Bootstrap-Referenzen entfernt/korrigiert
  - README.md Struktur bereinigt (doppelte Inhalte entfernt)

### Geändert
- **README.md Struktur**: Aufgeräumtes Layout mit klarer Hierarchie
  - Inhaltsverzeichnis mit korrekten Verlinkungen
  - Hauptmerkmale prominent platziert
  - Konsistente Badge-Darstellung (Version, PHP, License)

---

## [202511071820-dev]

### Behoben
- **ModuleManager API**: Fehler bei Asset-Loading behoben
  - `renderAdminTemplate()` verwendete nicht-existierende Methode `getLoadedModules()`
  - Verwendet jetzt korrekt `getActiveModules()` und `getModule($name)`
  - Iteriert über Namen der aktiven Module und holt Instanzen einzeln

### Technische Details
- Modul-Asset-Loading nutzt jetzt die vorhandene ModuleManager-API
- Code: `foreach ($moduleManager->getActiveModules() as $moduleName)` → `$module = $moduleManager->getModule($moduleName)`

---

## [202511071800-dev]

### Hinzugefügt
- **Modul-Asset-System**: Automatisches Laden von Modul-Assets im Admin-Bereich
  - `getAdminAssets()` Methode in `AbstractModule` für CSS/JS-Registrierung
  - Admin-Template lädt automatisch alle Assets von aktiven Modulen
  - `renderAdminTemplate()` sammelt Assets von allen Modulen via ModuleManager
- **Block-Editor Registry-System**: Generischer Mechanismus für Custom-Block-Renderer
  - `window.blockEditorRenderers` als globales Registry-Object
  - Core Block-Editor prüft Registry für unbekannte Block-Typen
  - Module können eigene Block-Renderer registrieren ohne Core-Code zu ändern
- **FormBuilder Modul v202511071800-dev**: Vollständige Modul-Unabhängigkeit erreicht
  - `form-block-editor.js` registriert Form-Block-Renderer im Registry
  - `README-BLOCK-EDITOR.md` dokumentiert Registry-Pattern
  - API-Endpunkt `/admin/api/forms/available` für dynamisches Laden

### Geändert
- **Block-Editor Core**: Form-spezifischer Code entfernt
  - ❌ Entfernt: `case 'form':` und `renderFormBlockEditor()` aus `block-editor.js`
  - ✅ Hinzugefügt: Generischer `default:` Case mit Registry-Check
  - Core kennt jetzt nur noch Standard-Blöcke (paragraph, heading, image, quote, list)
- **FormBlock**: Selbstständiges Beispiel-Rendering
  - Block erkennt `/admin/blocks` Route und zeigt Info-Box
  - Keine modulspezifische Beispiel-Logik mehr in `admin.php`
- **Admin-Template Layout**: Modul-Assets werden automatisch eingebunden
  - CSS-Assets im `<head>` nach Admin-CSS
  - JS-Assets vor schließendem `</body>` nach Admin-JS

### Architektur-Verbesserung
- **Vollständige Modul-Kapselung**: Kein modulspezifischer Code mehr im Core
  - Vorher: `block-editor.js` enthielt Form-spezifische Logik
  - Nachher: Core bietet generisches Registry, Module registrieren sich selbst
- **Saubere Trennung**: Klare Schnittstelle zwischen Core und Modulen
  - Core: Bietet Registry-Mechanismus und Asset-Loading-System
  - Module: Registrieren eigene Renderer und definieren eigene Assets
- **Erweiterbarkeit**: Andere Module können genauso Custom-Block-Renderer registrieren

### Technische Details
- Registry-Pattern für Block-Renderer über `window.blockEditorRenderers[blockType]`
- Module definieren Assets via `getAdminAssets()` → `['css' => [...], 'js' => [...]]`
- Asset-Pfade relativ zu Document-Root (z.B. `/modules/form-builder/assets/js/...`)

---

## [202511071410-dev]

### Hinzugefügt
- **Modul-System**: Vollständiges Plugin-System zur Erweiterung von fCMS
  - `ModuleInterface`: Definiert Modul-Contract mit boot, activate, deactivate, install, uninstall
  - `AbstractModule`: Basis-Klasse mit Config-Loading, View-Rendering und Standard-Implementierungen
  - `ModuleManager`: Modul-Discovery, Aktivierung/Deaktivierung, Booting-System
  - Modul-Verwaltungs-UI unter `/admin/modules` mit Aktivierungs-/Deaktivierungs-Funktionen
  - Modul-Menü in Admin-Navigation mit Puzzle-Icon
  - Aktive Module werden in `/content/active-modules.json` gespeichert
- **Form-Builder Modul**: Erstes offizielles Modul - dynamischer Formular-Generator
  - Drag-and-Drop Formular-Editor mit visueller Feldverwaltung
  - Unterstützte Feldtypen: Text, Email, Textarea, Select, Checkbox, Radio
  - Formular-Übersicht unter `/admin/forms` mit Erstellungs-/Bearbeitungsfunktionen
  - Submission-Verwaltung unter `/admin/forms/submissions/{id}`
  - JSON-basierte Speicherung in `/content/forms/` und `/content/submissions/`
  - Validierungs-Optionen (required, min/max length, email-Validierung)
  - **FormBlock**: Content-Block zum Einbetten von Formularen in Seiten
  - **Frontend-Rendering**: Vollständige Formular-Darstellung mit AJAX-Submission
  - **E-Mail-Benachrichtigungen**: Automatischer E-Mail-Versand bei Formular-Einsendungen
  - **Submission-Handler**: POST-Endpoint `/form/submit/{id}` mit Validierung und Speicherung
  - Custom CSS-Styles für konsistente Formular-Darstellung im fCMS-Design
- **Block-Bibliothek**: Neue Admin-Seite `/admin/blocks` mit Übersicht aller verfügbaren Content-Blöcke inkl. Live-Beispiele, JSON-Syntax und Verwendungshinweise
- **Admin-Branding**: Komplettes CSS-Redesign im fCMS-Stil mit Anthrazit (#2D2E32), Hellblau (#3FA9F5) und Hellgrau (#F2F4F5)
- **Navigation Enhancement**: Gradient-Hintergrund, Glow-Effekte auf Icons und smooth Transitions für bessere UX
- **Cache-Busting**: Automatische Versionierung von CSS/JS-Assets basierend auf Datei-Änderungszeit (filemtime)
- **Navigation Management**: Neue Admin-Seite `/admin/navigation` zur Verwaltung der Hauptnavigation und Footer-Navigation
- **Settings Management**: Neue Admin-Seite `/admin/settings` zur Verwaltung systemweiter Einstellungen
- **SettingsManager**: Neue Core-Klasse für JSON-basierte Einstellungsverwaltung mit Dot-Notation-Unterstützung (`site.name`), Thread-Safe File-Locking
- **Custom Navigation Labels**: Möglichkeit, individuelle Menü-Bezeichnungen unabhängig vom Seitentitel zu setzen (`nav_label`)
- **Bootstrap Icons**: Vollständige lokale Integration von Bootstrap Icons v1.11.1 im Admin-Bereich
- **AdminAssetManager**: Cache-Busting-Funktionalität und `bootstrapIcons()` Methode

### Geändert
- **Asset-Struktur**: Vereinfacht - `/admin/assets/` entfernt, nur noch `/public/admin/assets/` (keine Duplikate mehr)
- **System-Fonts**: Verwendung von System-Font-Stack statt externen Google Fonts (datenschutzfreundlich)
- **Block-Editor Frontend**: Block-Rendering verwendet `section['data']` statt `section['attributes']`/`section['content']`
- **Block-Namespaces**: Alle Block-Klassen nutzen einheitlich `FCMS\Blocks`
- **Autoloading**: `blocks/` Verzeichnis zu composer.json hinzugefügt
- **Button-Styles**: Alle Buttons mit `!important` verstärkt, Icons erben Textfarbe
- **Config**: Admin-Pfad zeigt jetzt auf `/public/admin` statt `/admin`, `modules` Pfad hinzugefügt

### Behoben
- **Block-Editor Content**: Seiten zeigten nur Titel, aber keine Block-Editor-Inhalte (Datenstruktur-Mismatch)
- **Block-Kompatibilität**: ParagraphBlock, HeadingBlock und QuoteBlock unterstützen beide Datenformate
- **Navigation Labels**: Theme-Templates verwenden `navigation.label` mit Fallback auf Seitentitel
- **BlockRegistry**: `getAllBlocks()` Methode hinzugefügt (fehlte)

---

## [202511071200-dev]

### Hinzugefügt

#### Core-Features
- Dateibasiertes Content-Management-System ohne Datenbank-Abhängigkeit
- Session-basierte Authentifizierung mit CSRF-Schutz
- Login-Throttling gegen Brute-Force-Angriffe
- Sichere Session-Konfiguration (httponly, secure, samesite)

#### Asset-Management
- Separates Asset-System für Admin-Bereich (`/admin/assets/`)
- Separates Asset-System für Themes (`/themes/[theme]/assets/`)
- Strikte Trennung zwischen Admin- und Theme-Assets
- Eigene Bootstrap-Installation für Admin und jedes Theme

#### Block-System
- Modulares Block-System mit automatischer Block-Discovery
- Standard-Blöcke implementiert:
  - Absatz (ParagraphBlock)
  - Überschrift (HeadingBlock)
  - Bild (ImageBlock)
  - Liste (ListBlock)
  - Zitat (QuoteBlock)
  - Button (ButtonBlock)
- Block-Interface für einfache Erweiterbarkeit
- AbstractBlock-Basisklasse mit Helper-Methoden
- Block-Registry für zentrale Verwaltung

#### Theme-System
- Theme-Manager mit Template-Rendering
- Default-Theme mit Bootstrap 5
- Vollständig eigenständige Theme-Struktur
- Theme-Metadaten (theme.json)
- Asset-Helper-Methoden für Themes

#### Admin-Bereich
- Admin-Dashboard mit Statistiken
- Login-Seite mit CSRF-Schutz
- Admin-Layout mit Bootstrap 5
- Responsive Admin-Interface
- Block-Editor-JavaScript-Grundgerüst
- Drag-and-Drop-Unterstützung für Blöcke

#### Mehrsprachigkeit
- Language-Manager mit JSON-basierten Sprachdateien
- Deutsche Standard-Übersetzungen
- Fallback-Mechanismus zu Deutsch
- Erweiterbar für weitere Sprachen
- Caching von Sprachdateien

#### Content-Management
- ContentManager für JSON-basierte Datenspeicherung
- File-Locking für sichere gleichzeitige Zugriffe
- Seiten-Verwaltung (Erstellen, Bearbeiten, Löschen)
- Status-Verwaltung (Veröffentlicht/Entwurf)
- Navigation-Verwaltung (Hauptnavigation, Footer, versteckt)
- Automatische Slug-Generierung mit Umlaut-Unterstützung

#### Build-System
- Automatisches Build-Script (`build/build.php`)
- Release-ZIP-Erstellung ohne Dev-Dependencies
- Vendor-Optimierung (Tests, Docs entfernt)
- Datei-Filterung (nur Produktions-Dateien)
- MD5-Checksumme-Generierung
- Versionierung im Dateinamen

#### DSGVO-Konformität
- Keine Cookies im Frontend
- Keine externe Ressourcen (CDN, Google Fonts)
- Keine IP-Adressen in Logs
- Session-Cookies nur im Admin (technisch notwendig)
- Datenschutzkonforme Log-Rotation
- DSGVO-Dokumentation

#### Dokumentation
- Umfassende README.md auf Deutsch
- Separate Dokumentation für Endanwender und Entwickler
- Detaillierte Installations-Anleitung
- Theme-Entwicklungs-Guide
- Block-Entwicklungs-Guide
- Asset-Management-Erklärung
- Troubleshooting-Sektion
- DSGVO-Compliance-Dokumentation
- Build-Prozess-Dokumentation

#### Entwickler-Tools
- Composer-basiertes Entwicklungs-Setup
- PSR-4 Autoloading
- Dependency Injection Container (PHP-DI)
- Slim Framework 4 für Routing
- Symfony Components (Filesystem, YAML)
- Passwort-Hash-Generator (`generate-password.php`)

#### Konfiguration
- Umfassende Konfigurations-Datei (`config/config.php`)
- Beispiel-Konfiguration (`config.example.php`)
- Session-Konfiguration
- Admin-Credentials-Verwaltung
- Theme-Auswahl
- Debug-Modus
- DSGVO-Einstellungen

#### Sicherheit
- CSRF-Token-System für alle Admin-Formulare
- Session-Regeneration nach Login
- Session-Timeout (30 Minuten Inaktivität)
- Passwort-Hashing mit bcrypt
- Login-Versuche-Tracking
- Automatische Sperre nach fehlgeschlagenen Logins
- Sichere Cookie-Konfiguration
- .htaccess-Sicherheitsregeln

### Technische Details

#### Verwendete Frameworks/Libraries
- PHP 7.4+
- Slim Framework 4.12
- PHP-DI 7.0
- Symfony Filesystem 6.3
- Symfony YAML 6.3
- Bootstrap 5 (in Admin und Default-Theme)

#### Verzeichnisstruktur
```
/admin/           - Admin-Bereich mit eigenen Assets
/blocks/          - Block-Typen
/build/           - Build-System
/config/          - Konfigurationsdateien
/content/         - Inhalte (JSON)
/languages/       - Sprachdateien
/logs/            - Log-Dateien
/public/          - Web-Root
/releases/        - Build-Outputs
/src/             - Core-Klassen
/themes/          - Themes mit eigenen Assets
/vendor/          - Composer Dependencies
```

### Bekannte Einschränkungen

- Aktuell nur Single-User-Admin (ein Admin-Account)
- Keine Medien-Upload-Funktionalität implementiert (Vorbereitet)
- Keine Seiten-Listen-Ansicht im Admin (nur Dashboard)
- Keine Block-Editor-UI vollständig implementiert (JavaScript-Grundgerüst vorhanden)
- Bootstrap-Dateien müssen manuell heruntergeladen werden

### Migration

Initial Release - Keine Migration erforderlich

### Breaking Changes

Initial Release - Keine Breaking Changes

---

## Versionierungsschema

fCMS verwendet Timestamp-basierte Versionierung:

- **Format**: `YYYYMMDDhhmm-{dev|stable}`
- **Beispiel**: `202511071410-dev` = 7. November 2025, 14:10 Uhr, Development-Version
- **dev**: Entwicklungsversion (aktive Entwicklung)
- **stable**: Stabile Produktionsversion

## Links

- [Repository](https://github.com/sbstnmrwld/fCMS)
- [Issues](https://github.com/sbstnmrwld/fCMS/issues)
