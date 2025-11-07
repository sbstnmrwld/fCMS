# Release-Pipeline

Automatisierte Release-Erstellung mit GitHub Actions.

## Übersicht

Das fCMS-Projekt verwendet GitHub Actions für automatisierte Release-Builds. Es gibt zwei Workflows:

1. **Automatischer Release** (`release.yml`) - Bei Git-Tags
2. **Manueller Release** (`manual-release.yml`) - Über GitHub UI

## Automatischer Release (Tag-basiert)

⚠️ **Nur für Stable-Releases**: Automatische Builds werden **nur** bei Tags ausgelöst, die auf `-stable` enden.

### Verwendung

```bash
# 1. Version und Changelog aktualisieren
# README.md, composer.json, CHANGELOG.md

# 2. Änderungen committen
git add .
git commit -m "Release v1.0.0-stable"

# 3. Tag mit -stable Suffix erstellen
git tag v1.0.0-stable

# 4. Tag pushen (triggert automatisch den Build)
git push origin v1.0.0-stable
```

### Wichtig: Tag-Naming für automatische Releases

- ✅ **Wird gebaut**: `v1.0.0-stable`, `v202511072230-stable`
- ❌ **Wird NICHT gebaut**: `v1.0.0`, `v1.0.0-dev`, `v1.0.0-beta`

Für Dev/Beta/Alpha Versionen verwenden Sie den **Manuellen Release** Workflow!

### Was passiert automatisch?

1. ✅ Checkout des Codes
2. ✅ PHP 8.3 Setup
3. ✅ Composer Dependencies (production only)
4. ✅ Bootstrap Download und Installation
5. ✅ Bootstrap Icons Download und Installation
6. ✅ Build-Script ausführen (`build/build.php`)
7. ✅ Build verifizieren
8. ✅ GitHub Release erstellen mit ZIP und MD5
9. ✅ Artifacts hochladen (90 Tage Aufbewahrung)

### Tag-Naming Convention

**Automatischer Release** (nur `-stable`):
- ✅ `v1.0.0-stable`, `v1.1.0-stable`, `v2.0.0-stable`
- ✅ `v202511072230-stable`

**Manueller Release** (alle anderen):
- 📝 `v1.0.0-dev`, `v202511072230-dev`
- 📝 `v1.0.0-beta`, `v1.0.0-beta.1`
- 📝 `v1.0.0-alpha`, `v1.0.0-alpha.1`
- 📝 Beliebige andere Versionen

**Wichtig:** Nur Tags mit `-stable` Suffix lösen automatische Releases aus!

## Manueller Release

Für **alle** nicht-stable Releases verwenden Sie den manuellen Workflow.

### Verwendung

1. Gehen Sie zu **Actions** → **Manual Release Build**
2. Klicken Sie auf **Run workflow**
3. Geben Sie ein:
   - **Version**: z.B. `1.0.0-dev`, `202511072230-dev`, `1.0.0-beta`
   - **Pre-Release**: Ja (für dev/beta/alpha), Nein (für stable)
4. Klicken Sie auf **Run workflow**

### Wann verwenden?

- ✅ **Development-Releases** (`-dev`)
- ✅ **Beta-Releases** (`-beta`)
- ✅ **Alpha-Releases** (`-alpha`)
- ✅ **Test-Releases**
- ✅ **Hot-Fixes** ohne Tag
- ✅ **Wenn automatischer Build fehlgeschlagen ist**
- ⚠️ **Stable-Releases** (wenn kein Tag möglich)

## Build-Prozess im Detail

### 1. Composer Dependencies

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

- Nur Produktions-Dependencies
- Optimierter Autoloader
- Keine Dev-Tools (PHPUnit, etc.)

### 2. Bootstrap Installation

**Admin-Bereich** (zwingend erforderlich):
```
public/admin/assets/bootstrap/
├── css/
│   ├── bootstrap.min.css
│   └── bootstrap.min.css.map
└── js/
    ├── bootstrap.bundle.min.js
    └── bootstrap.bundle.min.js.map
```

**Default-Theme** (optional für Themes):
```
public/themes/default/assets/bootstrap/
├── css/
└── js/
```

**Bootstrap Icons** (Admin):
```
public/admin/assets/bootstrap-icons/
├── bootstrap-icons.min.css
└── fonts/
```

### 3. Build-Script

Führt `build/build.php` aus:
- Filtert Dateien (ohne .git, tests, docs)
- Optimiert Vendor-Verzeichnis
- Erstellt ZIP-Archiv
- Generiert MD5-Checksumme

### 4. Output

**Erstellt Dateien:**
- `releases/fCMS-vX.X.X.zip` - Produktions-ZIP
- `releases/fCMS-vX.X.X.md5` - Checksumme

