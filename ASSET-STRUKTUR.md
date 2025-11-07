# Asset-Struktur in fCMS

## Wichtig: Assets liegen direkt im public/-Verzeichnis!

Da der Webserver nur auf das `public/`-Verzeichnis zugreifen kann, liegen alle Assets direkt dort.

## Verzeichnisstruktur

```
public/
├── admin/
│   └── assets/
│       ├── bootstrap/
│       │   ├── css/
│       │   └── js/
│       ├── bootstrap-icons/
│       ├── css/
│       │   └── admin.css
│       ├── js/
│       │   └── admin.js
│       ├── fonts/
│       └── images/
└── themes/
    └── default/
        └── assets/
            ├── bootstrap/
            │   ├── css/
            │   └── js/
            ├── css/
            ├── js/
            ├── fonts/
            └── images/
```

## Direkte Bearbeitung

Alle Assets werden direkt in `/public/` bearbeitet:
- Admin-Assets: `/public/admin/assets/`
- Theme-Assets: `/public/themes/[theme-name]/assets/`

**Kein Kopieren mehr nötig!** Änderungen sind sofort im Browser sichtbar (nach Hard-Refresh).

## Cache-Busting

Das System fügt automatisch Versions-Parameter zu Assets hinzu (z.B. `admin.css?v=1234567890`),
basierend auf der Datei-Änderungszeit. So wird sichergestellt, dass Browser immer die neueste
Version laden.

## Für Entwicklung

Während der Entwicklung können Sie ein Symlink erstellen:

```bash
# Admin-Assets
ln -s ../../admin/assets public/admin/assets

# Theme-Assets
ln -s ../../../themes/default/assets public/themes/default/assets
```

**Hinweis**: Symlinks funktionieren nicht auf allen Hosting-Providern!

## Für Produktion

Der Build-Prozess (`composer build`) kopiert automatisch alle Assets ins `public/`-Verzeichnis im Release-ZIP.
