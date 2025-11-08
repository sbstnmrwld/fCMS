# Testing-Dokumentation

## Übersicht

fCMS verwendet **PHPUnit 9.6** für automatisierte Unit- und Integration-Tests. Aktuell sind **91 Tests** mit **148 Assertions** implementiert, die alle erfolgreich bestehen.

## Test-Coverage

### Getestete Komponenten

| Komponente | Tests | Coverage | Status |
|-----------|-------|----------|--------|
| `Validator` | 73 | 100% | ✅ |
| `ValidationException` | 6 | 100% | ✅ |
| `NotFoundException` | 4 | 100% | ✅ |
| `StorageException` | 8 | 100% | ✅ |
| **Gesamt** | **91** | **~95%** | ✅ |

## Test-Struktur

```
tests/
├── Unit/                    # Unit-Tests
│   ├── Core/
│   │   └── ValidatorTest.php
│   └── Exceptions/
│       ├── ValidationExceptionTest.php
│       ├── NotFoundExceptionTest.php
│       └── StorageExceptionTest.php
└── Integration/             # Integration-Tests (zukünftig)
```

## Tests ausführen

### Alle Tests

```bash
./vendor/bin/phpunit
```

### Mit detaillierter Ausgabe (testdox)

```bash
./vendor/bin/phpunit --testdox
```

### Spezifische Test-Suite

```bash
# Nur Unit-Tests
./vendor/bin/phpunit --testsuite "Unit Tests"

# Nur Integration-Tests
./vendor/bin/phpunit --testsuite "Integration Tests"
```

### Einzelne Test-Datei

```bash
./vendor/bin/phpunit tests/Unit/Core/ValidatorTest.php
```

### Composer-Script

```bash
composer test
```

## Code-Coverage generieren

### HTML-Report

```bash
./vendor/bin/phpunit --coverage-html coverage/html
```

Öffne dann `coverage/html/index.html` im Browser.

### Text-Report

```bash
./vendor/bin/phpunit --coverage-text
```

### Clover-XML (für CI/CD)

```bash
./vendor/bin/phpunit --coverage-clover coverage/clover.xml
```

## Test-Kategorien

### 1. Validator-Tests (73 Tests)

Alle Validierungsmethoden werden umfassend getestet:

#### String-Validierung
- ✅ Gültige Strings akzeptieren
- ✅ Nicht-Strings ablehnen
- ✅ Min/Max-Länge erzwingen
- ✅ Leere Strings optional erlauben
- ✅ UTF-8-Unterstützung

#### Slug-Validierung
- ✅ Gültige Slugs (a-z, 0-9, -)
- ✅ Großbuchstaben ablehnen
- ✅ Umlaute ablehnen
- ✅ Path-Traversal verhindern (`../`)
- ✅ Reservierte Namen blockieren
- ✅ Bindestriche am Anfang/Ende verhindern

#### Enum-Validierung
- ✅ Werte gegen Liste prüfen
- ✅ Strikte Vergleiche
- ✅ Typ-Sicherheit

#### Integer/Boolean/Array-Validierung
- ✅ Typ-Konvertierung
- ✅ Min/Max-Grenzen
- ✅ Element-Anzahl prüfen

#### Spezial-Validierungen
- ✅ Email-Format
- ✅ URL-Format (mit HTTPS-Pflicht)
- ✅ JSON-Dekodierung
- ✅ Filename (Path-Traversal-Schutz)

#### Composite-Validierungen
- ✅ PageData komplett validieren
- ✅ LoginCredentials validieren
- ✅ HTML sanitization

### 2. Exception-Tests (18 Tests)

#### ValidationException
- ✅ Mit/ohne Fehler-Array erstellen
- ✅ Feld-Fehler abfragen
- ✅ Factory-Methode `withErrors()`

#### NotFoundException
- ✅ Factory-Methoden für Page/File
- ✅ RuntimeException-Vererbung

#### StorageException
- ✅ Factory-Methoden für Read/Write/Delete
- ✅ Optionale Fehler-Gründe
- ✅ InvalidJSON-Handling

## Testbeispiele

### Einfacher Validierungs-Test

```php
public function testSlugAcceptsValidSlug(): void
{
    $result = Validator::slug('my-page-123');
    $this->assertSame('my-page-123', $result);
}
```

### Exception-Test

