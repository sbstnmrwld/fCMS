<?php

namespace FCMS\Core;

/**
 * Session-Manager mit Sicherheits-Features
 * 
 * Verwaltet sichere Sessions für den Admin-Bereich mit:
 * - HTTP-Only und Secure Cookies
 * - Session-Regeneration nach Login
 * - Session-Timeout bei Inaktivität
 * - Session-Fixation-Schutz
 */
class SessionManager
{
    private array $config;
    private bool $started = false;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Startet eine sichere Session
     */
    public function start(): void
    {
        if ($this->started) {
            return;
        }

        // Session-Konfiguration
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_samesite', $this->config['cookie_samesite']);
        
        if ($this->config['cookie_secure']) {
            ini_set('session.cookie_secure', '1');
        }

        session_name($this->config['name']);
        session_set_cookie_params([
            'lifetime' => $this->config['cookie_lifetime'],
            'path' => $this->config['cookie_path'],
            'domain' => $this->config['cookie_domain'],
            'secure' => $this->config['cookie_secure'],
            'httponly' => $this->config['cookie_httponly'],
            'samesite' => $this->config['cookie_samesite'],
        ]);

        session_start();
        $this->started = true;

        // Prüfe Session-Timeout
        $this->checkTimeout();
    }

    /**
     * Regeneriert die Session-ID (nach erfolgreichem Login)
     */
    public function regenerate(): void
    {
        if (!$this->started) {
            $this->start();
        }

        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }

    /**
     * Setzt einen Session-Wert
     */
    public function set(string $key, $value): void
    {
        if (!$this->started) {
            $this->start();
        }

        $_SESSION[$key] = $value;
    }

    /**
     * Holt einen Session-Wert
     */
    public function get(string $key, $default = null)
    {
        if (!$this->started) {
            $this->start();
        }

        return $_SESSION[$key] ?? $default;
    }

    /**
     * Prüft ob ein Session-Wert existiert
     */
    public function has(string $key): bool
    {
        if (!$this->started) {
            $this->start();
        }

        return isset($_SESSION[$key]);
    }

    /**
     * Löscht einen Session-Wert
     */
    public function delete(string $key): void
    {
        if (!$this->started) {
            $this->start();
        }

        unset($_SESSION[$key]);
    }

    /**
     * Aktualisiert die letzte Aktivität
     */
    public function updateActivity(): void
    {
        $this->set('last_activity', time());
    }

    /**
     * Prüft Session-Timeout
     */
    private function checkTimeout(): void
    {
        $lastActivity = $this->get('last_activity');
        
        if ($lastActivity !== null) {
            $sessionLifetime = $this->config['session_lifetime'] ?? 1800;
            
            if (time() - $lastActivity > $sessionLifetime) {
                $this->destroy();
            }
        }
        
        $this->updateActivity();
    }

    /**
     * Zerstört die Session vollständig
     */
    public function destroy(): void
    {
        if (!$this->started) {
            $this->start();
        }

        $_SESSION = [];

        // Lösche Session-Cookie
        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        $this->started = false;
    }

    /**
     * Prüft ob Session gestartet ist
     */
    public function isStarted(): bool
    {
        return $this->started;
    }
}
