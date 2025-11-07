# Asset-Struktur in fCMS

## Wichtig: Assets müssen im public/-Verzeichnis liegen!

Da der Webserver nur auf das `public/`-Verzeichnis zugreifen kann, müssen alle Assets dort platziert werden.

## Verzeichnisstruktur

```
public/
├── admin/
│   └── assets/
│       ├── bootstrap/
│       │   ├── css/
│       │   └── js/
│       ├── css/
│       ├── js/
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

## Assets wurden kopiert

Die Assets wurden automatisch von:
- `/admin/assets/` → `/public/admin/assets/`
- `/themes/default/assets/` → `/public/themes/default/assets/`

## Für neue Themes

Wenn Sie ein neues Theme erstellen:

1. Erstellen Sie die Theme-Struktur in `/themes/ihr-theme/`
2. Kopieren Sie die Assets nach `/public/themes/ihr-theme/assets/`

Beispiel:
```bash
mkdir -p public/themes/mein-theme/assets
cp -r themes/mein-theme/assets/* public/themes/mein-theme/assets/
```

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
