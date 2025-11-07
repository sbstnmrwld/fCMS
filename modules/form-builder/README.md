# Form-Builder Modul

Das Form-Builder Modul erweitert fCMS um einen vollständigen Formular-Generator mit Drag-and-Drop-Editor, Submission-Verwaltung und E-Mail-Benachrichtigungen.

## ✨ Features

### 📝 Formular-Editor
- Visueller Drag-and-Drop-Editor
- 6 Feldtypen: Text, E-Mail, Textarea, Select, Checkbox, Radio
- Validierungs-Optionen (required, min/max length)
- Platzhalter-Texte und Labels
- Mehrfachauswahl-Felder (Checkbox, Radio, Select)

### 📊 Submission-Verwaltung
- Alle Formular-Einsendungen im Admin einsehbar
- Strukturierte Darstellung mit Zeitstempel und IP-Adresse
- JSON-basierte Speicherung in `/content/submissions/`

### 📧 E-Mail-Benachrichtigungen
- Automatischer E-Mail-Versand bei neuen Einsendungen
- Konfigurierbare Empfänger-Adresse
- Übersichtliche E-Mail-Formatierung mit allen Eingaben

### 🎨 Frontend-Integration
- **FormBlock**: Content-Block zum Einbetten in Seiten
- Responsive Design mit fCMS-Branding
- AJAX-basierte Submission (kein Seitenneuaufbau)
- Erfolgs- und Fehlermeldungen
- Automatische Integration in den Block-Editor

## 🔧 Modul-Architektur

### In sich abgeschlossen
Das Modul ist vollständig in sich abgeschlossen und benötigt keine Änderungen am fCMS-Core:

- **FormBuilderModule.php**: Haupt-Modul-Klasse, registriert Routen und Block
- **FormBlock.php**: Block-Implementation für Frontend und Editor
- **API-Endpunkte**: `/admin/api/forms/available` für Block-Editor
- **Views**: Alle Admin-Templates im `views/`-Verzeichnis
- **Assets**: CSS und JavaScript im `assets/`-Verzeichnis

### Dynamische Integration
- Automatische Registrierung des FormBlocks beim Modul-Boot
- API-Endpunkt für verfügbare Formulare (wird vom Block-Editor verwendet)
- Keine Abhängigkeiten zu Core-Dateien außer AbstractModule

## 📦 Installation

1. Modul aktivieren unter **Admin → Module**
2. Formular-Menü erscheint automatisch in der Admin-Navigation
3. FormBlock wird automatisch im Block-Editor verfügbar

## 🚀 Verwendung

### Formular erstellen

1. Gehe zu **Admin → Formulare**
2. Klicke auf **Neues Formular erstellen**
3. Fülle die Grundeinstellungen aus:
   - **Titel**: Name des Formulars
   - **Beschreibung**: Optionale Beschreibung (wird über dem Formular angezeigt)
4. Füge Felder hinzu:
   - Klicke auf **Feld hinzufügen**
   - Wähle den Feldtyp
   - Konfiguriere Label, Name und Validierung
   - Für Select/Checkbox/Radio: Optionen kommagetrennt eingeben
5. Konfiguriere Submit-Einstellungen:
   - Button-Text (Standard: "Absenden")
   - Erfolgs-Nachricht
6. Optional: E-Mail-Benachrichtigung aktivieren
   - Haken bei "E-Mail bei Eingabe senden" setzen
   - Empfänger-E-Mail-Adresse eingeben
7. Klicke auf **Formular speichern**

### Formular in Seite einbetten

#### Variante 1: FormBlock verwenden (empfohlen)
1. Öffne eine Seite im Editor
2. Füge einen **Formular-Block** hinzu
3. Wähle das gewünschte Formular aus der Dropdown-Liste
4. Speichere die Seite

#### Variante 2: Manuell in Theme-Template
```php
<?php
$formBlock = new \fCMS\Modules\FormBuilder\FormBlock();
echo $formBlock->render(['formId' => 'deine-formular-id']);
?>
```

### Eingaben verwalten

