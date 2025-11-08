<?php

/**
 * PHPUnit Bootstrap File
 *
 * Lädt Autoloader und definiert globale Funktionen für Tests
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Mock renderAdminTemplate Funktion für Controller-Tests
if (!function_exists('renderAdminTemplate')) {
    function renderAdminTemplate(string $template, array $data, $container): string
    {
        // Einfache Mock-Implementation für Tests
        // Gibt einfach den Template-Namen und die Daten als JSON zurück
        return json_encode([
            'template' => $template,
            'data' => array_keys($data)
        ]);
    }
}
