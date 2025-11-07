# GitHub Actions Workflows

Automatisierte CI/CD-Pipelines für fCMS.

## Verfügbare Workflows

### 1. Create Release (`release.yml`)

**Trigger:** Git-Tag mit `-stable` Suffix (z.B. `v1.0.0-stable`)

**Automatisch:**
- ✅ Build erstellen
- ✅ Bootstrap installieren
- ✅ GitHub Release erstellen
- ✅ ZIP und MD5 hochladen

**Verwendung:**
```bash
git tag v1.0.0-stable
git push origin v1.0.0-stable
```

⚠️ **Nur für stable Releases!** Andere Versionen über manuellen Workflow.

### 2. Manual Release Build (`manual-release.yml`)

**Trigger:** Manuell über GitHub UI

**Für:**
- 📝 Development Releases (`-dev`)
- 📝 Beta Releases (`-beta`)
- 📝 Alpha Releases (`-alpha`)
- 📝 Test Builds
- 📝 Hot-Fixes

**Features:**
- ⚙️ Version eingeben
- ⚙️ Pre-Release Ja/Nein
- ⚙️ Sofortiger Build

**Verwendung:**
1. Actions → Manual Release Build
2. Run workflow
3. Version eingeben (z.B. `202511072230-dev`)

## Dokumentation

Vollständige Anleitung: [CI/CD Pipeline](../docs/ci-cd-pipeline.md)

## Quick Start

```bash
# Stable Release (automatisch)
git tag v1.0.0-stable
git push origin v1.0.0-stable

# Development Release (manuell)
# GitHub → Actions → Manual Release Build
# Version: 202511072230-dev, Pre-Release: Ja

# Build verfolgen
# GitHub → Actions → Workflow-Name

# Release verifizieren
# GitHub → Releases
```

## Tag-Konventionen

- **Automatisch**: Nur `-stable` Tags (z.B. `v1.0.0-stable`)
- **Manuell**: Alle anderen (`-dev`, `-beta`, `-alpha`, etc.)
