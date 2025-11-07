# Admin-Bereich Benutzerhandbuch

Lernen Sie, wie Sie den fCMS Admin-Bereich effektiv nutzen, um Ihre Website zu verwalten.

## Anmeldung

1. Rufen Sie auf: `https://ihre-domain.de/admin`
2. Geben Sie Ihren Benutzernamen und Passwort ein
3. Klicken Sie auf "Anmelden"

## Dashboard

Nach der Anmeldung sehen Sie das Dashboard mit einer Übersicht über:

- **Seiten gesamt**: Anzahl aller Seiten
- **Veröffentlicht**: Anzahl der öffentlichen Seiten
- **Entwürfe**: Anzahl der unveröffentlichten Seiten
- **Letzte Änderungen**: Kürzlich bearbeitete Seiten

## Seiten verwalten

### Neue Seite erstellen

1. Klicken Sie im Menü auf **"Seiten"**
2. Klicken Sie auf **"Neue Seite erstellen"**
3. Geben Sie einen **Titel** ein
4. (Optional) Geben Sie einen **URL-Slug** ein
   - Wird automatisch aus dem Titel generiert
   - Manuell anpassbar für SEO
5. Fügen Sie **Inhalte** mit dem Block-Editor hinzu
6. Wählen Sie einen **Status**:
   - **Veröffentlicht**: Seite ist öffentlich sichtbar
   - **Entwurf**: Seite ist nur im Admin sichtbar
7. Klicken Sie auf **"Speichern"**

### Block-Editor verwenden

Der Block-Editor ermöglicht es Ihnen, Inhalte aus verschiedenen Block-Typen zusammenzustellen.

#### Verfügbare Block-Typen

**Absatz**
- Einfacher Fließtext
- Ideal für normale Textabschnitte

**Überschrift**
- H1 bis H6 Überschriften
- Strukturieren Sie Ihre Inhalte

**Bild**
- Bilder mit optionaler Beschriftung
- Alt-Text für Barrierefreiheit

**Liste**
- Nummerierte Listen
- Aufzählungslisten (Bullet Points)

**Zitat**
- Hervorgehobene Zitate
- Optional mit Autor-Angabe

**Button**
- Call-to-Action Buttons
- Verschiedene Styles (Primary, Secondary, Success, Danger, etc.)

**Formular** (falls FormBuilder-Modul aktiv)
- Einbindung von Formularen
- Aus Formular-Bibliothek wählen

#### Block hinzufügen

1. Klicken Sie auf **"Block hinzufügen"** oder das **+** Symbol
2. Wählen Sie den gewünschten Block-Typ
3. Der Block wird am Ende der Seite hinzugefügt

#### Block bearbeiten

1. Klicken Sie auf den Block
2. Bearbeiten Sie die Inhalte in den Eingabefeldern
3. Änderungen werden automatisch im JSON gespeichert

#### Block verschieben

1. Klicken Sie auf das **Verschieben-Symbol** (⋮⋮) am Block
2. Ziehen Sie den Block an die gewünschte Position
3. Lassen Sie die Maustaste los

#### Block löschen

1. Klicken Sie auf das **Papierkorb-Symbol** am Block
2. Der Block wird sofort gelöscht

### Seite bearbeiten

1. Gehen Sie zu **"Seiten"**
2. Klicken Sie auf **"Bearbeiten"** bei der gewünschten Seite
3. Nehmen Sie Ihre Änderungen vor
4. Klicken Sie auf **"Speichern"**

### Seite löschen

1. Gehen Sie zu **"Seiten"**
2. Klicken Sie auf **"Löschen"** bei der gewünschten Seite
3. Bestätigen Sie die Löschung

⚠️ **Achtung**: Gelöschte Seiten können nicht wiederhergestellt werden!

## Navigation verwalten

### Hauptnavigation einrichten

1. Gehen Sie zu **"Navigation"**
2. Sie sehen eine Liste aller Seiten
3. Aktivieren Sie **"In Hauptnavigation anzeigen"** für Seiten, die im Menü erscheinen sollen
4. Geben Sie optional einen **Menü-Titel** ein (abweichend vom Seitentitel)
5. Legen Sie die **Position** fest (niedrigere Zahl = weiter vorne)
6. Klicken Sie auf **"Speichern"**

### Footer-Navigation einrichten

1. Gehen Sie zu **"Navigation"**
2. Aktivieren Sie **"In Footer anzeigen"** für Seiten, die in der Fußzeile erscheinen sollen
3. Typische Footer-Seiten:
   - Impressum
   - Datenschutz
   - Kontakt
   - AGB

## Medien verwalten

### Bilder hochladen

1. Gehen Sie zu **"Medien"**
2. Klicken Sie auf **"Datei hochladen"**
3. Wählen Sie eine Bilddatei von Ihrem Computer
4. Das Bild erscheint in der Medien-Bibliothek

**Unterstützte Formate**:
- JPG/JPEG
- PNG
- GIF
- SVG
- WebP

**Maximale Dateigröße**: 5 MB (konfigurierbar)

### Bilder verwenden

1. Erstellen oder bearbeiten Sie eine Seite
2. Fügen Sie einen **Bild-Block** hinzu
3. Geben Sie die Bild-URL ein:
   - Format: `/content/media/ihr-bild.jpg`
   - Oder kopieren Sie die URL aus der Medien-Bibliothek
4. Geben Sie einen **Alt-Text** ein (wichtig für Barrierefreiheit!)
5. Optional: Fügen Sie eine **Bildunterschrift** hinzu

### Medien löschen

1. Gehen Sie zu **"Medien"**
2. Klicken Sie auf **"Löschen"** beim gewünschten Bild
3. Bestätigen Sie die Löschung

