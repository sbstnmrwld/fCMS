# fCMS Scripts

Dieses Verzeichnis enthält Hilfsskripte für Entwicklung und Testing.

## test.fish

Fish-Shell-Script zum Ausführen von PHPUnit-Tests mit verschiedenen Optionen.

### Installation

Das Script ist bereits ausführbar. Stelle sicher, dass du Fish-Shell installiert hast:

```bash
# macOS
brew install fish

# Ubuntu/Debian
apt-get install fish
```

### Verwendung

```bash
./scripts/test.fish [option]
```

### Verfügbare Optionen

| Option | Beschreibung |
|--------|-------------|
| `all` | Alle Tests ausführen (default) |
| `unit` | Nur Unit-Tests |
| `integration` | Nur Integration-Tests |
| `coverage` | Code-Coverage (HTML) generieren |
| `coverage-text` | Code-Coverage als Text ausgeben |
| `watch` | Tests im Watch-Mode (bei Dateiänderungen) |
| `filter <name>` | Spezifischen Test ausführen |
| `validator` | Nur Validator-Tests |
| `exceptions` | Nur Exception-Tests |
| `verbose` | Verbose Output |
| `debug` | Debug-Mode |
| `help` | Hilfe anzeigen |

### Beispiele

```bash
# Alle Tests ausführen
./scripts/test.fish

# Code-Coverage generieren und im Browser öffnen
./scripts/test.fish coverage

# Nur Validator-Tests
./scripts/test.fish validator

# Spezifischen Test ausführen
./scripts/test.fish filter testSlugRejectsPathTraversal

# Tests im Watch-Mode (benötigt fswatch)
./scripts/test.fish watch
```

### Watch-Mode

Der Watch-Mode überwacht Änderungen in `src/` und `tests/` und führt automatisch Tests aus.

**Voraussetzung:** `fswatch` muss installiert sein:

```bash
# macOS
brew install fswatch

# Ubuntu/Debian
apt-get install fswatch
```

### Features

- ✅ Farbige Ausgabe
- ✅ Übersichtliche Statusmeldungen
- ✅ Exit-Code-Weitergabe
- ✅ Automatisches Öffnen des Coverage-Reports (macOS)
- ✅ Watch-Mode für kontinuierliches Testing
- ✅ Filter-Unterstützung für einzelne Tests

### Ausgabe

Das Script zeigt einen formatierten Header und Statusmeldungen:

```
╔════════════════════════════════════════╗
║        fCMS Test Runner                ║
╚════════════════════════════════════════╝

→ Führe alle Tests aus...
✓ Alle Tests bestanden!
```

### Fehlerbehebung

**Problem:** `./scripts/test.fish: command not found`
```bash
# Lösung: Mache das Script ausführbar
chmod +x scripts/test.fish
```

**Problem:** `fish: command not found`
```bash
# Lösung: Installiere Fish-Shell
brew install fish  # macOS
```

**Problem:** `fswatch: command not found` (bei watch)
```bash
# Lösung: Installiere fswatch
brew install fswatch  # macOS
```

## Weitere Scripts (zukünftig)

Geplante Scripts:
- `build.fish` - Build-Automatisierung
- `deploy.fish` - Deployment-Helper
- `setup.fish` - Entwicklungsumgebung einrichten
