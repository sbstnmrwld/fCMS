<?php

declare(strict_types=1);

namespace FCMS\Exceptions;

use InvalidArgumentException;

/**
 * ValidationException
 *
 * Wird geworfen bei ungültigen Eingabedaten.
 * Ermöglicht differenziertes Error-Handling.
 */
class ValidationException extends InvalidArgumentException
{
    private array $errors = [];

    /**
     * Erstellt eine neue ValidationException
     *
     * @param string $message Die Fehlermeldung
     * @param array $errors Optional: Array mit Feldnamen => Fehlermeldungen
     */
    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    /**
     * Gibt alle Validierungsfehler zurück
     *
     * @return array Assoziatives Array mit Feldnamen => Fehlermeldungen
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Prüft ob ein spezifisches Feld einen Fehler hat
     *
     * @param string $field Der Feldname
     * @return bool True wenn Fehler vorhanden
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Gibt den Fehler für ein spezifisches Feld zurück
     *
     * @param string $field Der Feldname
     * @return string|null Die Fehlermeldung oder null
     */
    public function getError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * Erstellt eine ValidationException mit mehreren Feldfehlern
     *
     * @param array $errors Assoziatives Array mit Feldnamen => Fehlermeldungen
     * @return self
     */
    public static function withErrors(array $errors): self
    {
        $message = 'Validation failed: ' . implode(', ', array_keys($errors));
        return new self($message, $errors);
    }
}
