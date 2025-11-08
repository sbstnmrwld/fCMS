# Input-Validierung Implementation

## Übersicht

Es wurde ein umfassendes Input-Validierungssystem für fCMS implementiert, das alle Benutzereingaben validiert und vor häufigen Sicherheitslücken schützt.

## Implementierte Komponenten

### 1. Validator-Klasse (`src/Core/Validator.php`)

Zentrale Klasse für alle Validierungsoperationen mit folgenden Methoden:

#### String-Validierung
```php
Validator::string($value, $minLength, $maxLength, $allowEmpty)
```
- Prüft String-Typ
- Validiert Länge (UTF-8-aware)
- Optional: Erlaubt/verbietet leere Strings

#### Slug-Validierung
```php
Validator::slug($value, $maxLength)
```
- Nur Kleinbuchstaben, Zahlen und Bindestriche
- Keine Path-Traversal-Zeichen (`../`, `./`)
- Keine reservierten Namen (admin, api, assets, etc.)
- Keine aufeinanderfolgenden Bindestriche
- Keine Bindestriche am Anfang/Ende

#### Enum-Validierung
```php
Validator::enum($value, $allowedValues, $fieldName)
```
- Prüft gegen definierte Liste erlaubter Werte
- Strict-Vergleich
- Aussagekräftige Fehlermeldungen

#### Integer-Validierung
```php
Validator::integer($value, $min, $max)
```
- Konvertiert numerische Strings
- Optional: Min/Max-Grenzen

#### Boolean-Validierung
```php
Validator::boolean($value)
```
- Konvertiert verschiedene Formate (1, "true", "yes", etc.)

#### Array-Validierung
```php
Validator::array($value, $minItems, $maxItems)
```
- Prüft Array-Typ
- Optional: Anzahl der Elemente

#### Email-Validierung
```php
Validator::email($value)
```
- PHP `FILTER_VALIDATE_EMAIL`

#### URL-Validierung
```php
Validator::url($value, $requireHttps)
```
- PHP `FILTER_VALIDATE_URL`
- Optional: HTTPS-Pflicht

#### JSON-Validierung
```php
Validator::json($value)
```
- Prüft auf gültiges JSON
- Gibt dekodiertes Array zurück
- Aussagekräftige Fehlermeldungen

#### Filename-Validierung
```php
Validator::filename($value)
```
- Verhindert Path-Traversal
- Keine Slashes oder Null-Bytes
- Prüft auf reservierte Windows-Namen

#### Composite-Validierungen
```php
Validator::pageData($data)
Validator::loginCredentials($data)
```
- Validiert komplette Datenstrukturen
- Kombiniert mehrere Validierungen

### 2. Exception-Klassen

#### ValidationException (`src/Exceptions/ValidationException.php`)
- Wird bei ungültigen Eingaben geworfen
- Kann mehrere Feldfehlern speichern
- Methoden: `getErrors()`, `hasError()`, `getError()`, `withErrors()`

#### NotFoundException (`src/Exceptions/NotFoundException.php`)
- Wird geworfen wenn Ressourcen nicht gefunden werden
- Factory-Methoden: `page()`, `file()`

#### StorageException (`src/Exceptions/StorageException.php`)
- Wird bei Dateisystem-Fehlern geworfen
- Factory-Methoden: `cannotWrite()`, `cannotRead()`, `cannotDelete()`, `invalidJson()`

### 3. Integration in ContentManager

**Änderungen in `src/Core/ContentManager.php`:**

- ✅ `createPage()`: Validiert alle Seitendaten via `Validator::pageData()`
- ✅ `updatePage()`: Validiert Slug und alle Update-Daten
- ✅ `deletePage()`: Validiert Slug gegen Path-Traversal
- ✅ `getPage()`: Validiert Slug, wirft Exceptions bei Fehlern
- ✅ `getAllPages()`: Behandelt JSON-Fehler graceful
- ✅ `savePage()`: Prüft JSON-Encoding und File-Operations
- ✅ `pageExists()`: Validiert Slug

**Alle Methoden werfen nun typisierte Exceptions statt `false` zurückzugeben.**

### 4. Integration in AuthManager

**Änderungen in `src/Core/AuthManager.php`:**

- ✅ `attempt()`: Validiert Username und Password via `Validator::loginCredentials()`
- Verhindert SQL-Injection-ähnliche Angriffe
- Verhindert exzessiv lange Inputs

### 5. Integration in admin.php

**Änderungen in `public/admin.php`:**

#### Login-Route
```php
POST /login
```
- Try-Catch um `ValidationException`
- Benutzerfreundliche Fehlermeldungen

#### Seiten-Erstellung
```php
POST /pages/create
```
- JSON-Validierung für Blocks
- Exception-Handling für Validation/Storage-Fehler
- HTTP 400 bei Validierungsfehlern
- HTTP 500 bei Speicherfehlern

#### Seiten-Update
```php
POST /pages/update/{slug}
```
- Slug-Validierung
- JSON-Validierung
- Exception-Handling mit korrekten HTTP-Status-Codes

#### Seiten-Löschen
```php
GET /pages/delete/{slug}
```
- Slug-Validierung
- Exception-Handling

#### Seiten-Bearbeiten (Formular)
```php
GET /pages/edit/{slug}
```
- Slug-Validierung beim Laden

## Sicherheitsverbesserungen

### Path-Traversal-Schutz
**Vorher:**
```php
$slug = $_POST['slug']; // Könnte "../etc/passwd" sein
$path = $contentPath . '/' . $slug . '.json';
```

