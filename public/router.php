<?php
/**
 * Router Script für PHP Built-in Server
 * Simuliert .htaccess Rewrite-Regeln
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Admin Assets explizit erlauben (statische Dateien)
if (preg_match('#^/admin/assets/(.+)$#', $uri)) {
    return false; // Lass den Server die Datei direkt ausliefern
}

// Admin-Bereich (alle anderen /admin/* Requests)
if (preg_match('#^/admin(/.*)?$#', $uri)) {
    $_SERVER['SCRIPT_NAME'] = '/admin.php';
    require __DIR__ . '/admin.php';
    return true;
}

// Frontend-Routing (alles außer existierende Dateien)
if (file_exists(__DIR__ . $uri)) {
    return false; // Lass den Server die Datei direkt ausliefern
}

// Alle anderen Requests gehen zu index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
return true;
