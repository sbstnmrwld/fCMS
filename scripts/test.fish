#!/usr/bin/env fish

# fCMS Test Runner
# Führt PHPUnit-Tests mit verschiedenen Optionen aus

set -l script_dir (dirname (status --current-filename))
set -l project_root (realpath "$script_dir/..")

# Farben
set -l GREEN '\033[0;32m'
set -l YELLOW '\033[1;33m'
set -l RED '\033[0;31m'
set -l BLUE '\033[0;34m'
set -l NC '\033[0m' # No Color

function print_header
    echo -e "$BLUE"
    echo "╔════════════════════════════════════════╗"
    echo "║        fCMS Test Runner                ║"
    echo "╚════════════════════════════════════════╝"
    echo -e "$NC"
end

function print_success
    echo -e "$GREEN✓ $argv$NC"
end

function print_error
    echo -e "$RED✗ $argv$NC"
end

function print_info
    echo -e "$YELLOW→ $argv$NC"
end

function show_help
    echo "Usage: ./scripts/test.fish [option]"
    echo ""
    echo "Optionen:"
    echo "  all         Alle Tests ausführen (default)"
    echo "  unit        Nur Unit-Tests"
    echo "  integration Nur Integration-Tests"
    echo "  coverage    Tests mit Code-Coverage (HTML)"
    echo "  coverage-text Tests mit Code-Coverage (Text)"
    echo "  watch       Tests im Watch-Mode"
    echo "  filter      Spezifischen Test ausführen (z.B. filter testSlug)"
    echo "  validator   Nur Validator-Tests"
    echo "  exceptions  Nur Exception-Tests"
    echo "  verbose     Tests mit verbose Output"
    echo "  help        Diese Hilfe anzeigen"
    echo ""
    echo "Beispiele:"
    echo "  ./scripts/test.fish"
    echo "  ./scripts/test.fish coverage"
    echo "  ./scripts/test.fish filter testSlugRejectsPathTraversal"
end

# Wechsle ins Projekt-Verzeichnis
cd $project_root

# PHPUnit binary
set -l phpunit ./vendor/bin/phpunit

# Prüfe ob PHPUnit existiert
if not test -f $phpunit
    print_error "PHPUnit nicht gefunden. Führe 'composer install' aus."
    exit 1
end

print_header

# Standardmäßig alle Tests
set -l command "all"
if test (count $argv) -gt 0
    set command $argv[1]
end

switch $command
    case all
        print_info "Führe alle Tests aus..."
        $phpunit --testdox

    case unit
        print_info "Führe Unit-Tests aus..."
        $phpunit --testsuite "Unit Tests" --testdox

    case integration
        print_info "Führe Integration-Tests aus..."
        $phpunit --testsuite "Integration Tests" --testdox

    case coverage
        print_info "Generiere Code-Coverage (HTML)..."
        $phpunit --coverage-html coverage/html
        print_success "Coverage-Report erstellt: coverage/html/index.html"

        # Öffne im Browser (macOS)
        if test (uname) = "Darwin"
            print_info "Öffne Coverage-Report im Browser..."
            open coverage/html/index.html
        end

    case coverage-text
        print_info "Generiere Code-Coverage (Text)..."
        $phpunit --coverage-text

    case watch
        print_info "Tests im Watch-Mode..."
        print_info "Drücke Ctrl+C zum Beenden"

        # Nutze fswatch wenn verfügbar
        if command -v fswatch > /dev/null
            fswatch -o src/ tests/ | while read num
                clear
                print_header
                print_info "Änderung erkannt, führe Tests aus..."
                $phpunit --testdox
                echo ""
                print_info "Warte auf Änderungen..."
            end
        else
            print_error "fswatch nicht installiert. Installiere mit: brew install fswatch"
            exit 1
        end

    case filter
        if test (count $argv) -lt 2
            print_error "Filter-Name erforderlich. Beispiel: ./scripts/test.fish filter testSlug"
            exit 1
        end

        set -l filter_name $argv[2]
        print_info "Führe Tests mit Filter '$filter_name' aus..."
        $phpunit --filter $filter_name --testdox

    case validator
        print_info "Führe Validator-Tests aus..."
        $phpunit tests/Unit/Core/ValidatorTest.php --testdox

    case exceptions
        print_info "Führe Exception-Tests aus..."
        $phpunit tests/Unit/Exceptions/ --testdox

    case verbose
        print_info "Führe Tests mit verbose Output aus..."
        $phpunit --testdox --verbose

    case debug
        print_info "Führe Tests im Debug-Mode aus..."
        $phpunit --testdox --debug

    case help
        show_help

    case '*'
        print_error "Unbekannte Option: $command"
        echo ""
        show_help
        exit 1
end

# Exit-Code von PHPUnit weitergeben
set -l exit_code $status

if test $exit_code -eq 0
    echo ""
    print_success "Alle Tests bestanden!"
else
    echo ""
    print_error "Tests fehlgeschlagen (Exit-Code: $exit_code)"
end

exit $exit_code