**Nachher:**
```php
$slug = Validator::slug($_POST['slug']); // Wirft Exception bei "../"
$path = $contentPath . '/' . $slug . '.json';
```

### XSS-Schutz
```php
Validator::sanitizeHtml($userInput); // Escaped gefährliche Zeichen
```

### Injection-Schutz
- Alle Inputs werden typisiert und validiert
- Enum-Validierung für Status-Felder
- Integer-Casting mit Grenzen

### JSON-Injection-Schutz
**Vorher:**
```php
$data = json_decode($input, true); // Keine Fehlerprüfung
```

**Nachher:**
```php
$data = Validator::json($input); // Wirft Exception bei ungültigem JSON
```

## Error-Handling

### Vorher
```php
if (!$page) {
    return false; // Unklar warum es fehlschlug
}
```

### Nachher
```php
if (!$page) {
    throw NotFoundException::page($slug); // Klare Fehlermeldung
}
```

### HTTP-Status-Codes

| Exception | HTTP Status | Bedeutung |
|-----------|-------------|-----------|
| `ValidationException` | 400 | Ungültige Eingabe |
| `NotFoundException` | 404 | Ressource nicht gefunden |
| `StorageException` | 500 | Server-Fehler (Dateisystem) |
| CSRF-Fehler | 403 | Forbidden |

## Tests

Es wurden 33 automatisierte Tests implementiert in `test_validation.php`:

```bash
php test_validation.php
```

**Ergebnis:**
```
Gesamt: 33 Tests
Erfolgreich: 33 ✓
Fehlgeschlagen: 0 ✗
```

### Getestete Szenarien

1. ✅ String-Validierung (Länge, Empty)
2. ✅ Slug-Validierung (Format, Path-Traversal, Reservierte Namen)
3. ✅ Enum-Validierung (Erlaubte Werte)
4. ✅ Integer-Validierung (Min/Max)
5. ✅ Boolean-Validierung (Konvertierung)
6. ✅ Array-Validierung (Anzahl Elemente)
7. ✅ Email-Validierung
8. ✅ JSON-Validierung
9. ✅ Filename-Validierung (Path-Traversal)
10. ✅ PageData-Validierung (Komplette Seite)
11. ✅ LoginCredentials-Validierung

## Verwendung

### Beispiel: Seite erstellen
```php
try {
    $pageData = [
        'title' => $_POST['title'],
        'status' => $_POST['status'],
        'sections' => json_decode($_POST['blocks_json'], true)
    ];

    $contentManager->createPage($pageData); // Wirft ValidationException

    // Erfolg
    redirect('/admin/pages');
} catch (ValidationException $e) {
    // Zeige Validierungsfehler
    showError('Ungültige Eingabe: ' . $e->getMessage());
} catch (StorageException $e) {
    // Zeige Server-Fehler
    showError('Speicherfehler: ' . $e->getMessage());
}
```

### Beispiel: Custom-Validierung
```php
try {
    $slug = Validator::slug($_POST['slug']);
    $title = Validator::string($_POST['title'], minLength: 3, maxLength: 200);
    $status = Validator::enum($_POST['status'], ['draft', 'published']);

    // Daten sind jetzt sicher
} catch (ValidationException $e) {
    // Handle error
}
```

## Backward-Compatibility

**Breaking Changes:**

1. `ContentManager::updatePage()` wirft nun `NotFoundException` statt `false` zurückzugeben
2. `ContentManager::deletePage()` wirft nun Exceptions statt `false` zurückzugeben
3. `ContentManager::getPage()` wirft nun `StorageException` bei JSON-Fehlern

**Migration:**

**Vorher:**
```php
if (!$contentManager->updatePage($slug, $data)) {
    echo "Fehler";
}
```

**Nachher:**
```php
try {
    $contentManager->updatePage($slug, $data);
} catch (NotFoundException $e) {
    echo "Seite nicht gefunden";
} catch (ValidationException $e) {
    echo "Ungültige Daten";
} catch (StorageException $e) {
    echo "Speicherfehler";
}
```

## Performance-Überlegungen

- Validierung erfolgt **vor** Dateisystem-Operationen (fail-fast)
- Regex-Patterns sind optimiert
- Keine unnötigen Validierungen (nur was nötig ist)
- UTF-8-Operationen via `mb_*` Funktionen

## Zukünftige Verbesserungen

Mögliche Erweiterungen:

1. **Sanitization-Methoden**
   - HTML-Purifier Integration
   - Markdown-Sanitization

2. **Custom-Validation-Rules**
   - Callback-basierte Validierung
   - Regex-Patterns als Parameter

3. **Validation-Chains**
   - Fluent-API: `Validator::for($value)->string()->minLength(3)->validate()`

4. **I18n-Fehlermeldungen**
   - Übersetzbare Validierungsmeldungen

5. **Schema-Validation**
   - JSON-Schema für komplexe Strukturen
   - Array-Schema-Definition

## Zusammenfassung

✅ **Implementiert:**
- Umfassende Input-Validierung
- Path-Traversal-Schutz
- Type-Safety
- Exception-basiertes Error-Handling
- 33 automatisierte Tests

✅ **Geschützt gegen:**
- Path-Traversal-Angriffe
- Injection-Angriffe
- XSS (via Sanitization)
- Ungültige Datentypen
- Buffer-Overflows (Längen-Limits)

✅ **Verbessert:**
- Code-Qualität
- Wartbarkeit
- Debugging (klare Fehlermeldungen)
- Security-Posture

**Status: Production-Ready** 🚀
