# Race Condition Fix - Slug-Generierung

## Problem

Bei gleichzeitigen Page-Erstellungen mit identischen Titeln konnte es zu Race Conditions kommen:

1. **Thread A** prüft ob `test-page` existiert → NEIN
2. **Thread B** prüft ob `test-page` existiert → NEIN (A hat noch nicht gespeichert)
3. **Thread A** erstellt `test-page.json`
4. **Thread B** erstellt `test-page.json` → **überschreibt Datei von A!**

Dies führte zu:
- Verlorenen Seiten-Daten (überschrieben)
- Duplikaten in der Datenbank
- Inkonsistenten Zuständen

## Lösung

Implementierung von **File-Locking** im `ContentManager::createPage()`:

```php
public function createPage(array $data): bool
{
    // ... Validierung ...

    // Kritischer Abschnitt: Slug-Eindeutigkeit + Datei-Erstellung
    $lockFile = $this->contentPath . '/.slug-creation.lock';
    $lockHandle = fopen($lockFile, 'c');

    try {
        // Exklusives Lock erwerben
        if (!flock($lockHandle, LOCK_EX)) {
            throw new StorageException('Konnte Lock nicht erwerben');
        }

        // Slug-Eindeutigkeit prüfen (jetzt thread-safe)
        $slug = $this->ensureUniqueSlug($slug);

        // Seite erstellen
        $page = [...];

        // Seite speichern (noch unter Lock)
        $result = $this->savePage($slug, $page);

        // Lock freigeben
        flock($lockHandle, LOCK_UN);

        return $result;
    } finally {
        fclose($lockHandle);
    }
}
```

### Funktionsweise

1. **Lock-Datei**: `.slug-creation.lock` im Content-Verzeichnis
2. **Exklusives Lock**: `LOCK_EX` - nur ein Thread kann gleichzeitig zugreifen
3. **Atomare Operation**: Slug-Prüfung + Datei-Erstellung unter einem Lock
4. **Automatische Freigabe**: Lock wird in `finally`-Block freigegeben

### Vorteile

- ✅ **Thread-Safe**: Keine Duplikate mehr möglich
- ✅ **Einfach**: Standard PHP-Locking, keine externen Dependencies
- ✅ **Zuverlässig**: Lock wird auch bei Exceptions freigegeben
- ✅ **Performant**: Lock nur während kritischem Abschnitt

## Tests

### Unit-Tests

**`ContentManagerRaceConditionTest.php`** - 3 Tests:

1. **testConcurrentSlugGenerationCreatesUniquePages**
   - Simuliert 5 gleichzeitige Page-Erstellungen
   - Prüft, dass alle Slugs eindeutig sind
   - Erwartet: `test-page`, `test-page-1`, `test-page-2`, etc.

2. **testFileLocksPreventSimultaneousAccess**
   - Testet Lock-Mechanismus direkt
   - Prüft, dass zweites Lock fehlschlägt während erstes aktiv
   - Prüft, dass Lock nach Freigabe verfügbar ist

3. **testLockFileIsCreatedAndAccessible**
   - Prüft Erstellung der Lock-Datei
   - Prüft, dass Lock nach Operation freigegeben ist

```bash
$ vendor/bin/phpunit tests/Unit/Core/ContentManagerRaceConditionTest.php
PHPUnit 9.6.29 by Sebastian Bergmann and contributors.

...                                                                 3 / 3 (100%)

OK (3 tests, 31 assertions)
```

### Integration-Tests

**`PageCreationRaceConditionTest.php`** - Erfordert laufenden Server:

- Testet echte parallele HTTP-Requests mit `curl_multi`
- Prüft Slug-Eindeutigkeit bei 5 gleichzeitigen Requests
- Testet schnelle sequentielle Erstellungen

**Hinweis**: Integration-Tests erfordern CSRF-Tokens und Admin-Session.
Können mit `@group integration` markiert und separat ausgeführt werden.

## Performance

- **Overhead**: Minimal (~1-2ms für Lock-Operation)
- **Skalierung**: Gut bei normalem Traffic
- **Bottleneck**: Bei sehr hohem gleichzeitigen Schreibzugriff kann Lock zum Bottleneck werden

### Performance-Optimierungen (falls nötig)

Wenn Lock zum Bottleneck wird bei sehr hohem Traffic:

1. **Lock-Scope reduzieren**: Nur Slug-Prüfung locken, nicht Datei-Schreibung
2. **Lock-Granularität**: Separate Locks pro Slug-Präfix
3. **Optimistic Locking**: Retry-Mechanismus statt Blocking
4. **Database**: Wechsel zu DB mit UNIQUE-Constraint auf Slug

## Verwendung

Keine Code-Änderungen nötig - automatisch aktiv bei `createPage()`:

```php
$contentManager = new ContentManager($contentPath);

// Thread-safe, auch bei gleichzeitigen Aufrufen
$contentManager->createPage([
    'title' => 'Meine Seite',
    'status' => 'published',
    // ...
]);
```

## Monitoring

Lock-Datei wird erstellt unter:
```
content/pages/.slug-creation.lock
```

Bei Problemen:
- Prüfen ob Datei existiert und nicht dauerhaft gelockt
- Prüfen von Schreibrechten im Content-Verzeichnis
- Logs prüfen auf `StorageException` Fehler

## Weitere Informationen

- PHP File Locking: https://www.php.net/manual/en/function.flock.php
- Race Conditions: https://en.wikipedia.org/wiki/Race_condition
- Atomic Operations: https://en.wikipedia.org/wiki/Atomic_operation
