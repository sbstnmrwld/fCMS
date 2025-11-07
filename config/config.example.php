<?php
/**
 * fCMS - Dateibasiertes Content-Management-System
 *
 * Beispiel-Konfigurationsdatei
 * Kopieren Sie diese Datei zu config.php und passen Sie die Werte an.
 */

return [
    // Basis-Konfiguration
    'site' => [
        'name' => 'Meine Website',
        'url' => 'https://example.com',
        'language' => 'de',
        'timezone' => 'Europe/Berlin',
    ],

    // Theme-Einstellungen
    'theme' => [
        'active' => 'default',
    ],

    // Admin-Bereich
    'admin' => [
        'username' => 'admin',
        // Passwort: admin (bitte ändern!)
        // Generieren Sie ein neues Passwort mit: password_hash('IhrPasswort', PASSWORD_DEFAULT)
        'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'session_lifetime' => 1800, // 30 Minuten in Sekunden
        'login_attempts' => 5,
        'login_lockout' => 900, // 15 Minuten in Sekunden
    ],

    // Session-Sicherheit
    'session' => [
        'name' => 'FCMS_SESSION',
        'cookie_lifetime' => 0,
        'cookie_path' => '/',
        'cookie_domain' => '',
        'cookie_secure' => true, // Auf false setzen wenn kein HTTPS
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ],

    // Pfade (relativ zum Webroot)
    'paths' => [
        'content' => __DIR__ . '../content',
        'themes' => __DIR__ . '../themes',
        'admin' => __DIR__ . '../admin',
        'languages' => __DIR__ . '../languages',
        'blocks' => __DIR__ . '../blocks',
        'logs' => __DIR__ . '../logs',
    ],

    // DSGVO-Einstellungen
    'privacy' => [
        'admin_session_notice' => true, // Zeigt Hinweis zu Session-Cookie im Login
        'log_ip_addresses' => false, // IP-Adressen NICHT loggen
        'delete_logs_after_days' => 30,
    ],

    // Debug (NUR in Entwicklung aktivieren!)
    'debug' => [
        'enabled' => false,
        'display_errors' => false,
        'log_errors' => true,
    ],
];
