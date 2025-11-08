# Changelog - FormBuilder Modul

Alle wichtigen Änderungen am FormBuilder Modul werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).
Versionierung: `YYYYMMDDhhmm-{dev|stable}` (Timestamp-basiert)

## [202511081600-dev] - 2025-11-08

### Behoben
- **FormBlock Frontend-Rendering**: FormBlock wird jetzt korrekt im Frontend angezeigt
  - `ModuleManager::discoverModules()` wird jetzt in `public/index.php` aufgerufen
  - FormBlock wird erfolgreich im BlockRegistry registriert
- **Formular-Submission**: Submit-Route funktioniert jetzt korrekt
  - `$self`-Referenz in `registerPublicRoutes()` implementiert
  - `handleSubmission()` ist jetzt public und von Closure aufrufbar
- **Fehlende Field-Namen**: Automatische Generierung von `name`-Attributen aus Labels
  - Verhindert leere `name`-Attribute bei Formularfeldern
- **Glob-Pattern**: Korrektur des Pfads in `FormBlock::renderEditor()` (fehlender Schrägstrich)

### Hinzugefügt
- **Module-Asset-Route**: Dynamisches Serving von Modul-Assets über `/modules/{module}/assets/{path}`
  - Sicherheitscheck gegen Directory Traversal
  - Unterstützung für CSS, JS, Bilder, Fonts
  - Content-Type-Detection für verschiedene Dateitypen
- **E-Mail-Benachrichtigungen**: Vollständige Implementation
  - Unterstützung für `settings.email_notification` und `settings.notification_email`
  - Mail-Logging unter `logs/mails/` für Development
  - Debug-Logging für Fehlersuche
- **Automatische Bereinigung**: Cleanup-System für alte Daten
  - Submissions: Nur die letzten 100 pro Formular werden behalten
  - Mail-Logs: Nur die letzten 100 Log-Dateien werden behalten
  - System-Logs: Nur die letzten 100 Log-Dateien werden behalten
  - Bereinigung erfolgt automatisch (Submissions bei jedem Submit, Logs gelegentlich beim Boot)

### Geändert
- `.gitignore`: Anpassungen für `content/submissions/*` und `logs/*`

## [202511071800-dev] - 2025-11-07

### Geändert
- **Vollständige Modul-Unabhängigkeit**: FormBuilder ist jetzt komplett unabhängig vom Core-System
  - Form-spezifischer Code aus `block-editor.js` entfernt
  - Neues **Registry-System** für Custom-Block-Renderer implementiert
  - Core Block-Editor nutzt `window.blockEditorRenderers` für Modul-Blöcke
- **Modul-Asset-System**: Automatisches Laden von Modul-Assets im Admin-Bereich
  - `getAdminAssets()` Methode in AbstractModule hinzugefügt
  - `form-block-editor.js` registriert Form-Block-Renderer im Registry
  - Admin-Template lädt automatisch CSS/JS von allen aktiven Modulen
- **FormBlock**: Beispiel-Rendering jetzt im Block selbst statt in admin.php
  - Block erkennt `/admin/blocks` Route und zeigt Info-Box mit Beschreibung
  - Keine modulspezifische Logik mehr im Core

### Hinzugefügt
- `modules/form-builder/assets/js/form-block-editor.js` - Editor-Integration für Form-Block
- `modules/form-builder/README-BLOCK-EDITOR.md` - Dokumentation des Registry-Systems
- API-Endpunkt `/admin/api/forms/available` für dynamisches Laden der Formulare

### Technische Details
- **Registry-Pattern**: Module registrieren eigene Block-Renderer über globales Object
- **Asset-Loading**: Module definieren ihre Assets selbst via `getAdminAssets()`
- **Saubere Trennung**: Core kennt keine modulspezifische Logik mehr
- **Erweiterbar**: Andere Module können genauso eigene Block-Renderer registrieren

### Architektur-Verbesserung
Vorher: Core-Code enthielt Form-spezifische Logik (Switch-Case, renderFormBlockEditor)
Nachher: Core bietet generisches Registry-System, Module registrieren sich selbst

## [202511071500-dev] - 2025-11-07

### Hinzugefügt
- **Formular-Builder Modul**: Vollständiges Modul zur Formular-Verwaltung
  - Drag-and-Drop Formular-Editor mit visueller Feldverwaltung
  - Unterstützte Feldtypen: Text, Email, Textarea, Select, Checkbox, Radio
  - Formular-Übersicht unter `/admin/forms`
  - Submission-Verwaltung und Speicherung
  - FormBlock für Content-Integration
  - Frontend-Rendering mit AJAX-Submission
  - E-Mail-Benachrichtigungen bei Einsendungen
  - CSRF-Schutz für alle Formulare

### Behoben
- CSRF-Token Variable in form-editor Template korrigiert
- Form-Block Registrierung im BlockRegistry

### Sicherheit
- CSRF-Validierung für Formular-Submissions
- Input-Sanitization für alle Formular-Daten
- File-Path Validation mit `basename()`
