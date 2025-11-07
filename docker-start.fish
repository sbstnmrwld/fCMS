#!/usr/bin/env fish
#
# fCMS - Docker Development Environment Starter
#

# Farben
set GREEN (set_color green)
set BLUE (set_color blue)
set YELLOW (set_color yellow)
set RED (set_color red)
set NORMAL (set_color normal)

echo ""
echo "$BLUE┌─────────────────────────────────────┐$NORMAL"
echo "$BLUE│    fCMS Docker Development Setup    │$NORMAL"
echo "$BLUE└─────────────────────────────────────┘$NORMAL"
echo ""

# Prüfe ob Docker läuft
if not docker info >/dev/null 2>&1
    echo "$RED✗ Docker läuft nicht!$NORMAL"
    echo "  Bitte starte Docker Desktop und versuche es erneut."
    exit 1
end

# Prüfe ob Container bereits läuft
if docker ps | grep -q fcms-web
    echo "$YELLOW⚠ Container läuft bereits$NORMAL"
    echo ""
    echo "Optionen:"
    echo "  1) Neu starten"
    echo "  2) Stoppen"
    echo "  3) Logs anzeigen"
    echo "  4) Abbrechen"
    echo ""
    read -P "Wähle (1-4): " choice

    switch $choice
        case 1
            echo "$BLUE→ Starte Container neu...$NORMAL"
            docker-compose restart
        case 2
            echo "$BLUE→ Stoppe Container...$NORMAL"
            docker-compose down
            echo "$GREEN✓ Container gestoppt$NORMAL"
            exit 0
        case 3
            echo "$BLUE→ Zeige Logs (Ctrl+C zum Beenden)$NORMAL"
            docker-compose logs -f
            exit 0
        case '*'
            exit 0
    end
else
    # Container starten
    echo "$BLUE→ Baue und starte Container...$NORMAL"
    docker-compose up -d --build
end

echo ""
echo "$GREEN✓ Container läuft$NORMAL"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "$GREEN🚀 fCMS ist verfügbar auf:$NORMAL"
echo "   $YELLOW""http://localhost:8000$NORMAL"
echo "   $YELLOW""http://localhost:8000/admin$NORMAL"
echo ""
echo "📋 Nützliche Befehle:"
echo "   docker-compose logs -f     # Logs anzeigen"
echo "   docker-compose down        # Container stoppen"
echo "   docker-compose restart     # Container neu starten"
echo "   docker-compose exec web bash  # In Container einloggen"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
