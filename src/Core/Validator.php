<?php

declare(strict_types=1);

namespace FCMS\Core;

use FCMS\Exceptions\ValidationException;

/**
 * Validator-Klasse
 *
 * Zentrale Validierungslogik für alle Benutzereingaben.
 * Schützt vor Injections, Path-Traversal und ungültigen Daten.
 */
class Validator
{
    /**
     * Validiert einen String mit optionalen Längen-Einschränkungen
     *
     * @param mixed $value Der zu validierende Wert
     * @param int $minLength Minimale Länge
     * @param int $maxLength Maximale Länge
     * @param bool $allowEmpty Erlaubt leere Strings
     * @return string Der validierte String
     * @throws ValidationException Bei ungültigem Input
     */
    public static function string(
        mixed $value,
        int $minLength = 0,
        int $maxLength = PHP_INT_MAX,
        bool $allowEmpty = true
    ): string {
        if (!is_string($value)) {
            throw new ValidationException('Value must be a string');
        }

        $length = mb_strlen($value, 'UTF-8');

        if (!$allowEmpty && $length === 0) {
            throw new ValidationException('Value cannot be empty');
        }

        if ($length < $minLength) {
            throw new ValidationException("Value must be at least {$minLength} characters long");
        }

        if ($length > $maxLength) {
            throw new ValidationException("Value must not exceed {$maxLength} characters");
        }

        return $value;
    }

    /**
     * Validiert einen URL-Slug
     *
     * Erlaubt nur: Kleinbuchstaben, Zahlen und Bindestriche
     * Keine Path-Traversal-Zeichen wie ../ oder ./
     *
     * @param mixed $value Der zu validierende Slug
     * @param int $maxLength Maximale Länge
     * @return string Der validierte Slug
     * @throws ValidationException Bei ungültigem Slug
     */
    public static function slug(mixed $value, int $maxLength = 200): string
    {
        if (!is_string($value)) {
            throw new ValidationException('Slug must be a string');
        }

        $value = trim($value);

        if (empty($value)) {
            throw new ValidationException('Slug cannot be empty');
        }

        // Prüfe auf gültige Zeichen (nur a-z, 0-9, Bindestrich)
        if (!preg_match('/^[a-z0-9-]+$/', $value)) {
            throw new ValidationException(
                'Slug must contain only lowercase letters, numbers, and hyphens'
            );
        }

        // Prüfe auf mehrfache aufeinanderfolgende Bindestriche
        if (strpos($value, '--') !== false) {
            throw new ValidationException('Slug cannot contain consecutive hyphens');
        }

        // Prüfe Start/Ende
        if (str_starts_with($value, '-') || str_ends_with($value, '-')) {
            throw new ValidationException('Slug cannot start or end with a hyphen');
        }

        // Prüfe Länge
        if (mb_strlen($value, 'UTF-8') > $maxLength) {
            throw new ValidationException("Slug must not exceed {$maxLength} characters");
        }

        // Prüfe auf reservierte Slugs
        $reserved = ['admin', 'api', 'assets', 'login', 'logout', 'system'];
        if (in_array($value, $reserved, true)) {
            throw new ValidationException("Slug '{$value}' is reserved and cannot be used");
        }

        return $value;
    }

    /**
     * Validiert einen Enum-Wert gegen erlaubte Optionen
     *
     * @param mixed $value Der zu validierende Wert
     * @param array $allowedValues Erlaubte Werte
     * @param string $fieldName Name des Feldes für Fehlermeldung
     * @return string Der validierte Wert
     * @throws ValidationException Bei ungültigem Wert
     */
    public static function enum(mixed $value, array $allowedValues, string $fieldName = 'value'): string
    {
        if (!is_string($value)) {
            throw new ValidationException("{$fieldName} must be a string");
        }

        if (!in_array($value, $allowedValues, true)) {
            $allowed = implode(', ', $allowedValues);
            throw new ValidationException(
                "{$fieldName} must be one of: {$allowed}"
            );
        }

        return $value;
    }

    /**
     * Validiert eine Integer-Zahl
     *
     * @param mixed $value Der zu validierende Wert
     * @param int|null $min Minimaler Wert
     * @param int|null $max Maximaler Wert
     * @return int Der validierte Integer
     * @throws ValidationException Bei ungültigem Wert
     */
    public static function integer(mixed $value, ?int $min = null, ?int $max = null): int
    {
        if (!is_numeric($value)) {
            throw new ValidationException('Value must be a number');
        }

        $intValue = (int)$value;

        if ($min !== null && $intValue < $min) {
            throw new ValidationException("Value must be at least {$min}");
        }

        if ($max !== null && $intValue > $max) {
            throw new ValidationException("Value must not exceed {$max}");
        }

        return $intValue;
    }

