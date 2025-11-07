<?php

namespace FCMS\Core;

/**
 * CSRF-Token-Manager
 *
 * Generiert und validiert CSRF-Tokens für alle Admin-Formulare
 */
class CsrfManager
{
    private SessionManager $session;
    private string $tokenKey = '_csrf_token';

    public function __construct(SessionManager $session)
    {
        $this->session = $session;
    }

    /**
     * Generiert ein neues CSRF-Token
     */
    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->session->set($this->tokenKey, $token);
        return $token;
    }

    /**
     * Holt das aktuelle CSRF-Token (oder generiert ein neues)
     */
    public function getToken(): string
    {
        $token = $this->session->get($this->tokenKey);

        if ($token === null) {
            $token = $this->generateToken();
        }

        return $token;
    }

    /**
     * Validiert ein CSRF-Token
     */
    public function validateToken(string $token): bool
    {
        $sessionToken = $this->session->get($this->tokenKey);

        if ($sessionToken === null) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Generiert ein verstecktes Input-Feld für Formulare
     */
    public function getTokenField(): string
    {
        $token = $this->getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validiert Token aus Request-Daten
     */
    public function validateRequest(array $data): bool
    {
        $token = $data['csrf_token'] ?? '';
        return $this->validateToken($token);
    }
}