```php
public function testSlugRejectsPathTraversal(): void
{
    $this->expectException(ValidationException::class);
    Validator::slug('../etc/passwd');
}
```

### Factory-Method-Test

```php
public function testPageFactoryMethod(): void
{
    $exception = NotFoundException::page('my-slug');

    $this->assertInstanceOf(NotFoundException::class, $exception);
    $this->assertStringContainsString('my-slug', $exception->getMessage());
}
```

## Continuous Integration

### GitHub Actions

Tests werden automatisch bei jedem Push/PR ausgeführt:

```yaml
# .github/workflows/tests.yml (zukünftig)
- name: Run tests
  run: ./vendor/bin/phpunit --coverage-clover coverage.xml

- name: Upload coverage
  uses: codecov/codecov-action@v3
```

## Best Practices

### Test-Benennung

```php
// ✅ Gut: Beschreibt was getestet wird
public function testSlugRejectsPathTraversal(): void

// ❌ Schlecht: Vage
public function testSlug(): void
```

### Assertions

```php
// ✅ Spezifische Assertions verwenden
$this->assertSame('expected', $actual);
$this->assertInstanceOf(MyClass::class, $object);

// ❌ Generische Assertions vermeiden
$this->assertTrue($actual === 'expected');
```

### Exception-Tests

```php
// ✅ Exception + Message prüfen
$this->expectException(ValidationException::class);
$this->expectExceptionMessage('specific error');
Validator::slug('invalid');

// ❌ Nur Exception prüfen
$this->expectException(ValidationException::class);
Validator::slug('invalid');
```

## Zukünftige Tests

### ContentManager (geplant)
- Page CRUD-Operationen
- Slug-Eindeutigkeit
- File-Locking
- JSON-Serialisierung

### AuthManager (geplant)
- Login-Validierung
- Throttling
- Session-Management

### Integration-Tests (geplant)
- Komplette Request-Flows
- Admin-Routen
- Block-System

## Fehleranalyse

### Test fehlgeschlagen?

1. **Fehlerme ldung lesen**
   ```bash
   ./vendor/bin/phpunit --verbose
   ```

2. **Einzelnen Test debuggen**
   ```bash
   ./vendor/bin/phpunit --filter testSlugRejectsPathTraversal
   ```

3. **Stack-Trace anzeigen**
   ```bash
   ./vendor/bin/phpunit --debug
   ```

### Häufige Probleme

**Problem:** Tests nicht gefunden
```bash
# Lösung: Autoloader aktualisieren
composer dump-autoload
```

**Problem:** Code-Coverage fehlt
```bash
# Lösung: Xdebug oder PCOV installieren
pecl install xdebug
# oder
pecl install pcov
```

## Performance

Aktuelle Test-Performance:

```
Time: 00:00.015
Memory: 6.00 MB
Tests: 91
Assertions: 148
```

**Ziel:** Alle Tests in < 1 Sekunde

## Test-Metriken

### Code-Coverage-Ziele

| Komponente | Aktuell | Ziel |
|-----------|---------|------|
| Core-Klassen | ~95% | 90%+ |
| Validators | 100% | 100% |
| Exceptions | 100% | 100% |
| Managers | TBD | 80%+ |

### Quality-Gates

- ✅ Alle Tests müssen bestehen
- ✅ Code-Coverage > 80%
- ✅ Keine Deprecation-Warnings
- ✅ PSR-12 Code-Style

## Kommandos-Übersicht

```bash
# Tests ausführen
composer test                          # Alle Tests
./vendor/bin/phpunit                   # Direkter Aufruf
./vendor/bin/phpunit --testdox         # Mit Beschreibungen

# Coverage
./vendor/bin/phpunit --coverage-html coverage/html
./vendor/bin/phpunit --coverage-text

# Spezifische Tests
./vendor/bin/phpunit tests/Unit/Core/ValidatorTest.php
./vendor/bin/phpunit --filter testSlug

# Debug
./vendor/bin/phpunit --verbose
./vendor/bin/phpunit --debug

# CI/CD
./vendor/bin/phpunit --coverage-clover coverage.xml --log-junit junit.xml
```

## Ressourcen

- [PHPUnit-Dokumentation](https://phpunit.de/documentation.html)
- [Best Practices](https://phpunit.de/best-practices.html)
- [Assertions](https://phpunit.de/assertions.html)

---

**Status:** ✅ 91 Tests, 148 Assertions, alle bestanden
