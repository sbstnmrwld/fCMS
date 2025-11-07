# Changelog

Alle wichtigen Änderungen an fCMS werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).
Versionierung: `YYYYMMDDhhmm-{dev|stable}` (Timestamp-basiert)

## [202511071410-dev]

### Hinzugefügt
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
- **Config**: Admin-Pfad zeigt jetzt auf `/public/admin` statt `/admin`

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
