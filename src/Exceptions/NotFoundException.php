<?php

declare(strict_types=1);

namespace FCMS\Exceptions;

use RuntimeException;

/**
 * NotFoundException
 *
 * Wird geworfen wenn eine Ressource (Seite, Datei, etc.) nicht gefunden wurde.
 */
class NotFoundException extends RuntimeException
{
    /**
     * Erstellt eine neue NotFoundException für eine Seite
     *
     * @param string $slug Der gesuchte Slug
     * @return self
     */
    public static function page(string $slug): self
    {
        return new self("Page with slug '{$slug}' not found");
    }

    /**
     * Erstellt eine neue NotFoundException für eine Datei
     *
     * @param string $path Der gesuchte Dateipfad
     * @return self
     */
    public static function file(string $path): self
    {
        return new self("File '{$path}' not found");
    }
}
