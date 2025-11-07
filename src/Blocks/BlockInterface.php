<?php

namespace FCMS\Blocks;

/**
 * Block-Interface
 *
 * Definiert die Schnittstelle für alle Block-Typen.
 * Jeder Block muss diese Methoden implementieren.
 */
interface BlockInterface
{
    /**
     * Gibt die Block-Metadaten zurück
     */
    public function getMetadata(): array;

    /**
     * Rendert den Block für das Frontend
     */
    public function render(array $attributes, string $content = ''): string;

    /**
     * Rendert das Editor-Interface für den Admin-Bereich
     */
    public function renderEditor(array $attributes, string $content = ''): string;

    /**
     * Validiert Block-Attribute
     */
    public function validate(array $attributes): bool;

    /**
     * Gibt Standard-Attribute zurück
     */
    public function getDefaultAttributes(): array;
}
