<?php

declare(strict_types=1);

namespace FCMS\Exceptions;

use RuntimeException;

/**
 * StorageException
 *
 * Wird geworfen bei Dateisystem-Fehlern (Lesen, Schreiben, Löschen).
 */
class StorageException extends RuntimeException
{
    /**
     * Erstellt eine neue StorageException für Schreibfehler
     *
     * @param string $path Der Dateipfad
     * @param string|null $reason Optional: Grund des Fehlers
     * @return self
     */
    public static function cannotWrite(string $path, ?string $reason = null): self
    {
        $message = "Cannot write to file '{$path}'";
        if ($reason) {
            $message .= ": {$reason}";
        }
        return new self($message);
    }

    /**
     * Erstellt eine neue StorageException für Lesefehler
     *
     * @param string $path Der Dateipfad
     * @param string|null $reason Optional: Grund des Fehlers
     * @return self
     */
    public static function cannotRead(string $path, ?string $reason = null): self
    {
        $message = "Cannot read file '{$path}'";
        if ($reason) {
            $message .= ": {$reason}";
        }
        return new self($message);
    }

    /**
     * Erstellt eine neue StorageException für Löschfehler
     *
     * @param string $path Der Dateipfad
     * @param string|null $reason Optional: Grund des Fehlers
     * @return self
     */
    public static function cannotDelete(string $path, ?string $reason = null): self
    {
        $message = "Cannot delete file '{$path}'";
        if ($reason) {
            $message .= ": {$reason}";
        }
        return new self($message);
    }

    /**
     * Erstellt eine neue StorageException für ungültiges JSON
     *
     * @param string $path Der Dateipfad
     * @return self
     */
    public static function invalidJson(string $path): self
    {
        return new self("File '{$path}' contains invalid JSON: " . json_last_error_msg());
    }
}