1. Gehe zu **Admin → Formulare**
2. Klicke bei einem Formular auf **Eingaben anzeigen**
3. Alle Submissions werden chronologisch aufgelistet

## Technische Details

### Dateistruktur
```
modules/form-builder/
├── FormBuilderModule.php    # Haupt-Modul-Klasse
├── FormBlock.php             # Content-Block für Frontend
├── module.json               # Modul-Metadaten
├── assets/
│   └── form-styles.css       # Frontend-Styles
└── views/
    └── admin/
        ├── forms-list.php     # Formular-Übersicht
        ├── form-editor.php    # Formular-Editor
        └── submissions.php    # Submissions-Ansicht
```

### Routen

**Admin-Routen** (authentifiziert):
- `GET /admin/forms` - Formular-Übersicht
- `GET /admin/forms/create` - Neues Formular
- `GET /admin/forms/edit/{id}` - Formular bearbeiten
- `POST /admin/forms/save` - Formular speichern
- `POST /admin/forms/delete/{id}` - Formular löschen
- `GET /admin/forms/submissions/{id}` - Submissions anzeigen

**Öffentliche Routen**:
- `POST /form/submit/{id}` - Formular-Submission-Handler (AJAX)

### Datenstruktur

**Formular-Datei** (`/content/forms/{id}.json`):
```json
{
  "id": "kontakt",
  "title": "Kontaktformular",
  "description": "Schreiben Sie uns eine Nachricht",
  "fields": [
    {
      "type": "text",
      "name": "name",
      "label": "Ihr Name",
      "required": true,
      "placeholder": "Max Mustermann"
    },
    {
      "type": "email",
      "name": "email",
      "label": "E-Mail-Adresse",
      "required": true
    }
  ],
  "submit_text": "Absenden",
  "success_message": "Vielen Dank für Ihre Nachricht!",
  "email_notification": "admin@example.com"
}
```

**Submission-Datei** (`/content/submissions/{form-id}/{submission-id}.json`):
```json
{
  "id": "sub_12345",
  "form_id": "kontakt",
  "timestamp": "2025-11-07 14:30:00",
  "ip": "192.168.1.1",
  "user_agent": "Mozilla/5.0...",
  "data": {
    "name": "Max Mustermann",
    "email": "max@example.com",
    "message": "Hallo..."
  }
}
```

## Validierung

Das Modul validiert Eingaben serverseitig:

- **Required-Felder**: Müssen ausgefüllt sein
- **E-Mail**: Prüfung auf gültiges E-Mail-Format
- **Längen-Beschränkungen**: Min/Max-Länge für Text-Felder

## Sicherheit

- **CSRF-Schutz**: Alle Admin-Aktionen sind durch CSRF-Tokens geschützt
- **Input-Sanitization**: Alle Eingaben werden escaped
- **XSS-Schutz**: HTML-Entities werden in Ausgaben escaped
- **File-Validierung**: Formular- und Submission-IDs werden durch `basename()` gesichert

## E-Mail-Versand

E-Mails werden mit der PHP `mail()`-Funktion versendet. Stellen Sie sicher, dass:
- Ein Mail-Server (z.B. Sendmail, Postfix) auf dem Server konfiguriert ist
- Die `mail()`-Funktion nicht deaktiviert ist
- SPF/DKIM-Records für die Absender-Domain eingerichtet sind (optional, für bessere Zustellbarkeit)

## Anpassungen

### Custom Styling
Die Formular-Styles können über `/modules/form-builder/assets/form-styles.css` angepasst werden oder durch Theme-eigenes CSS überschrieben werden.

### E-Mail-Templates
Die E-Mail-Formatierung kann in `FormBuilderModule::sendEmailNotification()` angepasst werden.

### Neue Feldtypen
Neue Feldtypen können hinzugefügt werden, indem:
1. Der Feldtyp in `form-editor.php` zur Dropdown-Liste hinzugefügt wird
2. Das Rendering in `FormBlock::renderField()` implementiert wird
3. Die Validierung in `FormBuilderModule::validateSubmission()` erweitert wird

## Lizenz

Teil von fCMS - siehe Hauptprojekt-Lizenz