⚠️ **Achtung**: Prüfen Sie vorher, ob das Bild noch auf Seiten verwendet wird!

## Blöcke-Bibliothek

Die Blöcke-Bibliothek zeigt Ihnen alle verfügbaren Block-Typen mit Beispielen.

1. Gehen Sie zu **"Blöcke"**
2. Durchsuchen Sie die verfügbaren Blöcke
3. Sehen Sie Live-Beispiele
4. Kopieren Sie JSON-Syntax für erweiterte Nutzung

## Module verwalten

Falls Module installiert sind:

1. Gehen Sie zu **"Module"**
2. Sie sehen alle verfügbaren Module
3. **Aktivieren**: Klicken Sie auf "Aktivieren" bei einem Modul
4. **Deaktivieren**: Klicken Sie auf "Deaktivieren" bei einem aktiven Modul

**Aktive Module**:
- Werden automatisch beim Start geladen
- Können eigene Routen und Funktionen hinzufügen
- Können eigene Blöcke registrieren

Siehe [Modul-Entwicklung](module-development.md) für mehr Details.

## Formulare verwalten (FormBuilder-Modul)

Falls das FormBuilder-Modul aktiv ist:

### Neues Formular erstellen

1. Gehen Sie zu **"Formulare"**
2. Klicken Sie auf **"Neues Formular"**
3. Geben Sie einen **Titel** ein
4. Fügen Sie **Felder** hinzu:
   - Text (einzeilig)
   - E-Mail
   - Textarea (mehrzeilig)
   - Select (Dropdown)
   - Checkbox
   - Radio (Auswahl)
5. Konfigurieren Sie jedes Feld:
   - Label (Beschriftung)
   - Name (technischer Name)
   - Pflichtfeld
   - Platzhalter
6. Passen Sie **Einstellungen** an:
   - Submit-Button-Text
   - Erfolgs-Nachricht
   - E-Mail-Benachrichtigung
7. Klicken Sie auf **"Speichern"**

### Formular einbinden

1. Bearbeiten Sie eine Seite
2. Fügen Sie einen **Formular-Block** hinzu
3. Wählen Sie das gewünschte Formular aus der Liste
4. Speichern Sie die Seite
5. Das Formular erscheint auf der Seite

### Formular-Einsendungen ansehen

1. Gehen Sie zu **"Formulare"**
2. Klicken Sie auf **"Eingaben"** beim gewünschten Formular
3. Sie sehen alle Einsendungen mit:
   - Zeitpunkt
   - Übermittelte Daten
   - IP-Adresse (falls aktiviert)

## Einstellungen

### Website-Einstellungen

1. Gehen Sie zu **"Einstellungen"**
2. Passen Sie an:
   - **Website-Name**: Wird im Browser-Tab angezeigt
   - **Website-Beschreibung**: Meta-Beschreibung für SEO
   - **Sprache**: Sprache der Benutzeroberfläche
   - **Zeitzone**: Für Zeitstempel in Logs und Formularen

### Theme-Einstellungen

1. Gehen Sie zu **"Einstellungen"** → **"Theme"**
2. Wählen Sie ein aktives Theme
3. Theme-spezifische Optionen (falls vorhanden)

Siehe [Theme-Entwicklung](theme-development.md) für eigene Themes.

## Best Practices

### Seiten-Struktur

✅ **DO:**
- Verwenden Sie aussagekräftige Titel
- Strukturieren Sie Inhalte mit Überschriften (H2, H3)
- Verwenden Sie Absätze für bessere Lesbarkeit
- Fügen Sie Alt-Texte bei Bildern hinzu

❌ **DON'T:**
- Keine H1-Überschriften in Blöcken (wird automatisch als Seitentitel verwendet)
- Keine zu langen Absätze (schwer lesbar)
- Keine Bilder ohne Alt-Text (schlecht für Barrierefreiheit)

### Navigation

✅ **DO:**
- Maximal 7 Hauptmenü-Punkte
- Logische Reihenfolge
- Kurze, prägnante Menü-Titel
- Wichtigste Seiten zuerst

❌ **DON'T:**
- Keine verschachtelten Menüs (wird nicht unterstützt)
- Keine zu langen Menü-Titel
- Nicht alle Seiten ins Hauptmenü

### Medien

✅ **DO:**
- Bilder optimieren (max. 1-2 MB)
- WebP verwenden für moderne Browser
- Aussagekräftige Dateinamen
- Alt-Texte nicht vergessen

❌ **DON'T:**
- Keine riesigen Bilder (5+ MB)
- Keine Leerzeichen in Dateinamen
- Keine Bilder ohne Alt-Text

## Tastatur-Shortcuts

- `Strg/Cmd + S` - Seite speichern
- `Strg/Cmd + B` - Neuen Block hinzufügen (im Editor)
- `Esc` - Block-Auswahl schließen

## Sicherheit

### Passwort ändern

1. Bearbeiten Sie `config/config.php`
2. Generieren Sie einen neuen Hash mit `generate-password.php`
3. Ersetzen Sie den alten Hash
4. Löschen Sie `generate-password.php`

### Abmelden

- Klicken Sie auf Ihren Benutzernamen oben rechts
- Klicken Sie auf **"Abmelden"**
- Oder schließen Sie einfach den Browser-Tab

### Session-Timeout

Aus Sicherheitsgründen werden Sie automatisch nach **30 Minuten Inaktivität** abgemeldet.

## Nächste Schritte

- ⚙️ [Einstellungen & Konfiguration](configuration.md) - Detaillierte Konfiguration
- 🎨 [Theme-Entwicklung](theme-development.md) - Design anpassen
- 🔧 [Troubleshooting](troubleshooting.md) - Probleme lösen
