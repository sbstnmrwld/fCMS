<?php

namespace FCMS\Blocks;

/**
 * Abstrakte Basis-Klasse für Blöcke
 *
 * Bietet gemeinsame Funktionalität für alle Blöcke.
 */
abstract class AbstractBlock implements BlockInterface
{
    /**
     * Validiert Block-Attribute anhand des Schemas
     */
    public function validate(array $attributes): bool
    {
        $defaults = $this->getDefaultAttributes();

        foreach ($defaults as $key => $default) {
            if (!isset($attributes[$key])) {
                // Optionale Attribute
                continue;
            }

            // Typ-Validierung (vereinfacht)
            $expectedType = gettype($default);
            $actualType = gettype($attributes[$key]);

            if ($expectedType !== $actualType && $default !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Merged Attribute mit Defaults
     */
    protected function mergeAttributes(array $attributes): array
    {
        return array_merge($this->getDefaultAttributes(), $attributes);
    }

    /**
     * Escaped HTML-Ausgabe
     */
    protected function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Rendert HTML-Attribute
     */
    protected function renderAttributes(array $attributes): string
    {
        $html = [];

        foreach ($attributes as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if ($value === true) {
                $html[] = $this->escape($key);
            } else {
                $html[] = $this->escape($key) . '="' . $this->escape($value) . '"';
            }
        }

        return implode(' ', $html);
    }
}
