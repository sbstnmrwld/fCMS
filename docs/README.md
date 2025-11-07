# fCMS Dokumentation

Willkommen zur fCMS-Dokumentation! Hier finden Sie alle Informationen zur Installation, Verwendung und Entwicklung.

## 📚 Dokumentations-Struktur

### Für Endanwender

| Dokument | Beschreibung |
|----------|-------------|
| [Installation](installation.md) | Schritt-für-Schritt Installationsanleitung |
| [Admin-Bereich](admin-guide.md) | Vollständiges Handbuch für den Admin-Bereich |
| [Konfiguration](configuration.md) | Einstellungen und Konfigurationsoptionen |
| [DSGVO-Konformität](dsgvo.md) | Datenschutz und rechtliche Aspekte |
| [Troubleshooting](troubleshooting.md) | Häufige Probleme und Lösungen |

### Für Entwickler

| Dokument | Beschreibung |
|----------|-------------|
| [Entwicklungs-Setup](development-setup.md) | Lokale Entwicklungsumgebung einrichten |
| [Architektur](architecture.md) | System-Architektur und Konzepte |
| [Theme-Entwicklung](theme-development.md) | Eigene Themes erstellen |
| [Block-Entwicklung](block-development.md) | Custom Blöcke entwickeln |
| [Modul-Entwicklung](module-development.md) | Eigene Module erstellen |
| [Asset-Management](asset-management.md) | CSS/JS richtig einbinden |
| [Build-Release](build-release.md) | Produktions-Build erstellen |

## 🚀 Schnelleinstieg

### Neuinstallation

1. **Download**: Laden Sie die neueste Version herunter
2. **Entpacken**: Entpacken Sie die ZIP-Datei
3. **Konfigurieren**: `config/config.php` anpassen
4. **Hochladen**: Alle Dateien per FTP hochladen
5. **Fertig**: Website besuchen und anmelden

👉 [Detaillierte Installationsanleitung](installation.md)

### Erste Seite erstellen

1. Im Admin-Bereich anmelden
2. Zu "Seiten" navigieren
3. "Neue Seite erstellen" klicken
4. Titel eingeben und Blöcke hinzufügen
5. Speichern

👉 [Admin-Bereich Handbuch](admin-guide.md)

### Eigenes Theme erstellen

1. Neuen Ordner in `themes/` erstellen
2. `theme.json` und Templates anlegen
3. Assets hinzufügen (CSS Framework optional)
4. Theme in Config aktivieren

👉 [Theme-Entwicklung Guide](theme-development.md)

## 🆘 Hilfe benötigt?

- 📖 [Troubleshooting](troubleshooting.md) - Häufige Probleme
- 💬 [GitHub Discussions](https://github.com/IhrRepo/fCMS/discussions) - Community
- 🐛 [GitHub Issues](https://github.com/IhrRepo/fCMS/issues) - Fehler melden

## 📋 Systemanforderungen

- **PHP**: 7.4+ (8.0+ empfohlen)
- **Webserver**: Apache mit mod_rewrite oder Nginx
- **Speicherplatz**: Mind. 50 MB
- **Keine Datenbank erforderlich!**

## 🔍 Was suchen Sie?

### Installation & Setup
- [Erste Installation](installation.md#installation)
- [Passwort ändern](configuration.md#passwort-hash-generieren)
- [Nginx konfigurieren](installation.md#nginx-konfiguration)
- [Docker-Entwicklung](development-setup.md#docker-entwicklung)

### Admin-Bereich
- [Seiten erstellen](admin-guide.md#neue-seite-erstellen)
- [Block-Editor verwenden](admin-guide.md#block-editor-verwenden)
- [Navigation einrichten](admin-guide.md#navigation-verwalten)
- [Bilder hochladen](admin-guide.md#bilder-hochladen)

### Entwicklung
- [Lokales Setup](development-setup.md)
- [Theme erstellen](theme-development.md)
- [Block erstellen](block-development.md)
- [Modul erstellen](module-development.md)

### Rechtliches
- [DSGVO-Konformität](dsgvo.md)
- [Datenschutzerklärung](dsgvo.md#datenschutzerklärung)
- [Cookie-Hinweis](dsgvo.md#admin-bereich)

## 📝 Beispiele

### Beispiel-Seite mit Blöcken

```json
{
  "title": "Über uns",
  "slug": "ueber-uns",
  "status": "published",
  "sections": [
    {
      "type": "heading",
      "data": {
        "text": "Willkommen",
        "level": 2
      }
    },
    {
      "type": "paragraph",
      "data": {
        "text": "Dies ist ein Beispiel-Text."
      }
    }
  ]
}
```

### Beispiel-Modul

```php
<?php
namespace FCMS\\Modules;

class MeinModul extends AbstractModule {
    public function boot($app, $container) {
        // Modul-Logik
    }
}
```

## 🎯 Best Practices

✅ **DO:**
- HTTPS verwenden
- Passwörter sicher speichern
- Bilder optimieren
- Module aktuell halten

❌ **DON'T:**
- Debug-Modus in Produktion
- Standard-Passwort verwenden
- Externe CDNs (DSGVO!)
- Schreibrechte 777

## 📚 Weiterführende Ressourcen

- [Changelog](../CHANGELOG.md) - Versionshistorie
- [License](../LICENSE) - MIT-Lizenz
- [GitHub Repository](https://github.com/IhrRepo/fCMS)

## 🤝 Beitragen

Möchten Sie zur Dokumentation beitragen?

1. Fork des Repositories
2. Dokumentation bearbeiten/erweitern
3. Pull Request erstellen

Siehe [CONTRIBUTING.md](../CONTRIBUTING.md) für Details.

---

**Entwickelt mit ❤️ für kleine Organisationen und Fördervereine**

Version 202511072246-dev | [Zurück zur Hauptseite](../README.md)