    /**
     * Validiert einen Boolean-Wert
     *
     * @param mixed $value Der zu validierende Wert
     * @return bool Der validierte Boolean
     */
    public static function boolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower($value);
            if (in_array($value, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($value, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
        }

        if (is_numeric($value)) {
            return (bool)$value;
        }

        throw new ValidationException('Value must be a boolean');
    }

    /**
     * Validiert ein Array
     *
     * @param mixed $value Der zu validierende Wert
     * @param int|null $minItems Minimale Anzahl Elemente
     * @param int|null $maxItems Maximale Anzahl Elemente
     * @return array Das validierte Array
     * @throws ValidationException Bei ungültigem Wert
     */
    public static function array(mixed $value, ?int $minItems = null, ?int $maxItems = null): array
    {
        if (!is_array($value)) {
            throw new ValidationException('Value must be an array');
        }

        $count = count($value);

        if ($minItems !== null && $count < $minItems) {
            throw new ValidationException("Array must contain at least {$minItems} items");
        }

        if ($maxItems !== null && $count > $maxItems) {
            throw new ValidationException("Array must not exceed {$maxItems} items");
        }

        return $value;
    }

    /**
     * Validiert eine E-Mail-Adresse
     *
     * @param mixed $value Die zu validierende E-Mail
     * @return string Die validierte E-Mail
     * @throws ValidationException Bei ungültiger E-Mail
     */
    public static function email(mixed $value): string
    {
        if (!is_string($value)) {
            throw new ValidationException('Email must be a string');
        }

        $value = trim($value);

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email address');
        }

        return $value;
    }

    /**
     * Validiert eine URL
     *
     * @param mixed $value Die zu validierende URL
     * @param bool $requireHttps Erfordert HTTPS
     * @return string Die validierte URL
     * @throws ValidationException Bei ungültiger URL
     */
    public static function url(mixed $value, bool $requireHttps = false): string
    {
        if (!is_string($value)) {
            throw new ValidationException('URL must be a string');
        }

        $value = trim($value);

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            throw new ValidationException('Invalid URL');
        }

        if ($requireHttps && !str_starts_with($value, 'https://')) {
            throw new ValidationException('URL must use HTTPS');
        }

        return $value;
    }

    /**
     * Validiert JSON-String
     *
     * @param mixed $value Der zu validierende JSON-String
     * @return array Die dekodierten Daten
     * @throws ValidationException Bei ungültigem JSON
     */
    public static function json(mixed $value): array
    {
        if (!is_string($value)) {
            throw new ValidationException('JSON must be a string');
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationException('Invalid JSON: ' . json_last_error_msg());
        }

        if (!is_array($decoded)) {
            throw new ValidationException('JSON must decode to an array');
        }

        return $decoded;
    }

    /**
     * Validiert Seiten-Daten komplett
     *
     * @param array $data Die zu validierenden Seitendaten
     * @return array Die validierten Daten
     * @throws ValidationException Bei ungültigen Daten
     */
    public static function pageData(array $data): array
    {
        $validated = [];

        // Titel validieren
        $validated['title'] = self::string(
            $data['title'] ?? '',
            minLength: 1,
            maxLength: 255,
            allowEmpty: false
        );

        // Slug validieren (falls vorhanden)
        if (isset($data['slug']) && !empty($data['slug'])) {
            $validated['slug'] = self::slug($data['slug']);
        }

        // Status validieren
        $validated['status'] = self::enum(
            $data['status'] ?? 'draft',
            ['draft', 'published'],
            'status'
        );

        // Sections validieren
        $validated['sections'] = self::array($data['sections'] ?? [], minItems: 0, maxItems: 100);

        // Navigations-Einstellungen validieren
        $validated['nav_main'] = self::boolean($data['nav_main'] ?? false);
        $validated['nav_footer'] = self::boolean($data['nav_footer'] ?? false);
        $validated['nav_order'] = self::integer($data['nav_order'] ?? 0, min: 0, max: 9999);
        $validated['nav_label'] = self::string($data['nav_label'] ?? '', maxLength: 100);

        // Meta-Daten validieren
        $validated['meta_description'] = self::string(
            $data['meta_description'] ?? '',
            maxLength: 500
        );
        $validated['meta_keywords'] = self::string(
            $data['meta_keywords'] ?? '',
            maxLength: 255
        );

        return $validated;
    }

    /**
     * Validiert Login-Credentials
     *
     * @param array $data Die Login-Daten
     * @return array Die validierten Credentials
     * @throws ValidationException Bei ungültigen Daten
     */
    public static function loginCredentials(array $data): array
    {
        $validated = [];

        $validated['username'] = self::string(
            $data['username'] ?? '',
            minLength: 1,
            maxLength: 100,
            allowEmpty: false
        );

        $validated['password'] = self::string(
            $data['password'] ?? '',
            minLength: 1,
            maxLength: 1000,
            allowEmpty: false
        );

        return $validated;
    }

    /**
     * Sanitiert HTML-Output (XSS-Schutz)
     *
     * @param string $value Der zu bereinigende String
     * @return string Der bereinigte String
     */
    public static function sanitizeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Validiert einen Dateinamen (gegen Path-Traversal)
     *
     * @param mixed $value Der zu validierende Dateiname
     * @return string Der validierte Dateiname
     * @throws ValidationException Bei ungültigem Dateinamen
     */
    public static function filename(mixed $value): string
    {
        if (!is_string($value)) {
            throw new ValidationException('Filename must be a string');
        }

        $value = trim($value);

        if (empty($value)) {
            throw new ValidationException('Filename cannot be empty');
        }

        // Prüfe auf Path-Traversal-Versuche
        if (
            str_contains($value, '..') ||
            str_contains($value, '/') ||
            str_contains($value, '\\') ||
            str_contains($value, "\0")
        ) {
            throw new ValidationException('Filename contains invalid characters');
        }

        // Prüfe auf reservierte Namen (Windows)
        $reserved = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'LPT1', 'LPT2'];
        $nameWithoutExt = pathinfo($value, PATHINFO_FILENAME);
        if (in_array(strtoupper($nameWithoutExt), $reserved, true)) {
            throw new ValidationException('Filename uses a reserved name');
        }

        return $value;
    }
}
