<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Core;

use FCMS\Core\AuthManager;
use FCMS\Core\SessionManager;
use FCMS\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Tests für AuthManager
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class AuthManagerTest extends TestCase
{
    private AuthManager $authManager;
    private SessionManager $sessionManager;
    private string $tempDir;
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();

        // Erstelle temporäres Verzeichnis für Tests
        $this->tempDir = sys_get_temp_dir() . '/fcms_auth_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);

        $sessionConfig = [
            'name' => 'fcms_test_session',
            'cookie_lifetime' => 0,
            'cookie_path' => '/',
            'cookie_domain' => '',
            'cookie_secure' => false,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'session_lifetime' => 1800,
        ];

        $this->config = [
            'admin' => [
                'username' => 'admin',
                'password' => password_hash('test_password', PASSWORD_DEFAULT),
                'login_attempts' => 5,
                'login_lockout' => 900, // 15 Minuten
            ],
            'paths' => [
                'content' => $this->tempDir,
            ],
        ];

        $this->sessionManager = new SessionManager($sessionConfig);
        $this->authManager = new AuthManager($this->sessionManager, $this->config);
    }

    protected function tearDown(): void
    {
        // Räume auf
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        parent::tearDown();
    }

    public function testIsAuthenticatedReturnsFalseInitially(): void
    {
        $this->assertFalse($this->authManager->isAuthenticated());
    }

    public function testAttemptWithValidCredentialsReturnsTrue(): void
    {
        $result = $this->authManager->attempt('admin', 'test_password');

        $this->assertTrue($result);
    }

    public function testAttemptSetsAuthenticatedInSession(): void
    {
        $this->authManager->attempt('admin', 'test_password');

        $this->assertTrue($this->sessionManager->get('authenticated'));
    }

    public function testAttemptSetsUsernameInSession(): void
    {
        $this->authManager->attempt('admin', 'test_password');

        $this->assertEquals('admin', $this->sessionManager->get('username'));
    }

    public function testAttemptSetsLoginTimeInSession(): void
    {
        $before = time();
        $this->authManager->attempt('admin', 'test_password');
        $after = time();

        $loginTime = $this->sessionManager->get('login_time');

        $this->assertGreaterThanOrEqual($before, $loginTime);
        $this->assertLessThanOrEqual($after, $loginTime);
    }

    public function testAttemptRegeneratesSession(): void
    {
        $this->sessionManager->start();
        $oldId = session_id();

        $this->authManager->attempt('admin', 'test_password');
        $newId = session_id();

        $this->assertNotEquals($oldId, $newId);
    }

    public function testAttemptWithInvalidUsernameReturnsFalse(): void
    {
        $result = $this->authManager->attempt('wrong_user', 'test_password');

        $this->assertFalse($result);
    }

    public function testAttemptWithInvalidPasswordReturnsFalse(): void
    {
        $result = $this->authManager->attempt('admin', 'wrong_password');

        $this->assertFalse($result);
    }

    public function testAttemptThrowsExceptionForEmptyUsername(): void
    {
        $this->expectException(ValidationException::class);

        $this->authManager->attempt('', 'test_password');
    }

    public function testAttemptThrowsExceptionForEmptyPassword(): void
    {
        $this->expectException(ValidationException::class);

        $this->authManager->attempt('admin', '');
    }

    public function testIsAuthenticatedReturnsTrueAfterSuccessfulLogin(): void
    {
        $this->authManager->attempt('admin', 'test_password');

        $this->assertTrue($this->authManager->isAuthenticated());
    }

    public function testLogoutDestroysSession(): void
    {
        $this->authManager->attempt('admin', 'test_password');

        $this->authManager->logout();

        $this->assertFalse($this->authManager->isAuthenticated());
    }

    public function testGetUsernameReturnsNullWhenNotAuthenticated(): void
    {
        $username = $this->authManager->getUsername();

        $this->assertNull($username);
    }

    public function testGetUsernameReturnsUsernameWhenAuthenticated(): void
    {
        $this->authManager->attempt('admin', 'test_password');

        $username = $this->authManager->getUsername();

        $this->assertEquals('admin', $username);
    }

    public function testFailedAttemptsAreLocked(): void
    {
        // Führe zu viele fehlgeschlagene Versuche durch
        for ($i = 0; $i < 5; $i++) {
            $this->authManager->attempt('admin', 'wrong_password');
        }

        // Nächster Versuch sollte gesperrt sein (auch mit korrektem Passwort)
        $result = $this->authManager->attempt('admin', 'test_password');

        $this->assertFalse($result);
    }

    public function testGetLockoutTimeRemainingReturnsZeroWhenNotLocked(): void
    {
        $remaining = $this->authManager->getLockoutTimeRemaining();

        $this->assertEquals(0, $remaining);
    }

    public function testGetLockoutTimeRemainingReturnsTimeWhenLocked(): void
    {
        // Sperre Account
        for ($i = 0; $i < 5; $i++) {
            $this->authManager->attempt('admin', 'wrong_password');
        }

        $remaining = $this->authManager->getLockoutTimeRemaining();

        $this->assertGreaterThan(0, $remaining);
        $this->assertLessThanOrEqual(900, $remaining); // Maximal 15 Minuten
    }

    public function testSuccessfulLoginClearsFailedAttempts(): void
    {
        // Fehlgeschlagene Versuche
        $this->authManager->attempt('admin', 'wrong_password');
        $this->authManager->attempt('admin', 'wrong_password');

        // Erfolgreicher Login
        $this->authManager->attempt('admin', 'test_password');

        // Versuche sollten zurückgesetzt sein
        $remaining = $this->authManager->getLockoutTimeRemaining();
        $this->assertEquals(0, $remaining);
    }

    public function testAttemptAcceptsValidUsernameFormat(): void
    {
        // Validator erlaubt alphanumerische Zeichen und Unterstriche
        // Das ist korrekt, daher testen wir dass gültige Benutzernamen akzeptiert werden
        $result = $this->authManager->attempt('admin_user', 'test_password');

        // Wird false sein weil Passwort nicht stimmt, aber keine ValidationException
        $this->assertFalse($result);
    }
}
