# ⚠️ WICHTIG: Fehlende Bootstrap-Assets

## Status der Implementierung

fCMS ist **strukturell vollständig** implementiert, aber **Bootstrap-Assets fehlen** in:

1. `/admin/assets/bootstrap/` (Admin-Bereich)
2. `/themes/default/assets/bootstrap/` (Default-Theme)

## Warum fehlen diese Dateien?

Bootstrap-Dateien werden nicht mit fCMS ausgeliefert aus folgenden Gründen:

1. **Lizenz-Compliance**: Bootstrap hat eigene Lizenzbedingungen
2. **Größe**: Bootstrap-Dateien würden das Repository unnötig aufblähen
3. **Versionskontrolle**: Sie können selbst die gewünschte Bootstrap-Version wählen
4. **Aktualität**: Sie können immer die neueste Version herunterladen

## Was müssen Sie tun?

### Option 1: Bootstrap herunterladen (Empfohlen)

1. **Besuchen Sie**: https://getbootstrap.com/docs/5.3/getting-started/download/
2. **Laden Sie herunter**: "Compiled CSS and JS"
3. **Entpacken Sie** die ZIP-Datei

4. **Kopieren Sie für Admin**:
   ```
   bootstrap/css/   → /admin/assets/bootstrap/css/
   bootstrap/js/    → /admin/assets/bootstrap/js/
   ```

5. **Kopieren Sie für Theme**:
   ```
   bootstrap/css/   → /themes/default/assets/bootstrap/css/
   bootstrap/js/    → /themes/default/assets/bootstrap/js/
   ```

### Option 2: CDN-Links verwenden (Entwicklung)

⚠️ **Nur für Entwicklung! Nicht DSGVO-konform für Produktion!**

In `admin/templates/layout.php` und `themes/default/templates/layout.php`:

```html
<!-- Statt: -->
<link href="<?= $assets->bootstrapCss() ?>" rel="stylesheet">

<!-- Verwenden Sie: -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
```

### Option 3: Alternative CSS-Frameworks

Sie können auch andere CSS-Frameworks verwenden:
- Tailwind CSS
- Bulma
- Foundation
- Oder eigene CSS-Dateien

## Verzeichnisstruktur mit Bootstrap

Nach dem Download sollte die Struktur so aussehen:

```
/admin/assets/bootstrap/
├── css/
│   ├── bootstrap.min.css
│   ├── bootstrap.min.css.map
│   └── ...
└── js/
    ├── bootstrap.bundle.min.js
    ├── bootstrap.bundle.min.js.map
    └── ...

/themes/default/assets/bootstrap/
├── css/
│   ├── bootstrap.min.css
│   ├── bootstrap.min.css.map
│   └── ...
└── js/
    ├── bootstrap.bundle.min.js
    ├── bootstrap.bundle.min.js.map
    └── ...
```

## Checklist vor dem ersten Start

- [ ] Composer Dependencies installiert (`composer install`)
- [ ] `config/config.php` erstellt und angepasst
- [ ] Bootstrap heruntergeladen
- [ ] Bootstrap in `/admin/assets/bootstrap/` kopiert
- [ ] Bootstrap in `/themes/default/assets/bootstrap/` kopiert
- [ ] Verzeichnisrechte gesetzt (`content/`, `logs/`)
- [ ] Admin-Passwort geändert

## Alternative: Minimales CSS erstellen

Falls Sie kein Bootstrap verwenden möchten, erstellen Sie minimale CSS-Dateien:

```css
/* admin/assets/css/admin.css */
/* Grundlegende Styles für Admin-Bereich */
body { font-family: system-ui, sans-serif; margin: 0; padding: 20px; }
.container { max-width: 1200px; margin: 0 auto; }
/* ... weitere Styles ... */
```

Und passen Sie die Templates an, um nicht auf Bootstrap-Klassen zu verweisen.

## Hinweis für Build-Prozess

Der Build-Prozess (`composer build`) funktioniert auch ohne Bootstrap-Dateien, aber das resultierende Release-ZIP wird dann ebenfalls ohne Bootstrap sein. Stellen Sie sicher, dass Sie Bootstrap kopiert haben, bevor Sie ein Release-ZIP erstellen.

## Support

Bei Fragen oder Problemen:
- GitHub Issues: https://github.com/IhrRepo/fCMS/issues
- README.md lesen: Siehe Abschnitt "Troubleshooting"

---

**Zusammenfassung**: fCMS ist code-technisch vollständig, Sie müssen nur Bootstrap-Dateien hinzufügen, um die volle Funktionalität zu erhalten.
