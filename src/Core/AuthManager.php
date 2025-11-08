<?php

declare(strict_types=1);

namespace FCMS\Core;

use FCMS\Exceptions\ValidationException;

/**
 * Authentifizierungs-Manager
 *
 * Verwaltet Admin-Login mit:
 * - Passwort-Verifizierung
 * - Login-Throttling gegen Brute-Force
 * - Session-basierte Authentifizierung
 */
class AuthManager
{
    private SessionManager $session;
    private array $config;
    private string $attemptsFile;

    public function __construct(SessionManager $session, array $config)
    {
        $this->session = $session;
        $this->config = $config;
        $this->attemptsFile = $config['paths']['content'] . '/login_attempts.json';
    }

    /**
     * Prüft ob Benutzer eingeloggt ist
     */
    public function isAuthenticated(): bool
    {
        return $this->session->get('authenticated', false) === true;
    }

    /**
     * Login-Versuch
     *
     * @param string $username Der Benutzername
     * @param string $password Das Passwort
     * @return bool True bei erfolgreichem Login
     * @throws ValidationException Bei ungültigen Credentials
     */
    public function attempt(string $username, string $password): bool
    {
        // Validiere Login-Daten
        $validated = Validator::loginCredentials([
            'username' => $username,
            'password' => $password
        ]);

        $username = $validated['username'];
        $password = $validated['password'];

        // Prüfe Login-Sperre
        if ($this->isLocked()) {
            return false;
        }

        // Validiere Credentials
        $isValid = $this->validateCredentials($username, $password);

        if ($isValid) {
            // Erfolgreicher Login
            $this->session->regenerate();
            $this->session->set('authenticated', true);
            $this->session->set('username', $username);
            $this->session->set('login_time', time());
            $this->clearAttempts();
            return true;
        } else {
            // Fehlgeschlagener Login
            $this->recordFailedAttempt();
            return false;
        }
    }

    /**
     * Validiert Benutzername und Passwort
     */
    private function validateCredentials(string $username, string $password): bool
    {
        $validUsername = $this->config['admin']['username'];
        $validPasswordHash = $this->config['admin']['password'];

        if ($username !== $validUsername) {
            return false;
        }

        return password_verify($password, $validPasswordHash);
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        $this->session->destroy();
    }

    /**
     * Zeichnet fehlgeschlagenen Login-Versuch auf
     */
    private function recordFailedAttempt(): void
    {
        $attempts = $this->getAttempts();
        $attempts[] = [
            'time' => time(),
            // IP-Adresse wird NICHT gespeichert (DSGVO)
        ];

        // Behalte nur die letzten Versuche
        $maxAttempts = $this->config['admin']['login_attempts'];
        $attempts = array_slice($attempts, -$maxAttempts);

        $this->saveAttempts($attempts);
    }

    /**
     * Holt gespeicherte Login-Versuche
     */
    private function getAttempts(): array
    {
        if (!file_exists($this->attemptsFile)) {
            return [];
        }

        $content = file_get_contents($this->attemptsFile);
        $attempts = json_decode($content, true);

        return is_array($attempts) ? $attempts : [];
    }

    /**
     * Speichert Login-Versuche
     */
    private function saveAttempts(array $attempts): void
    {
        $dir = dirname($this->attemptsFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->attemptsFile, json_encode($attempts));
    }

    /**
     * Löscht Login-Versuche
     */
    private function clearAttempts(): void
    {
        if (file_exists($this->attemptsFile)) {
            unlink($this->attemptsFile);
        }
    }

    /**
     * Prüft ob Login gesperrt ist
     */
    private function isLocked(): bool
    {
        $attempts = $this->getAttempts();

        if (empty($attempts)) {
            return false;
        }

        $maxAttempts = $this->config['admin']['login_attempts'];
        $lockoutTime = $this->config['admin']['login_lockout'];

        // Zähle aktuelle Versuche innerhalb des Lockout-Zeitfensters
        $currentTime = time();
        $recentAttempts = array_filter($attempts, function($attempt) use ($currentTime, $lockoutTime) {
            return ($currentTime - $attempt['time']) < $lockoutTime;
        });

        return count($recentAttempts) >= $maxAttempts;
    }

    /**
     * Gibt verbleibende Sperr-Zeit in Sekunden zurück
     */
    public function getLockoutTimeRemaining(): int
    {
        if (!$this->isLocked()) {
            return 0;
        }

        $attempts = $this->getAttempts();
        $lastAttempt = end($attempts);
        $lockoutTime = $this->config['admin']['login_lockout'];
        $elapsed = time() - $lastAttempt['time'];

        return max(0, $lockoutTime - $elapsed);
    }

    /**
     * Holt aktuellen Benutzernamen
     */
    public function getUsername(): ?string
    {
        return $this->session->get('username');
    }
}