**GitHub Release:**
- ZIP und MD5 als Download
- Release-Notes automatisch generiert
- Link zu Changelog und Dokumentation

## Konfiguration anpassen

### Bootstrap-Version ändern

In `.github/workflows/release.yml`:

```yaml
- name: Download Bootstrap
  run: |
    # Andere Bootstrap-Version verwenden
    wget https://github.com/twbs/bootstrap/releases/download/v5.3.3/bootstrap-5.3.3-dist.zip
    unzip bootstrap-5.3.3-dist.zip
    # ...
```

### PHP-Version ändern

```yaml
- name: Setup PHP
  uses: shivammathur/setup-php@v2
  with:
    php-version: '8.2'  # Oder andere Version
```

### Build-Script erweitern

Bearbeiten Sie `build/build.php` um:
- Weitere Optimierungen
- Zusätzliche Checks
- Custom Post-Processing

## Troubleshooting

### Build schlägt fehl

**Logs anzeigen:**
1. Actions → fehlgeschlagener Workflow
2. Klicken Sie auf den fehlgeschlagenen Step
3. Lesen Sie die Fehlerausgabe

**Häufige Probleme:**

1. **Composer-Fehler**
   ```
   composer install --no-dev --no-interaction --ignore-platform-reqs
   ```

2. **Bootstrap-Download fehlgeschlagen**
   - Prüfen Sie URL und Version
   - GitHub Rate Limits?

3. **Build-Script Fehler**
   - Lokal testen: `php build/build.php test`
   - Berechtigungen prüfen

### Release wurde nicht erstellt

**Mögliche Ursachen:**

1. **Tag nicht gepusht**
   ```bash
   git push origin --tags
   ```

2. **Token-Berechtigungen**
   - Repository → Settings → Actions → General
   - Workflow permissions: "Read and write permissions"

3. **Workflow deaktiviert**
   - Actions → Workflows → Release aktivieren

## Lokaler Test

Testen Sie den Build lokal:

```bash
# Dependencies installieren
composer install --no-dev --optimize-autoloader

# Bootstrap manuell hinzufügen (oder setup-script)
./scripts/setup-bootstrap.sh

# Build ausführen
php build/build.php 1.0.0-test

# Verifizieren
ls -lh releases/
unzip -l releases/fCMS-v1.0.0-test.zip
```

## Best Practices

### Vor jedem Release

- [ ] Version in `README.md` aktualisiert
- [ ] Version in `composer.json` aktualisiert
- [ ] CHANGELOG.md gepflegt
- [ ] Tests lokal ausgeführt
- [ ] Lokaler Build getestet
- [ ] Dokumentation aktualisiert
- [ ] Branch `main` oder `stable`

### Tag-Workflow

```bash
# Stable Release (automatisch)
# ============================

# 1. Finale Änderungen committen
git add .
git commit -m "Release v1.0.0-stable: Version bump and changelog"

# 2. Tag mit -stable Suffix erstellen
git tag -a v1.0.0-stable -m "Release v1.0.0-stable"

# 3. Alles pushen
git push origin main
git push origin v1.0.0-stable

# 4. Build verfolgen
# GitHub → Actions → Create Release


# Development Release (manuell)
# ==============================

# 1. Version committen
git add .
git commit -m "Dev build v202511072230-dev"

# 2. (Optional) Tag erstellen für Nachvollziehbarkeit
git tag v202511072230-dev
git push origin v202511072230-dev

# 3. Manuellen Workflow starten
# GitHub → Actions → Manual Release Build
# Version: 202511072230-dev
# Pre-Release: Ja
```

### Nach dem Release

- [ ] GitHub Release verifizieren
- [ ] ZIP-Datei herunterladen und testen
- [ ] Installation testen
- [ ] Checksumme verifizieren
- [ ] Ankündigung schreiben

## Artefakte

Alle Builds werden 90 Tage als GitHub Artifacts gespeichert:

- Actions → Workflow → Run → Artifacts
- Download ZIP direkt ohne Release-Erstellung

## Weitere Workflows

### Test-Workflow (optional)

Erstellen Sie `.github/workflows/test.yml` für:
- Automatische Tests bei PRs
- Code-Style-Checks
- Syntax-Validierung

### Deploy-Workflow (optional)

Erstellen Sie `.github/workflows/deploy.yml` für:
- Automatisches Deployment auf Test-Server
- FTP/SFTP Upload
- Backup erstellen

## Weiterführend

- [GitHub Actions Dokumentation](https://docs.github.com/en/actions)
- [Release erstellen](https://docs.github.com/en/repositories/releasing-projects-on-github)
- [Build-Script](../build/build.php)
